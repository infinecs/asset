<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();

        $locations = Location::withCount(['assets', 'workstations'])->orderBy('name')->get();
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
            ...$this->codeRules($request),
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
            ...$this->codeRules($request, $location),
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

        if ($location->workstations()->exists()) {
            return redirect()->route('locations.index')->with('error', "Delete or move the workstations in {$location->name} before deleting it.");
        }

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

        return $this->deleteLocations(Location::whereIn('id', $validated['ids']));
    }

    public function destroyAll()
    {
        $this->authorizeAdmin();

        return $this->deleteLocations(Location::query());
    }

    /**
     * Delete the given locations, keeping any that still have workstations.
     */
    private function deleteLocations($query)
    {
        $kept = (clone $query)->has('workstations')->count();
        $deleted = $query->doesntHave('workstations')->delete();

        $message = $deleted . ' location(s) deleted successfully.';
        if ($kept > 0) {
            $message .= " {$kept} location(s) with workstations were kept.";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * The location's part of a workstation code, e.g. "P1A" in INFP1ARAWS01. Stored upper-case.
     */
    private function codeRules(Request $request, ?Location $location = null): array
    {
        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper(trim($request->input('code')))]);
        }

        return [
            'code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/', Rule::unique('locations', 'code')->ignore($location)],
        ];
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
