<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        $locations = Location::withCount('assets')->orderBy('name')->get();
        return view('locations.index', compact('locations'));
    }

    public function create()
    {
        $this->authorizeAdmin();

        return view('locations.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'building' => 'nullable|string|max:255',
            'floor' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        Location::create($validated);
        return redirect()->route('locations.index')->with('success', 'Location created successfully.');
    }

    public function edit(Location $location)
    {
        $this->authorizeAdmin();

        return view('locations.edit', compact('location'));
    }

    public function update(Request $request, Location $location)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'building' => 'nullable|string|max:255',
            'floor' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        $location->update($validated);
        return redirect()->route('locations.index')->with('success', 'Location updated successfully.');
    }

    public function destroy(Location $location)
    {
        $this->authorizeAdmin();

        $location->delete();
        return redirect()->route('locations.index')->with('success', 'Location deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:locations,id',
        ]);

        $deleted = Location::whereIn('id', $validated['ids'])->delete();

        return redirect()->back()->with('success', $deleted . ' location(s) deleted successfully.');
    }

    public function destroyAll()
    {
        $this->authorizeAdmin();

        $deleted = Location::query()->delete();

        return redirect()->back()->with('success', $deleted . ' location(s) deleted successfully.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
