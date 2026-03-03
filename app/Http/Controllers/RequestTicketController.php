<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use App\Models\RequestTicket;
use App\Models\User;
use Illuminate\Http\Request;

class RequestTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = RequestTicket::with(['requester', 'asset', 'assignedStaff']);

        if (!auth()->user()->isStaff()) {
            $query->where('requested_by', auth()->id());
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    public function create(Request $request)
    {
        $assets = Asset::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $selectedAsset = $request->filled('asset_id') ? Asset::find($request->asset_id) : null;
        return view('tickets.create', compact('assets', 'categories', 'selectedAsset'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'nullable|exists:assets,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:new_asset,repair,replacement,return,transfer',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        $validated['requested_by'] = auth()->id();
        $validated['ticket_number'] = RequestTicket::generateTicketNumber();
        $validated['status'] = 'open';

        $ticket = RequestTicket::create($validated);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket submitted successfully. Ticket #' . $ticket->ticket_number);
    }

    public function show(RequestTicket $ticket)
    {
        if (!auth()->user()->isStaff() && $ticket->requested_by !== auth()->id()) {
            abort(403);
        }
        $ticket->load(['requester', 'asset.category', 'assignedStaff', 'category']);
        $staffUsers = User::where('role', 'staff')->orWhere('role', 'admin')->orderBy('name')->get();
        return view('tickets.show', compact('ticket', 'staffUsers'));
    }

    public function edit(RequestTicket $ticket)
    {
        if (!auth()->user()->isStaff()) {
            abort(403);
        }
        $assets = Asset::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $staffUsers = User::where('role', 'staff')->orWhere('role', 'admin')->orderBy('name')->get();
        return view('tickets.edit', compact('ticket', 'assets', 'categories', 'staffUsers'));
    }

    public function update(Request $request, RequestTicket $ticket)
    {
        if (!auth()->user()->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'asset_id' => 'nullable|exists:assets,id',
            'category_id' => 'nullable|exists:categories,id',
            'type' => 'required|in:new_asset,repair,replacement,return,transfer',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:open,in_progress,resolved,closed,rejected',
            'assigned_to' => 'nullable|exists:users,id',
            'resolution_notes' => 'nullable|string',
        ]);

        if (in_array($validated['status'], ['resolved', 'closed']) && !$ticket->resolved_at) {
            $validated['resolved_at'] = now();
        }

        $ticket->update($validated);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket updated successfully.');
    }

    public function destroy(RequestTicket $ticket)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }
        $ticket->delete();
        return redirect()->route('tickets.index')->with('success', 'Ticket deleted.');
    }

    public function updateStatus(Request $request, RequestTicket $ticket)
    {
        if (!auth()->user()->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed,rejected',
            'resolution_notes' => 'nullable|string',
        ]);

        if (in_array($validated['status'], ['resolved', 'closed']) && !$ticket->resolved_at) {
            $validated['resolved_at'] = now();
        }

        $ticket->update($validated);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket status updated.');
    }
}
