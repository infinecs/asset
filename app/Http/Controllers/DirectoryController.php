<?php

namespace App\Http\Controllers;

use App\Models\DirectoryContact;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;

class DirectoryController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->orderBy('name');
        $contactQuery = DirectoryContact::with('location')->orderBy('name');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });

            $contactQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('location')) {
            $query->whereHas('assets', function ($assetQuery) use ($request) {
                $assetQuery->where('location_id', $request->location);
            });

            $contactQuery->where('location_id', $request->location);
        }

        $users = $query->paginate(20)->withQueryString();
        $contacts = $contactQuery->get();
        $locations = Location::orderBy('name')->get();

        return view('directory.index', compact('users', 'contacts', 'locations'));
    }
}