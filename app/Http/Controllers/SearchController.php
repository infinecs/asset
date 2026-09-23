<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Team;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->input('q', ''));

        $results = [
            'employees' => collect(),
            'assets' => collect(),
            'teams' => collect(),
            'roles' => collect(),
        ];

        if ($query !== '') {
            $results['employees'] = Employee::with(['role', 'team'])
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('id_number', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->orderBy('name')
                ->limit(10)
                ->get();

            $assetsQuery = Asset::with('category')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('asset_tag', 'like', "%{$query}%")
                        ->orWhere('serial_number', 'like', "%{$query}%");
                });
            if (!auth()->user()->isAdmin()) {
                $assetsQuery->where('status', 'available');
            }
            $results['assets'] = $assetsQuery->orderBy('name')->limit(10)->get();

            if (auth()->user()->isAdmin()) {
                $results['teams'] = Team::where('name', 'like', "%{$query}%")->orderBy('name')->limit(10)->get();
                $results['roles'] = Role::where('name', 'like', "%{$query}%")->orderBy('name')->limit(10)->get();
            }
        }

        return view('search.index', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
