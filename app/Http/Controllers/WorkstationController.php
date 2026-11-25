<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Workstation;
use App\Models\WorkstationAudit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WorkstationController extends Controller
{
    /**
     * Upper bound on how many workstations one bulk-add can create.
     */
    private const MAX_BULK = 200;

    private const ROOM_RULE = ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'];

    /**
     * Most labels printed in one go; a whole office floor fits comfortably.
     */
    private const MAX_LABELS = 500;

    /**
     * Most assets placed at a desk in one go (scanning a desk's equipment on a phone).
     */
    public const MAX_PLACE_AT_ONCE = 50;

    /**
     * The workstation list, as a paginated table or (view=rooms) as a floor overview of every
     * matching desk grouped by office and room.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $view = $request->input('view') === 'rooms' ? 'rooms' : 'list';
        $query = $this->filteredQuery($request);

        if ($view === 'rooms') {
            $workstations = null;
            $floors = $query->get()
                ->groupBy(fn (Workstation $w) => $w->location?->name ?? 'No location')
                ->map(fn ($desks) => $desks->groupBy(fn (Workstation $w) => $w->room ?? ''));
        } else {
            $workstations = $query->paginate(25)->withQueryString();
            $floors = null;
        }

        $locations = Location::orderBy('name')->get();
        $rooms = Workstation::whereNotNull('room')
            ->when($request->filled('location'), fn ($q) => $q->where('location_id', $request->location))
            ->distinct()
            ->orderBy('room')
            ->pluck('room');
        $auditDueCount = Workstation::whereDoesntHave('audits', fn ($q) => $q->where('created_at', '>=', now()->subDays(Workstation::AUDIT_INTERVAL_DAYS)))->count();
        $attentionCount = Workstation::needsAttention()->count();

        return view('workstations.index', compact('view', 'workstations', 'floors', 'locations', 'rooms', 'auditDueCount', 'attentionCount'));
    }

    /**
     * The filtered workstation list as a CSV, one row per desk with its equipment.
     */
    public function export(Request $request)
    {
        $this->authorizeAdmin();

        $workstations = $this->filteredQuery($request)->with('latestAudit.user')->get();

        return $this->csv('workstations', [
            'Code', 'Location', 'Room', 'Number', 'Occupant', 'Equipment Count', 'Equipment', 'Last Audit', 'Audited By', 'Notes',
        ], $workstations->map(fn (Workstation $w) => [
            $w->name,
            $w->location?->name,
            $w->room,
            $w->number,
            $w->occupant?->name,
            $w->assets->count(),
            $w->assets->map(fn (Asset $a) => "{$a->asset_tag} ({$a->type})")->implode(', '),
            $w->latestAudit?->created_at?->format('Y-m-d H:i'),
            $w->latestAudit?->user?->name,
            $w->notes,
        ]));
    }

    /**
     * Printable QR labels for every desk matching the list filters, e.g. a whole room. Scanning
     * a label opens the desk's page, where it can be audited.
     */
    public function labels(Request $request)
    {
        $this->authorizeAdmin();

        $query = $this->filteredQuery($request);
        $total = (clone $query)->count();
        $workstations = $query->limit(self::MAX_LABELS)->get();

        if ($workstations->isEmpty()) {
            return redirect()->route('workstations.index', $request->query())->with('error', 'No workstations match those filters, so there are no labels to print.');
        }

        return $this->labelSheet($workstations, $total > self::MAX_LABELS ? self::MAX_LABELS : null);
    }

    public function label(Workstation $workstation)
    {
        $this->authorizeAdmin();

        return $this->labelSheet(collect([$workstation->load('location')]));
    }

    public function create()
    {
        $this->authorizeAdmin();

        return view('workstations.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        if ($request->input('mode') === 'bulk') {
            return $this->storeBulk($request);
        }

        $validated = $this->validateWorkstation($request);
        $workstation = Workstation::create($validated);

        return redirect()->route('workstations.show', $workstation)->with('success', "Workstation {$workstation->name} created.");
    }

    /**
     * Create a numbered run of workstations in one room, e.g. room RA, 1 to 20 →
     * INFP1ARAWS01 … INFP1ARAWS20. Codes that already exist are skipped.
     */
    private function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'room' => self::ROOM_RULE,
            'from' => 'required|integer|min:1|max:999',
            'to' => 'required|integer|gte:from|max:999',
        ], ['room.regex' => 'The room may only contain letters and numbers, e.g. RA.']);

        $location = $this->locationWithCode($validated['location_id']);

        if ($validated['to'] - $validated['from'] + 1 > self::MAX_BULK) {
            return back()->withErrors(['to' => 'You can add at most ' . self::MAX_BULK . ' workstations at a time.'])->withInput();
        }

        $room = $validated['room'] ?? null;
        $numbers = collect(range($validated['from'], $validated['to']));
        $existing = Workstation::where('location_id', $location->id)
            ->whereIn('name', $numbers->map(fn ($n) => Workstation::buildCode($location->code, $room, $n)))
            ->pluck('number');
        $toCreate = $numbers->diff($existing);

        foreach ($toCreate as $number) {
            Workstation::create(['location_id' => $location->id, 'room' => $room, 'number' => $number]);
        }

        $message = $toCreate->count() . ' workstation(s) created.';
        if ($existing->isNotEmpty()) {
            $message .= ' Skipped ' . $existing->count() . ' that already existed.';
        }

        return redirect()->route('workstations.index', ['location' => $location->id])->with('success', $message);
    }

    public function show(Workstation $workstation)
    {
        $this->authorizeAdmin();

        $workstation->load(['location', 'occupant', 'assets.category', 'assets.assignedEmployee', 'latestAudit.user']);

        $movements = $workstation->movements()->with(['asset', 'user'])->paginate(15);
        $audits = $workstation->audits()->with('user')->limit(5)->get();

        // Fixed assets that could be placed here: anything not already at this desk and still in service.
        $placeableAssets = $this->assetsNotAt($workstation, ['retired', 'lost']);

        $workstationOptions = $this->workstationOptions();

        return view('workstations.show', compact('workstation', 'movements', 'audits', 'placeableAssets', 'workstationOptions'));
    }

    /**
     * Desk audit: check what is physically at the desk against what the records say.
     */
    public function audit(Workstation $workstation)
    {
        $this->authorizeAdmin();

        $workstation->load(['location', 'occupant', 'assets', 'latestAudit.user']);

        // Anything that might turn up at this desk. Lost assets are included: finding one is the point.
        $findableAssets = $this->assetsNotAt($workstation, ['retired']);

        return view('workstations.audit', compact('workstation', 'findableAssets'));
    }

    /**
     * Save a desk audit. Recorded equipment that wasn't ticked as present is taken off the desk
     * (status left alone: missing isn't the same as available); equipment found here that the
     * records put elsewhere is moved here. Every change is logged with an audit note.
     */
    public function storeAudit(Request $request, Workstation $workstation)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'present' => 'nullable|array',
            'present.*' => 'integer',
            'found' => 'nullable|array',
            'found.*' => 'integer|distinct|exists:assets,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $presentIds = collect($validated['present'] ?? [])->map(fn ($id) => (int) $id);
        $recorded = $workstation->assets()->get();
        $missing = $recorded->reject(fn (Asset $a) => $presentIds->contains($a->id))->values();

        $found = Asset::with('workstation')
            ->whereIn('id', $validated['found'] ?? [])
            ->where(fn ($q) => $q->whereNull('workstation_id')->orWhere('workstation_id', '!=', $workstation->id))
            ->get();

        if ($found->contains(fn (Asset $a) => !$a->isFixed())) {
            throw ValidationException::withMessages(['found' => 'Only desktops, monitors and speakerphones can be recorded at a workstation.']);
        }

        $snapshot = fn (Asset $a) => ['id' => $a->id, 'tag' => $a->asset_tag, 'name' => $a->name];
        $missingSnapshot = $missing->map($snapshot)->all();
        $foundSnapshot = $found->map(fn (Asset $a) => $snapshot($a) + ['from' => $a->workstation?->label])->all();

        $audit = DB::transaction(function () use ($workstation, $missing, $found, $missingSnapshot, $foundSnapshot, $recorded, $validated) {
            foreach ($missing as $asset) {
                $asset->moveToWorkstation(null, 'Not found during desk audit', syncStatus: false);
            }

            foreach ($found as $asset) {
                $asset->moveToWorkstation($workstation, 'Found here during desk audit');
            }

            return WorkstationAudit::create([
                'workstation_id' => $workstation->id,
                'user_id' => auth()->id(),
                'present_count' => $recorded->count() - $missing->count(),
                'missing' => $missingSnapshot,
                'found' => $foundSnapshot,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $message = "Audit of {$workstation->name} saved: {$audit->present_count} present";
        $message .= ', ' . count($missingSnapshot) . ' missing, ' . count($foundSnapshot) . ' found.';

        return redirect()->route('workstations.show', $workstation)->with('success', $message);
    }

    public function edit(Workstation $workstation)
    {
        $this->authorizeAdmin();

        return view('workstations.edit', ['workstation' => $workstation] + $this->formData($workstation));
    }

    public function update(Request $request, Workstation $workstation)
    {
        $this->authorizeAdmin();

        $validated = $this->validateWorkstation($request, $workstation);
        $workstation->update($validated);

        // The desk was re-filed under another office: its equipment goes with it.
        if ($workstation->wasChanged('location_id')) {
            $workstation->assets()->update(['location_id' => $workstation->location_id]);
        }

        return redirect()->route('workstations.show', $workstation)->with('success', "Workstation {$workstation->name} updated.");
    }

    public function destroy(Workstation $workstation)
    {
        $this->authorizeAdmin();

        $assetCount = $workstation->assets()->count();
        if ($assetCount > 0) {
            return back()->with('error', "Move or remove the {$assetCount} asset(s) at {$workstation->name} before deleting it.");
        }

        $workstation->delete();

        return redirect()->route('workstations.index')->with('success', 'Workstation deleted.');
    }

    /**
     * Place assets at this workstation, moving each from wherever it was. Takes one asset_id (the
     * dropdown) or a list of asset_ids (scanning the desk's equipment).
     */
    public function placeAsset(Request $request, Workstation $workstation)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'asset_id' => 'required_without:asset_ids|nullable|integer|exists:assets,id',
            'asset_ids' => 'required_without:asset_id|array|max:' . self::MAX_PLACE_AT_ONCE,
            'asset_ids.*' => 'integer|distinct|exists:assets,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $assets = Asset::with('workstation')
            ->whereIn('id', $validated['asset_ids'] ?? [$validated['asset_id']])
            ->orderBy('asset_tag')
            ->get();

        [$fixed, $portable] = $assets->partition(fn (Asset $asset) => $asset->isFixed());
        $placed = $fixed->filter(fn (Asset $asset) => $asset->moveToWorkstation($workstation, $validated['notes'] ?? null));

        if ($placed->isEmpty()) {
            return back()->with('error', $portable->isNotEmpty()
                ? 'Only desktops, monitors and speakerphones can be placed at a workstation.'
                : 'Nothing to place: ' . ($fixed->count() === 1 ? 'it is' : 'they are') . " already at {$workstation->name}.");
        }

        $tags = $placed->pluck('asset_tag')->join(', ');
        $message = $placed->count() === 1
            ? "{$tags} is now at {$workstation->name}."
            : "{$placed->count()} assets placed at {$workstation->name}: {$tags}.";

        if ($portable->isNotEmpty()) {
            $message .= ' Skipped ' . $portable->pluck('asset_tag')->join(', ') . ' (only desktops, monitors and speakerphones can be placed at a desk).';
        }

        return back()->with('success', $message);
    }

    /**
     * Company-wide log of every workstation move.
     */
    public function movements(Request $request)
    {
        $this->authorizeAdmin();

        $movements = $this->filteredMovementsQuery($request)->paginate(25)->withQueryString();
        $locations = Location::orderBy('name')->get();

        return view('workstations.movements', compact('movements', 'locations'));
    }

    public function exportMovements(Request $request)
    {
        $this->authorizeAdmin();

        $movements = $this->filteredMovementsQuery($request)->get();

        return $this->csv('workstation_movements', [
            'Date', 'Asset Tag', 'Asset Name', 'Type', 'Serial Number', 'From', 'To', 'Note', 'By',
        ], $movements->map(fn (AssetMovement $m) => [
            $m->created_at->format('Y-m-d H:i'),
            $m->asset?->asset_tag,
            $m->asset?->name,
            $m->asset?->type,
            $m->asset?->serial_number,
            $m->from_label ?? 'Storage',
            $m->to_label ?? 'Storage',
            $m->notes,
            $m->user?->name ?? 'System',
        ]));
    }

    private function filteredMovementsQuery(Request $request): Builder
    {
        $query = AssetMovement::with(['asset', 'user', 'fromWorkstation', 'toWorkstation'])->latest()->latest('id');

        if ($request->filled('search')) {
            $like = '%' . addcslashes(trim($request->search), '%_\\') . '%';
            $query->where(fn ($q) => $q->whereHas('asset', fn ($a) => $a->where('asset_tag', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('serial_number', 'like', $like))
                ->orWhere('from_label', 'like', $like)
                ->orWhere('to_label', 'like', $like));
        }

        if ($request->filled('location')) {
            $inLocation = fn ($w) => $w->where('location_id', $request->location);
            $query->where(fn ($q) => $q->whereHas('fromWorkstation', $inLocation)->orWhereHas('toWorkstation', $inLocation));
        }

        if ($request->filled('type')) {
            $query->whereHas('asset', fn ($a) => $a->where('type', $request->type));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        return $query;
    }

    /**
     * All workstations grouped by office name, for "move to" dropdowns.
     */
    public static function workstationOptions()
    {
        return Workstation::with(['location', 'occupant'])
            ->withCount('assets')
            ->get()
            ->sortBy(fn (Workstation $w) => [$w->location?->name, (string) $w->room, $w->number])
            ->groupBy(fn (Workstation $w) => $w->location?->name ?? 'No location');
    }

    /**
     * Workstations matching the list filters (location, room, search, equipment, audit), sorted
     * office → room → number. Shared by the list, the room view, the CSV export and the labels.
     */
    private function filteredQuery(Request $request): Builder
    {
        $query = Workstation::with(['location', 'occupant', 'assets.assignedEmployee', 'latestAudit'])
            ->select('workstations.*')
            ->join('locations', 'locations.id', '=', 'workstations.location_id');

        if ($request->filled('location')) {
            $query->where('workstations.location_id', $request->location);
        }

        if ($request->filled('room')) {
            $query->where('workstations.room', $request->room);
        }

        if ($request->filled('search')) {
            $like = '%' . addcslashes(trim($request->search), '%_\\') . '%';
            $query->where(fn ($q) => $q->where('workstations.name', 'like', $like)
                ->orWhereHas('occupant', fn ($o) => $o->where('name', 'like', $like))
                ->orWhereHas('assets', fn ($a) => $a->where('asset_tag', 'like', $like)->orWhere('name', 'like', $like)));
        }

        if ($request->occupancy === 'empty') {
            $query->doesntHave('assets');
        } elseif ($request->occupancy === 'equipped') {
            $query->has('assets');
        }

        if ($request->audit === 'due') {
            $query->whereDoesntHave('audits', fn ($q) => $q->where('created_at', '>=', now()->subDays(Workstation::AUDIT_INTERVAL_DAYS)));
        } elseif ($request->audit === 'never') {
            $query->doesntHave('audits');
        }

        if ($request->boolean('attention')) {
            $query->needsAttention();
        }

        return $query->orderBy('locations.name')
            ->orderBy('workstations.room')
            ->orderBy('workstations.number');
    }

    /**
     * Fixed assets not currently at $workstation, excluding the given statuses.
     */
    private function assetsNotAt(Workstation $workstation, array $excludeStatuses): Collection
    {
        return Asset::with('workstation')
            ->whereIn('type', Asset::FIXED_TYPES)
            ->whereNotIn('status', $excludeStatuses)
            ->where(fn ($q) => $q->whereNull('workstation_id')->orWhere('workstation_id', '!=', $workstation->id))
            ->orderBy('asset_tag')
            ->get();
    }

    private function labelSheet(Collection $workstations, ?int $truncatedAt = null)
    {
        $qrCodes = $workstations->mapWithKeys(fn (Workstation $w) => [
            $w->id => QrCode::size(200)->margin(0)->generate(route('workstations.show', $w)),
        ]);

        return view('workstations.labels', compact('workstations', 'qrCodes', 'truncatedAt'));
    }

    private function csv(string $name, array $header, Collection $rows)
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $h = fopen('php://output', 'w');
            fputcsv($h, $header);
            foreach ($rows as $row) {
                fputcsv($h, $row);
            }
            fclose($h);
        }, $name . '_' . now()->format('Y-m-d_His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Shared data for the create/edit forms, including the rooms already used per location so the
     * form can suggest them.
     */
    private function formData(?Workstation $workstation = null): array
    {
        $locations = Location::orderBy('name')->get();
        $employees = Employee::where('status', 'active')
            ->when($workstation?->occupant_id, fn ($q, $id) => $q->orWhere('id', $id))
            ->orderBy('name')
            ->get();
        $roomsByLocation = Workstation::whereNotNull('room')
            ->select('location_id', 'room')
            ->distinct()
            ->orderBy('room')
            ->get()
            ->groupBy('location_id')
            ->map(fn ($rows) => $rows->pluck('room')->values());

        return compact('locations', 'employees', 'roomsByLocation');
    }

    private function validateWorkstation(Request $request, ?Workstation $workstation = null): array
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'room' => self::ROOM_RULE,
            'number' => 'required|integer|min:1|max:999',
            'occupant_id' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string|max:1000',
        ], ['room.regex' => 'The room may only contain letters and numbers, e.g. RA.']);

        $location = $this->locationWithCode($validated['location_id']);
        $code = Workstation::buildCode($location->code, $validated['room'] ?? null, (int) $validated['number']);

        $taken = Workstation::where('location_id', $location->id)
            ->where('name', $code)
            ->when($workstation, fn ($q) => $q->whereKeyNot($workstation->id))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['number' => "{$code} already exists."]);
        }

        return $validated;
    }

    /**
     * Workstation codes are built from the location code, so the location must have one.
     */
    private function locationWithCode(int|string $locationId): Location
    {
        $location = Location::findOrFail($locationId);

        if (!$location->code) {
            throw ValidationException::withMessages([
                'location_id' => "Give {$location->name} a workstation code (e.g. P1A) on the Locations page first.",
            ]);
        }

        return $location;
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
