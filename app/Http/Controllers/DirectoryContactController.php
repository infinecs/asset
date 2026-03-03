<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DirectoryContact;
use App\Models\Location;
use Illuminate\Http\Request;

class DirectoryContactController extends Controller
{
    public function create()
    {
        $this->authorizeAdmin();

        $departments = Department::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        return view('directory_contacts.create', compact('departments', 'locations'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'department' => 'nullable|exists:departments,name',
            'phone' => 'nullable|string|max:50',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        DirectoryContact::create($validated);

        return redirect()->route('directory.index')->with('success', 'Directory contact added successfully.');
    }

    public function edit(DirectoryContact $directory_contact)
    {
        $this->authorizeAdmin();

        $departments = Department::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        return view('directory_contacts.edit', [
            'contact' => $directory_contact,
            'departments' => $departments,
            'locations' => $locations,
        ]);
    }

    public function update(Request $request, DirectoryContact $directory_contact)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'department' => 'nullable|exists:departments,name',
            'phone' => 'nullable|string|max:50',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        $directory_contact->update($validated);

        return redirect()->route('directory.index')->with('success', 'Directory contact updated successfully.');
    }

    public function destroy(DirectoryContact $directory_contact)
    {
        $this->authorizeAdmin();

        $directory_contact->delete();

        return redirect()->route('directory.index')->with('success', 'Directory contact deleted.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
