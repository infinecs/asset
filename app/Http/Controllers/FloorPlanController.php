<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Location;
use App\Models\Workstation;
use Illuminate\Http\Request;
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

    public function show(Request $request)
    {
        $this->authorizeAdmin();

        $locations = Location::withCount('workstations')->orderBy('name')->get();

        $location = $locations->firstWhere('id', (int) $request->input('location'))
            ?? $locations->first(fn (Location $l) => $l->floor_plan_path)
            ?? $locations->first(fn (Location $l) => $l->workstations_count > 0)
            ?? $locations->first();

        $desks = collect();
        if ($location) {
            $desks = $location->workstations()
                ->with(['occupant', 'assets', 'latestAudit'])
                ->orderBy('room')
                ->orderBy('number')
                ->get()
                ->map(fn (Workstation $w) => $this->deskData($w));
        }

        $selectedDesk = $desks->firstWhere('id', (int) $request->input('desk'))['id'] ?? null;

        return view('workstations.floor-plan', compact('locations', 'location', 'desks', 'selectedDesk'));
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
            $location->workstations()->update(['plan_x' => null, 'plan_y' => null, 'plan_w' => null, 'plan_h' => null]);
        });

        return redirect()->route('workstations.floor-plan', ['location' => $location->id])
            ->with('success', "Floor plan for {$location->name} removed, and its desks were taken off the plan.");
    }

    /**
     * Save every desk's position in one go (the "Save layout" button). A null position takes the
     * desk off the plan.
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
        ]);

        $positions = collect($validated['positions'])->keyBy('id');
        $desks = $location->workstations()->whereIn('id', $positions->keys())->get();

        if ($desks->count() !== $positions->count()) {
            throw ValidationException::withMessages(['positions' => 'Some of those desks are not in ' . $location->name . '. Reload the page and try again.']);
        }

        DB::transaction(function () use ($desks, $positions) {
            foreach ($desks as $desk) {
                $position = $positions[$desk->id];
                $desk->plan_x = isset($position['x']) ? round((float) $position['x'], 3) : null;
                $desk->plan_y = isset($position['y']) ? round((float) $position['y'], 3) : null;
                $desk->plan_w = isset($position['w']) ? round((float) $position['w'], 3) : null;
                $desk->plan_h = isset($position['h']) ? round((float) $position['h'], 3) : null;
                if ($desk->isDirty(['plan_x', 'plan_y', 'plan_w', 'plan_h'])) {
                    $desk->save();
                }
            }
        });

        $placed = $location->workstations()->whereNotNull('plan_x')->count();

        return response()->json([
            'message' => "Layout saved. {$placed} of {$location->workstations()->count()} desk(s) are on the plan.",
            'placed' => $placed,
        ]);
    }

    private function deskData(Workstation $w): array
    {
        $assets = $w->assets;

        return [
            'id' => $w->id,
            'code' => $w->name,
            'desk' => $w->desk_code,
            'room' => $w->room,
            'label' => trim(($w->room ? $w->room . ' ' : '') . $w->desk_code),
            'occupant' => $w->occupant?->name,
            'status' => $w->occupant ? 'assigned' : ($assets->isEmpty() ? 'empty' : 'hotdesk'),
            'audit_due' => $w->isAuditDue(),
            'audit_issues' => (bool) $w->latestAudit?->hasDiscrepancies(),
            'last_audit' => $w->latestAudit?->created_at?->format('d M Y'),
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
