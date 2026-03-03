<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\RequestTicket;
use App\Models\User;
use App\Models\Category;
use App\Models\Task;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $pendingTaskAlert = null;

        if (auth()->user()->isAdmin()) {
            $today = now('Asia/Kuala_Lumpur');
            $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $weekStart->copy()->addDays(4);

            $pendingTasks = Task::where('user_id', auth()->id())
                ->where('is_completed', false)
                ->whereBetween('task_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->orderBy('task_date')
                ->orderBy('category')
                ->orderBy('title')
                ->get();

            $pendingTaskAlert = [
                'count' => $pendingTasks->count(),
                'weekNumber' => $weekStart->isoWeek,
                'weekYear' => $weekStart->isoWeekYear,
                'weekStart' => $weekStart,
                'weekEnd' => $weekEnd,
                'tasks' => $pendingTasks->take(5),
            ];
        }

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
            'stats', 'recentTickets', 'recentAssets', 'assetsByCategory', 'warrantyExpiring', 'pendingTaskAlert'
        ));
    }
}
