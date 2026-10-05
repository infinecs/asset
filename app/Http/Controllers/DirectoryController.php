<?php

namespace App\Http\Controllers;

use App\Models\DirectoryContact;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class DirectoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->search);
        $locationId = $request->filled('location') ? (int) $request->location : null;
        $selectedLocation = $locationId ? Location::find($locationId) : null;

        // Users have no tracked location, so they're excluded entirely when filtering by location.
        $userRows = $locationId ? collect() : User::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->get()
            ->map(fn (User $user) => [
                'source' => 'user',
                'name' => $user->name,
                'email' => $user->email,
                'meta' => $user->department ?? '-',
                'phone' => $user->phone ?? '-',
                'badge_label' => $user->roleLabel(),
                'badge_class' => $user->role === 'admin' || $user->role === 'superadmin' ? 'danger' : 'warning',
                'is_you' => $user->id === Auth::id(),
                'contact_id' => null,
            ]);

        $employeeRows = Employee::query()
            ->where('status', 'active')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('work_location', 'like', "%{$search}%");
                });
            })
            ->when($selectedLocation, fn ($q) => $q->where('work_location', 'like', "%{$selectedLocation->name}%"))
            ->get()
            ->map(fn (Employee $employee) => [
                'source' => 'employee',
                'name' => $employee->name,
                'email' => $employee->email ?? '-',
                'meta' => $employee->work_location ?? '-',
                'phone' => '-',
                'badge_label' => 'Employee',
                'badge_class' => 'secondary',
                'is_you' => false,
                'contact_id' => null,
            ]);

        $seenEmails = [];
        $entries = $userRows->concat($employeeRows)
            ->reject(function ($row) use (&$seenEmails) {
                $email = $row['email'] && $row['email'] !== '-' ? strtolower(trim($row['email'])) : null;

                if ($email === null) {
                    return false;
                }

                if (isset($seenEmails[$email])) {
                    return true;
                }

                $seenEmails[$email] = true;

                return false;
            })
            ->sortBy(fn ($row) => strtolower($row['name']))
            ->values();

        $perPage = 15;
        $page = (int) $request->get('page', 1);

        $directory = new LengthAwarePaginator(
            $entries->slice(($page - 1) * $perPage, $perPage)->values(),
            $entries->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $contacts = DirectoryContact::with('location')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->orderBy('name')
            ->get();

        $locations = Location::orderBy('name')->get();

        return view('directory.index', compact('directory', 'contacts', 'locations'));
    }
}
