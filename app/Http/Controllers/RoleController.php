<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $query = Role::withCount('employees');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where('name', 'like', '%' . $search . '%');
        }

        if ($request->filled('type')) {
            $query->where('is_top_level', $request->type === 'top_level');
        }

        if ($request->filled('employees')) {
            if ($request->employees === 'assigned') {
                $query->has('employees');
            } elseif ($request->employees === 'unassigned') {
                $query->doesntHave('employees');
            }
        }

        $roles = $query->orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
        ]);
        $validated['is_top_level'] = $request->boolean('is_top_level');

        Role::create($validated);
        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $this->authorizeAdmin();
        return view('roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        ]);
        $validated['is_top_level'] = $request->boolean('is_top_level');

        $role->update($validated);
        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $this->authorizeAdmin();

        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
