<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::with(['brand', 'category', 'location', 'assignedUser']);

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

        $assets = $query->latest()->paginate(15)->withQueryString();
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        return view('assets.index', compact('assets', 'brands', 'categories', 'locations'));
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

        $validated = $request->validate([
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
        ]);

        $validated['asset_tag'] = $this->generateAssetTag();
        $validated['brand'] = null;
        if (!empty($validated['brand_id'])) {
            $validated['brand'] = Brand::whereKey($validated['brand_id'])->value('name');
        }

        $validated['assigned_to'] = null;

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('assets/photos', 'public');
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
            'assignedUser',
            'histories.user',
            'requestTickets.requester',
            'leases.lessee',
            'leases.returnedBy',
        ]);

        $historyActivities = $asset->histories->map(function ($history) {
            return (object) [
                'type' => 'asset_history',
                'at' => $history->created_at,
                'title' => ucwords(str_replace('_', ' ', $history->action)),
                'by' => $history->user?->name ?? 'System',
                'notes' => $history->notes,
                'icon' => $history->action == 'created' ? 'plus' : ($history->action == 'status_changed' ? 'arrow-repeat' : 'pencil'),
            ];
        });

        $leaseActivities = $asset->leases->flatMap(function ($lease) {
            $events = [];

            if ($lease->signed_at) {
                $events[] = (object) [
                    'type' => 'lease_signed',
                    'at' => $lease->signed_at,
                    'title' => 'Leased to ' . ($lease->lessee?->name ?? 'User'),
                    'by' => $lease->signed_name ?: ($lease->lessee?->name ?? 'User'),
                    'notes' => 'Lease #' . $lease->lease_number . ' signed',
                    'icon' => 'pen',
                ];
            }

            if ($lease->returned_at) {
                $events[] = (object) [
                    'type' => 'lease_returned',
                    'at' => $lease->returned_at,
                    'title' => 'Asset returned',
                    'by' => $lease->returnedBy?->name ?? 'System',
                    'notes' => $lease->returned_notes ?: ('Lease #' . $lease->lease_number . ' marked as returned'),
                    'icon' => 'box-arrow-in-left',
                ];
            }

            return $events;
        });

        $activityTimeline = $historyActivities
            ->concat($leaseActivities)
            ->sortByDesc('at')
            ->take(15)
            ->values();

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
        return view('assets.edit', compact('asset', 'brands', 'categories', 'locations'));
    }

    public function update(Request $request, Asset $asset)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
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
        ]);

        $changes = [];

        $validated['brand'] = null;
        if (!empty($validated['brand_id'])) {
            $validated['brand'] = Brand::whereKey($validated['brand_id'])->value('name');
        }

        $validated['assigned_to'] = $asset->leases()
            ->whereNotNull('signed_at')
            ->whereNull('returned_at')
            ->latest('signed_at')
            ->value('lessee_id');

        if ($request->hasFile('photo')) {
            if ($asset->photo_path && Storage::disk('public')->exists($asset->photo_path)) {
                Storage::disk('public')->delete($asset->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('assets/photos', 'public');
        }

        foreach ($validated as $key => $value) {
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

        $assets = Asset::with(['brand', 'category', 'location', 'assignedUser'])
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
