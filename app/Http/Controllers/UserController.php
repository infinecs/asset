<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function settings()
    {
        $user = auth()->user();
        return view('users.edit', [
            'user' => $user,
            'isSettings' => true,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'change_password' => 'nullable|boolean',
        ]);

        if ($request->boolean('change_password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $user->fill($validated);
        $user->save();

        return redirect()->route('settings.edit')->with('success', 'Settings updated successfully.');
    }

    public function index()
    {
        $this->authorizeAdmin();

        $users = User::withCount(['assets', 'requestTickets'])->orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $this->authorizeAdmin();

        $departments = Department::orderBy('name')->get();
        return view('users.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,staff',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        User::create($validated);
        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $this->authorizeAdmin();

        $user->load(['assets.category', 'requestTickets']);
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorizeAdmin();

        $departments = Department::orderBy('name')->get();
        return view('users.edit', compact('user', 'departments'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,staff',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'change_password' => 'nullable|boolean',
        ]);

        if ($request->boolean('change_password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $user->fill($validated);
        $user->role = $validated['role'];
        $user->save();
        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorizeAdmin();

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
