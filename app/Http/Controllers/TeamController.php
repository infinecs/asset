<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();
        $teams = Team::with('manager')->withCount('employees')->orderBy('name')->get();
        return view('teams.index', compact('teams'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        $managers = Employee::where('is_manager', true)->orderBy('name')->get();
        return view('teams.create', compact('managers'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name',
            'manager_id' => 'nullable|exists:employees,id',
        ]);

        Team::create($validated);
        return redirect()->route('teams.index')->with('success', 'Team created successfully.');
    }

    public function edit(Team $team)
    {
        $this->authorizeAdmin();
        $managers = Employee::where('is_manager', true)->orderBy('name')->get();
        return view('teams.edit', compact('team', 'managers'));
    }

    public function update(Request $request, Team $team)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id,
            'manager_id' => 'nullable|exists:employees,id',
        ]);

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
