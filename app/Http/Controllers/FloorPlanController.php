<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Workstation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * An office's floor plan: an uploaded image of the layout with each workstation pinned to its
 * spot. Positions are stored on the workstation as percentages of the image (plan_x / plan_y).
 */
class FloorPlanController extends Controller
{
    private const MAX_IMAGE_KB = 10240;

    /**
     * What deskData() needs loaded on each workstation.
     */
    private const DESK_RELATIONS = ['occupant', 'assets.assignedEmployee', 'latestAudit'];

    /**
     * Most desks one "Add copies" can create, and most new desks in one layout save.
     */
    public const MAX_COPIES = 20;
    private const MAX_NEW_PER_SAVE = 200;

    public function show(Request $request)
    {
        $this->authorizeAdmin();

        $locations = Location::withCount('workstations')->orderBy('name')->get();

        $location = $locations->firstWhere('id', (int) $request->input('location'))
            ?? $locations->first(fn (Location $l) => $l->floor_plan_path)
            ?? $locations->first(fn (Location $l) => $l->workstations_count > 0)
            ?? $locations->first();

        $desks = $location ? $this->desksOf($location) : collect();

        $selectedDesk = $desks->firstWhere('id', (int) $request->input('desk'))['id'] ?? null;

        // Who can be seated: active employees, plus anyone already at a desk. Each one carries the
        // desks they sit at (in any office), so the page can warn before moving them.
        $employees = Employee::where('status', 'active')
            ->orWhereHas('workstations')
            ->with('workstations:id,name,occupant_id')
            ->orderBy('name')
            ->get(['id', 'name', 'id_number', 'status'])
            ->map(fn (Employee $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'id_number' => $e->id_number,
                'active' => $e->status === 'active',
                'desks' => $e->workstations->map(fn (Workstation $w) => ['id' => $w->id, 'code' => $w->name])->values(),
            ]);

        $audit = ['interval' => Workstation::AUDIT_INTERVAL_DAYS, 'soon' => Workstation::AUDIT_DUE_SOON_DAYS];

        return view('workstations.floor-plan', compact('locations', 'location', 'desks', 'selectedDesk', 'employees', 'audit'));
    }

    /**
     * A print-ready page of a location's floor plan with every desk labelled, to print or save
     * as a PDF. The page itself lets you pick names on/off, the colouring and the desk list.
     */
    public function print(Request $request)
    {
        $this->authorizeAdmin();

        $location = Location::findOrFail((int) $request->input('location'));

        if (!$location->floor_plan_path) {
            return redirect()->route('workstations.floor-plan', ['location' => $location->id])
                ->with('error', "{$location->name} has no floor plan to print yet.");
        }

        $desks = $this->desksOf($location);
        $colour = $request->input('colour') === 'audit' ? 'audit' : 'occupancy';
        $names = $request->input('names', '1') !== '0';
        $list = $request->boolean('list');

        return view('workstations.floor-plan-print', compact('location', 'desks', 'colour', 'names', 'list'));
    }

    private function desksOf(Location $location): Collection
    {
        return $location->workstations()
            ->with(self::DESK_RELATIONS)
            ->orderBy('room')
            ->orderBy('number')
            ->get()
            ->map(fn (Workstation $w) => $this->deskData($w));
    }

    /**
     * Seat someone at a desk straight from the floor plan (or make it a hot desk with a null
     * occupant). Someone who already sits elsewhere is only moved when "move" is set; their old
     * desk becomes a hot desk.
     */
    public function setOccupant(Request $request, Workstation $workstation)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'occupant_id' => 'nullable|integer|exists:employees,id',
            'move' => 'boolean',
        ]);

        $employee = isset($validated['occupant_id']) ? Employee::find($validated['occupant_id']) : null;

        if ($employee && $employee->id !== $workstation->occupant_id && $employee->status !== 'active') {
            throw ValidationException::withMessages(['occupant_id' => "{$employee->name} is not an active employee."]);
        }

        $elsewhere = $employee
            ? $employee->workstations()->whereKeyNot($workstation->id)->with(self::DESK_RELATIONS)->get()
            : collect();

        if ($elsewhere->isNotEmpty() && !$request->boolean('move')) {
            throw ValidationException::withMessages([
                'occupant_id' => "{$employee->name} already sits at " . $elsewhere->pluck('name')->join(', ') . '. Confirm to move them here.',
            ]);
        }

        DB::transaction(function () use ($workstation, $employee, $elsewhere) {
            $elsewhere->each(fn (Workstation $w) => $w->update(['occupant_id' => null]));
            $workstation->update(['occupant_id' => $employee?->id]);
        });

        $changed = collect([$workstation->fresh(self::DESK_RELATIONS)])
            ->concat($elsewhere->map(fn (Workstation $w) => $w->fresh(self::DESK_RELATIONS)));

        $message = $employee
            ? "{$employee->name} now sits at {$workstation->name}."
                . ($elsewhere->isNotEmpty() ? ' ' . $elsewhere->pluck('name')->join(', ') . ' is now a hot desk.' : '')
            : "{$workstation->name} is now a hot desk.";

        return response()->json([
            'message' => $message,
            'desks' => $changed->map(fn (Workstation $w) => $this->deskData($w))->values(),
        ]);
    }

    public function upload(Request $request, Location $location)
    {
        $this->authorizeAdmin();

        $request->validate([
            'floor_plan' => 'required|image|mimes:jpg,jpeg,png,webp|max:' . self::MAX_IMAGE_KB,
        ], [
            'floor_plan.required' => 'Choose an image of the floor plan to upload.',
            'floor_plan.image' => 'The floor plan must be an image (JPG, PNG or WEBP).',
            'floor_plan.mimes' => 'The floor plan must be a JPG, PNG or WEBP image.',
            'floor_plan.max' => 'The floor plan image must not be larger than 10 MB.',
        ]);

        $replacing = (bool) $location->floor_plan_path;
        $old = $location->floor_plan_path;

        $file = $request->file('floor_plan');
        $path = $file->storeAs('floor-plans', $location->id . '-' . now()->timestamp . '.' . strtolower($file->extension() ?: 'png'), 'public');
        $location->update(['floor_plan_path' => $path]);

        if ($old && $old !== $path) {
            Storage::disk('public')->delete($old);
        }

        return redirect()->route('workstations.floor-plan', ['location' => $location->id])->with('success', $replacing
            ? "Floor plan for {$location->name} replaced. Desks keep their positions; drag any that no longer line up."
            : "Floor plan for {$location->name} uploaded. Use Edit layout to place the desks.");
    }

    public function destroy(Location $location)
    {
        $this->authorizeAdmin();

        DB::transaction(function () use ($location) {
            if ($location->floor_plan_path) {
                Storage::disk('public')->delete($location->floor_plan_path);
            }
            $location->update(['floor_plan_path' => null]);
            $location->workstations()->update(['plan_x' => null, 'plan_y' => null, 'plan_w' => null, 'plan_h' => null, 'plan_rotation' => 0]);
        });

        return redirect()->route('workstations.floor-plan', ['location' => $location->id])
            ->with('success', "Floor plan for {$location->name} removed, and its desks were taken off the plan.");
    }

    /**
     * Save every desk's position in one go (the "Save layout" button). A null position takes the
     * desk off the plan. new_desks are copies added on the plan ("Add copies"): each is created
     * in its room with the next free number and placed where it was dropped. The response maps
     * each copy's temporary key to the desk that was created for it.
     */
    public function saveLayout(Request $request, Location $location)
    {
        $this->authorizeAdmin();

        if (!$location->floor_plan_path) {
            throw ValidationException::withMessages(['positions' => 'Upload a floor plan for this location before placing desks.']);
        }

        $validated = $request->validate([
            'positions' => 'present|array',
            'positions.*.id' => 'required|integer|distinct',
            'positions.*.x' => 'nullable|numeric|between:0,100|required_with:positions.*.y',
            'positions.*.y' => 'nullable|numeric|between:0,100|required_with:positions.*.x',
            // Optional marker size; leave both out (or null) for the default small label.
            'positions.*.w' => 'nullable|numeric|between:0.5,100|required_with:positions.*.h',
            'positions.*.h' => 'nullable|numeric|between:0.5,100|required_with:positions.*.w',
            // Horizontal (0) or vertical (90 degrees clockwise); left out = horizontal.
            'positions.*.r' => 'nullable|integer|in:0,90',
            'new_desks' => 'sometimes|array|max:' . self::MAX_NEW_PER_SAVE,
            'new_desks.*.key' => 'required|string|max:40|distinct',
            'new_desks.*.room' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
            'new_desks.*.x' => 'required|numeric|between:0,100',
            'new_desks.*.y' => 'required|numeric|between:0,100',
            'new_desks.*.w' => 'nullable|numeric|between:0.5,100|required_with:new_desks.*.h',
            'new_desks.*.h' => 'nullable|numeric|between:0.5,100|required_with:new_desks.*.w',
            'new_desks.*.r' => 'nullable|integer|in:0,90',
        ], [
            'new_desks.max' => 'You can add at most ' . self::MAX_NEW_PER_SAVE . ' new desks in one save.',
        ]);

        $positions = collect($validated['positions'])->keyBy('id');
        $desks = $location->workstations()->whereIn('id', $positions->keys())->get();

        if ($desks->count() !== $positions->count()) {
            throw ValidationException::withMessages(['positions' => 'Some of those desks are not in ' . $location->name . '. Reload the page and try again.']);
        }

        $newDesks = collect($validated['new_desks'] ?? []);
        if ($newDesks->isNotEmpty() && !$location->code) {
            throw ValidationException::withMessages(['new_desks' => "Give {$location->name} a workstation code (e.g. P1A) on the Locations page before adding desks."]);
        }

        $created = DB::transaction(function () use ($desks, $positions, $newDesks, $location) {
            foreach ($desks as $desk) {
                $position = $positions[$desk->id];
                $desk->plan_x = isset($position['x']) ? round((float) $position['x'], 3) : null;
                $desk->plan_y = isset($position['y']) ? round((float) $position['y'], 3) : null;
                $desk->plan_w = isset($position['w']) ? round((float) $position['w'], 3) : null;
                $desk->plan_h = isset($position['h']) ? round((float) $position['h'], 3) : null;
                $desk->plan_rotation = (int) ($position['r'] ?? 0);
                if ($desk->isDirty(['plan_x', 'plan_y', 'plan_w', 'plan_h', 'plan_rotation'])) {
                    $desk->save();
                }
            }

            // Next free number per room, counted on from the highest one in use.
            $next = [];
            $created = [];
            foreach ($newDesks as $new) {
                $room = isset($new['room']) && $new['room'] !== '' ? strtoupper($new['room']) : null;
                $key = $room ?? '';
                $next[$key] ??= (int) $location->workstations()->where('room', $room)->max('number') + 1;

                if ($next[$key] > 999) {
                    throw ValidationException::withMessages(['new_desks' => 'Room ' . ($room ?? '(none)') . ' has run out of desk numbers (999 is the highest).']);
                }

                $created[$new['key']] = Workstation::create([
                    'location_id' => $location->id,
                    'room' => $room,
                    'number' => $next[$key]++,
                    'plan_x' => round((float) $new['x'], 3),
                    'plan_y' => round((float) $new['y'], 3),
                    'plan_w' => isset($new['w']) ? round((float) $new['w'], 3) : null,
                    'plan_h' => isset($new['h']) ? round((float) $new['h'], 3) : null,
                    'plan_rotation' => (int) ($new['r'] ?? 0),
                ]);
            }

            return $created;
        });

        $placed = $location->workstations()->whereNotNull('plan_x')->count();
        $message = "Layout saved. {$placed} of {$location->workstations()->count()} desk(s) are on the plan.";
        if ($created) {
            $codes = collect($created)->pluck('name');
            $message .= ' Added ' . count($created) . ' desk(s): ' . ($codes->count() > 3 ? $codes->first() . ' to ' . $codes->last() : $codes->join(', ')) . '.';
        }

        return response()->json([
            'message' => $message,
            'placed' => $placed,
            'created' => (object) collect($created)->map(fn (Workstation $w) => $this->deskData($w->load(self::DESK_RELATIONS)))->all(),
        ]);
    }

    private function deskData(Workstation $w): array
    {
        $assets = $w->assets;

        return [
            'id' => $w->id,
            'code' => $w->name,
            'desk' => $w->desk_code,
            'number' => $w->number,
            'room' => $w->room,
            'label' => trim(($w->room ? $w->room . ' ' : '') . $w->desk_code),
            'occupant' => $w->occupant?->name,
            'occupant_id' => $w->occupant_id,
            'occupant_url' => route('workstations.occupant', $w),
            'status' => $w->occupant ? 'assigned' : ($assets->isEmpty() ? 'empty' : 'hotdesk'),
            'audit_due' => $w->isAuditDue(),
            'audit_issues' => (bool) $w->latestAudit?->hasDiscrepancies(),
            'last_audit' => $w->latestAudit?->created_at?->format('d M Y'),
            'audit_age' => $w->auditAgeInDays(),
            'warnings' => $w->warnings(),
            'assets' => $assets->map(fn (Asset $a) => [
                'tag' => $a->asset_tag,
                'name' => $a->name,
                'icon' => $a->type_icon,
                'url' => route('assets.show', $a),
            ])->values(),
            'search' => mb_strtolower(implode(' ', array_filter([
                $w->name, $w->room, $w->desk_code, $w->occupant?->name,
                ...$assets->pluck('asset_tag')->all(),
            ]))),
            'x' => $w->plan_x,
            'y' => $w->plan_y,
            'w' => $w->plan_w,
            'h' => $w->plan_h,
            'r' => $w->plan_rotation ?? 0,
            'url' => route('workstations.show', $w),
            'audit_url' => route('workstations.audit', $w),
        ];
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
