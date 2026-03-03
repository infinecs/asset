<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\RequestTicket;
use App\Models\User;
use App\Models\Category;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_assets' => Asset::count(),
            'available_assets' => Asset::where('status', 'available')->count(),
            'in_use_assets' => Asset::where('status', 'in_use')->count(),
            'maintenance_assets' => Asset::where('status', 'under_maintenance')->count(),
            'open_tickets' => RequestTicket::whereIn('status', ['open', 'in_progress'])->count(),
            'my_tickets' => RequestTicket::where('requested_by', auth()->id())->whereIn('status', ['open', 'in_progress'])->count(),
            'total_users' => User::count(),
        ];

        $recentTickets = RequestTicket::with(['requester', 'asset'])
            ->latest()
            ->take(5)
            ->get();

        $recentAssets = Asset::with(['category', 'assignedUser'])
            ->latest()
            ->take(5)
            ->get();

        $assetsByCategory = Category::withCount('assets')
            ->get()
            ->filter(fn($c) => $c->assets_count > 0)
            ->values();

        $warrantyExpiring = Asset::whereNotNull('warranty_expiry')
            ->where('warranty_expiry', '>', now())
            ->where('warranty_expiry', '<=', now()->addDays(30))
            ->with(['category'])
            ->get();

        return view('dashboard', compact(
            'stats', 'recentTickets', 'recentAssets', 'assetsByCategory', 'warrantyExpiring'
        ));
    }
}
