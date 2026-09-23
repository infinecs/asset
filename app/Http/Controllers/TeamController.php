<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();
        $teams = Team::with(['manager', 'client'])->withCount('employees')->orderBy('name')->get();
        return view('teams.index', compact('teams'));
    }

    public function show(Team $team)
    {
        $this->authorizeAdmin();
        $team->load(['manager.role', 'client']);
        $employees = $team->employees()->with('role')->orderBy('name')->get();

        $memberIds = $employees->pluck('id');
        if ($team->manager_id) {
            $memberIds->push($team->manager_id);
        }
        $assets = Asset::whereIn('assigned_to', $memberIds)
            ->with(['category', 'assignedEmployee'])
            ->orderBy('name')
            ->get();

        return view('teams.show', compact('team', 'employees', 'assets'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        $managers = Employee::where('is_manager', true)->orderBy('name')->get();
        $clients = Client::orderBy('name')->get();
        return view('teams.create', compact('managers', 'clients'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            'manager_id' => 'nullable|exists:employees,id',
            'type' => 'required|in:internal,client_placement',
            'client_id' => 'required_if:type,client_placement|nullable|exists:clients,id',
        ]);
        // A client only makes sense for a client-placement team.
        $validated['client_id'] = $validated['type'] === 'client_placement' ? $validated['client_id'] : null;

        Team::create($validated);
        return redirect()->route('teams.index')->with('success', 'Team created successfully.');
    }

    public function edit(Team $team)
    {
        $this->authorizeAdmin();
        $managers = Employee::where('is_manager', true)->orderBy('name')->get();
        $clients = Client::orderBy('name')->get();
        return view('teams.edit', compact('team', 'managers', 'clients'));
    }

    public function update(Request $request, Team $team)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id,
            'manager_id' => 'nullable|exists:employees,id',
            'type' => 'required|in:internal,client_placement',
            'client_id' => 'required_if:type,client_placement|nullable|exists:clients,id',
        ]);
        $validated['client_id'] = $validated['type'] === 'client_placement' ? $validated['client_id'] : null;

        $team->update($validated);
        return redirect()->route('teams.index')->with('success', 'Team updated successfully.');
    }

    public function destroy(Team $team)
    {
        $this->authorizeAdmin();

        $team->delete();
        return redirect()->route('teams.index')->with('success', 'Team deleted.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
