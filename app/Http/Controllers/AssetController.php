<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    private function filteredAssetsQuery(Request $request)
    {
        $query = Asset::with(['brand', 'category', 'location', 'assignedEmployee']);

        if (!auth()->user()->isAdmin()) {
            $query->where('status', 'available');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_tag', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                                    ->orWhere('model', 'like', "%{$search}%")
                                    ->orWhereHas('brand', function ($brandQuery) use ($search) {
                                            $brandQuery->where('name', 'like', "%{$search}%");
                                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('location')) {
            $query->where('location_id', $request->location);
        }

        if ($request->filled('cpu')) {
            $query->where('cpu', $request->cpu);
        }

        if ($request->filled('ram')) {
            $query->where('ram', $request->ram);
        }

        if ($request->filled('storage')) {
            $query->where('storage', $request->storage);
        }

        if ($request->filled('display')) {
            $query->where('display', $request->display);
        }

        $sortable = ['asset_tag', 'name', 'category', 'location', 'assigned_to', 'status', 'last_seen_at'];
        $sort = $request->get('sort');
        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->select('assets.*');

            switch ($sort) {
                case 'category':
                    $query->leftJoin('categories', 'categories.id', '=', 'assets.category_id')
                          ->orderBy('categories.name', $direction);
                    break;
                case 'location':
                    $query->leftJoin('locations', 'locations.id', '=', 'assets.location_id')
                          ->orderBy('locations.name', $direction);
                    break;
                case 'assigned_to':
                    $query->leftJoin('employees', 'employees.id', '=', 'assets.assigned_to')
                          ->orderBy('employees.name', $direction);
                    break;
                default:
                    $query->orderBy('assets.' . $sort, $direction);
            }
        } else {
            $query->orderByRaw('CAST(SUBSTRING(asset_tag, 7) AS UNSIGNED) DESC');
        }

        return $query;
    }

    public function index(Request $request)
    {
        $assets = $this->filteredAssetsQuery($request)->paginate(15)->withQueryString();
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        $filterCpus     = Asset::whereNotNull('cpu')->distinct()->orderBy('cpu')->pluck('cpu');
        $filterRams     = Asset::whereNotNull('ram')->distinct()->orderBy('ram')->pluck('ram');
        $filterStorages = Asset::whereNotNull('storage')->distinct()->orderBy('storage')->pluck('storage');
        $filterDisplays = Asset::whereNotNull('display')->distinct()->orderBy('display')->pluck('display');

        return view('assets.index', compact('assets', 'brands', 'categories', 'locations', 'filterCpus', 'filterRams', 'filterStorages', 'filterDisplays'));
    }

    public function export(Request $request)
    {
        $assets = $this->filteredAssetsQuery($request)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="assets_' . now()->format('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($assets) {
            $h = fopen('php://output', 'w');
            fputcsv($h, [
                'Asset Tag', 'Name', 'Type', 'Brand', 'Model', 'Serial Number',
                'Category', 'Location', 'Assigned To', 'Status',
                'CPU', 'RAM', 'Storage', 'Display',
                'Purchase Date', 'Purchase Cost', 'Warranty Expiry', 'Last Seen',
            ]);

            foreach ($assets as $asset) {
                fputcsv($h, [
                    $asset->asset_tag,
                    $asset->name,
                    $asset->type,
                    $asset->brand_label,
                    $asset->model,
                    $asset->serial_number,
                    $asset->category?->name,
                    $asset->location?->name,
                    $asset->assignedEmployee?->name,
                    $asset->status_label,
                    $asset->cpu,
                    $asset->ram,
                    $asset->storage,
                    $asset->display,
                    $asset->purchase_date?->format('Y-m-d'),
                    $asset->purchase_cost,
                    $asset->warranty_expiry?->format('Y-m-d'),
                    $asset->last_seen_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        return view('assets.create', compact('brands', 'categories', 'locations'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $typePrefix = match($request->input('type')) {
            'desktop'    => 'ISSB-D',
            'smartphone' => 'ISSB-S',
            'tablet'     => 'ISSB-T',
            default      => 'ISSB-L',
        };
        $request->merge(['asset_tag' => $typePrefix . trim($request->input('asset_tag_suffix', ''))]);

        $validated = $request->validate([
            'type' => 'nullable|in:laptop,desktop,smartphone,tablet',
            'asset_tag' => 'required|string|max:255|unique:assets,asset_tag',
            'name' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'status' => 'required|in:available,in_use,under_maintenance,retired,lost',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'signed_document' => 'nullable|mimes:pdf,doc,docx|max:10240',
            'cpu' => 'nullable|string|max:100',
            'ram' => 'nullable|string|max:50',
            'storage' => 'nullable|string|max:100',
            'display' => 'nullable|string|max:50',
        ]);
        $validated['brand'] = null;
        if (!empty($validated['brand_id'])) {
            $validated['brand'] = Brand::whereKey($validated['brand_id'])->value('name');
        }

        $validated['assigned_to'] = null;

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('assets/photos', 'public');
        }

        if ($request->hasFile('signed_document')) {
            $validated['signed_document_path'] = $request->file('signed_document')->store('assets/documents', 'public');
        }

        $asset = Asset::create($validated);

        AssetHistory::create([
            'asset_id' => $asset->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'notes' => 'Asset created',
        ]);

        return redirect()->route('assets.show', $asset)->with('success', 'Asset created successfully.');
    }

    public function show(Asset $asset)
    {
        if (!auth()->user()->isAdmin() && $asset->status !== 'available') {
            abort(403);
        }

        $asset->load([
            'brand',
            'category',
            'location',
            'assignedEmployee',
            'histories.user',
        ]);

        $relationNameMaps = [
            'category_id' => Category::pluck('name', 'id'),
            'location_id' => Location::pluck('name', 'id'),
            'assigned_to' => Employee::pluck('name', 'id'),
            'brand_id' => Brand::pluck('name', 'id'),
        ];
        $dateFields = ['purchase_date', 'warranty_expiry', 'last_seen_at'];

        $activityTimeline = $asset->histories->map(function ($history) use ($relationNameMaps, $dateFields) {
            $changes = collect($history->changes ?? [])
                ->reject(fn ($change, $field) => in_array($field, ['photo', 'signed_document'], true))
                ->map(function ($change, $field) use ($relationNameMaps, $dateFields) {
                    $old = $change['old'] ?? null;
                    $new = $change['new'] ?? null;

                    if (is_array($old)) {
                        $old = !empty($old) ? json_encode($old) : null;
                    }
                    if (is_array($new)) {
                        $new = !empty($new) ? json_encode($new) : null;
                    }

                    if (isset($relationNameMaps[$field])) {
                        $old = $old ? ($relationNameMaps[$field][$old] ?? $old) : null;
                        $new = $new ? ($relationNameMaps[$field][$new] ?? $new) : null;
                    } elseif ($field === 'last_seen_at') {
                        $old = $old ? \Carbon\Carbon::parse($old)->format('M d, Y h:i A') : null;
                        $new = $new ? \Carbon\Carbon::parse($new)->format('M d, Y h:i A') : null;
                    } elseif (in_array($field, $dateFields, true)) {
                        $old = $old ? \Carbon\Carbon::parse($old)->format('M d, Y') : null;
                        $new = $new ? \Carbon\Carbon::parse($new)->format('M d, Y') : null;
                    } elseif (in_array($field, ['photo_path', 'signed_document_path'], true)) {
                        $old = $old ? basename($old) : null;
                        $new = $new ? basename($new) : null;
                    }

                    $labels = [
                        'category_id' => 'Category',
                        'location_id' => 'Location',
                        'brand_id' => 'Brand',
                        'assigned_to' => 'Assigned To',
                        'photo_path' => 'Photo',
                        'signed_document_path' => 'Signed Document',
                    ];

                    return [
                        'label' => $labels[$field] ?? ucwords(str_replace('_', ' ', $field)),
                        'old' => $old ?? '—',
                        'new' => $new ?? '—',
                    ];
                })->values();

            return (object) [
                'type' => 'asset_history',
                'at' => $history->created_at,
                'title' => ucwords(str_replace('_', ' ', $history->action)),
                'by' => $history->user?->name ?? 'System',
                'notes' => $history->notes,
                'changes' => $changes,
                'icon' => $history->action == 'created' ? 'plus' : ($history->action == 'status_changed' ? 'arrow-repeat' : 'pencil'),
            ];
        })->sortByDesc('at')->take(15)->values();

        return view('assets.show', compact('asset', 'activityTimeline'));
    }

    public function edit(Asset $asset)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $employees = Employee::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        return view('assets.edit', compact('asset', 'brands', 'categories', 'locations', 'employees', 'departments'));
    }

    public function update(Request $request, Asset $asset)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $typePrefix = match($request->input('type')) {
            'desktop'    => 'ISSB-D',
            'smartphone' => 'ISSB-S',
            'tablet'     => 'ISSB-T',
            default      => 'ISSB-L',
        };
        $request->merge(['asset_tag' => $typePrefix . trim($request->input('asset_tag_suffix', ''))]);

        $validated = $request->validate([
            'type' => 'nullable|in:laptop,desktop,smartphone,tablet',
            'asset_tag' => 'required|string|max:255|unique:assets,asset_tag,' . $asset->id,
            'name' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'assigned_to' => 'nullable|exists:employees,id',
            'last_seen_at' => 'nullable|date_format:Y-m-d\TH:i',
            'status' => 'required|in:available,in_use,under_maintenance,retired,lost',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'signed_document' => 'nullable|mimes:pdf,doc,docx|max:10240',
            'cpu' => 'nullable|string|max:100',
            'ram' => 'nullable|string|max:50',
            'storage' => 'nullable|string|max:100',
            'display' => 'nullable|string|max:50',
        ]);

        $changes = [];

        $validated['brand'] = null;
        if (!empty($validated['brand_id'])) {
            $validated['brand'] = Brand::whereKey($validated['brand_id'])->value('name');
        }

        if ($request->hasFile('photo')) {
            if ($asset->photo_path && Storage::disk('public')->exists($asset->photo_path)) {
                Storage::disk('public')->delete($asset->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('assets/photos', 'public');
        }

        if ($request->hasFile('signed_document')) {
            if ($asset->signed_document_path && Storage::disk('public')->exists($asset->signed_document_path)) {
                Storage::disk('public')->delete($asset->signed_document_path);
            }
            $validated['signed_document_path'] = $request->file('signed_document')->store('assets/documents', 'public');
        }

        foreach (collect($validated)->except(['photo', 'signed_document']) as $key => $value) {
            if ($asset->$key != $value) {
                $changes[$key] = ['old' => $asset->$key, 'new' => $value];
            }
        }

        $asset->update($validated);

        if (!empty($changes)) {
            AssetHistory::create([
                'asset_id' => $asset->id,
                'user_id' => auth()->id(),
                'action' => 'updated',
                'notes' => 'Asset information updated',
                'changes' => $changes,
            ]);
        }

        return redirect()->route('assets.show', $asset)->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        if ($asset->photo_path && Storage::disk('public')->exists($asset->photo_path)) {
            Storage::disk('public')->delete($asset->photo_path);
        }

        if ($asset->signed_document_path && Storage::disk('public')->exists($asset->signed_document_path)) {
            Storage::disk('public')->delete($asset->signed_document_path);
        }

        $asset->delete();
        return redirect()->route('assets.index')->with('success', 'Asset deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:assets,id',
        ]);

        $deleted = Asset::whereIn('id', $validated['ids'])->delete();

        return redirect()->back()->with('success', $deleted . ' asset(s) deleted successfully.');
    }

    public function destroyAll()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $deleted = Asset::query()->delete();

        return redirect()->back()->with('success', $deleted . ' asset(s) deleted successfully.');
    }

    public function updateStatus(Request $request, Asset $asset)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:available,in_use,under_maintenance,retired,lost',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $asset->status;
        $asset->update([
            'status' => $validated['status'],
            'last_seen_at' => now(),
        ]);

        AssetHistory::create([
            'asset_id' => $asset->id,
            'user_id' => auth()->id(),
            'action' => 'status_changed',
            'notes' => $validated['notes'] ?? 'Status updated',
            'changes' => ['status' => ['old' => $oldStatus, 'new' => $validated['status']]],
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'status' => $asset->fresh()->status_label]);
        }

        return redirect()->route('assets.show', $asset)->with('success', 'Asset status updated.');
    }

    public function live()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $assets = Asset::with(['brand', 'category', 'location', 'assignedEmployee'])
            ->when(!auth()->user()->isAdmin(), function ($query) {
                $query->where('status', 'available');
            })
            ->orderBy('last_seen_at', 'desc')
            ->paginate(20);

        $statusCounts = Asset::when(!auth()->user()->isAdmin(), function ($query) {
                $query->where('status', 'available');
            })
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('assets.live', compact('assets', 'statusCounts'));
    }

    private function generateAssetTag(): string
    {
        $prefix = 'AST';
        $year = now()->format('y');
        $count = Asset::count() + 1;
        return $prefix . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
