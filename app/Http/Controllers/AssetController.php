<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::with(['category', 'location', 'assignedUser']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_tag', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
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
        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();

        return view('assets.index', compact('assets', 'categories', 'locations'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        return view('assets.create', compact('categories', 'locations', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:available,in_use,under_maintenance,retired,lost',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['asset_tag'] = $this->generateAssetTag();

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
        $asset->load(['category', 'location', 'assignedUser', 'histories.user', 'requestTickets.requester']);
        return view('assets.show', compact('asset'));
    }

    public function edit(Asset $asset)
    {
        $categories = Category::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        return view('assets.edit', compact('asset', 'categories', 'locations', 'users'));
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'location_id' => 'nullable|exists:locations,id',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'required|in:available,in_use,under_maintenance,retired,lost',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $changes = [];
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
        $asset->delete();
        return redirect()->route('assets.index')->with('success', 'Asset deleted successfully.');
    }

    public function updateStatus(Request $request, Asset $asset)
    {
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
        $assets = Asset::with(['category', 'location', 'assignedUser'])
            ->orderBy('last_seen_at', 'desc')
            ->paginate(20);

        $statusCounts = Asset::selectRaw('status, count(*) as count')
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
