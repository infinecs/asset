<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use Illuminate\Http\Request;

class LeaseController extends Controller
{
    public function index()
    {
        $query = Lease::with(['asset', 'lessee', 'issuer', 'returnedBy'])->latest();

        if (!auth()->user()->isStaff()) {
            $query->where('lessee_id', auth()->id());
        }

        $leases = $query->paginate(15);

        return view('leases.index', compact('leases'));
    }

    public function create()
    {
        if (!auth()->user()->isStaff()) {
            abort(403);
        }

        $assets = Asset::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('leases.create', compact('assets', 'users'));
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'lessee_id' => 'required|exists:users,id',
            'lease_start' => 'nullable|date',
            'lease_end' => 'nullable|date|after_or_equal:lease_start',
            'terms' => 'nullable|string',
        ]);

        $lease = Lease::create([
            ...$validated,
            'lease_number' => $this->generateLeaseNumber(),
            'issued_by' => auth()->id(),
            'status' => 'pending',
        ]);

        return redirect()->route('leases.show', $lease)->with('success', 'Lease created. Send the signing link to the user.');
    }

    public function show(Lease $lease)
    {
        $this->authorizeLeaseAccess($lease);

        $lease->load(['asset', 'lessee', 'issuer', 'returnedBy']);

        $signUrl = secure_url(route('leases.sign', ['lease' => $lease], false));

        return view('leases.show', compact('lease', 'signUrl'));
    }

    public function destroy(Lease $lease)
    {
        if (!auth()->user()->isStaff()) {
            abort(403);
        }

        $lease->delete();

        return redirect()->route('leases.index')->with('success', 'Lease deleted.');
    }

    public function sign(Lease $lease)
    {
        $this->authorizeLeaseSign($lease);

        $lease->load(['asset', 'lessee']);

        return view('leases.sign', compact('lease'));
    }

    public function signStore(Request $request, Lease $lease)
    {
        $this->authorizeLeaseSign($lease);

        $validated = $request->validate([
            'signed_name' => 'required|string|max:255',
            'agree' => 'accepted',
        ]);

        $lease->update([
            'status' => 'signed',
            'signed_at' => now(),
            'signed_name' => $validated['signed_name'],
            'signed_ip' => $request->ip(),
            'signed_user_agent' => $request->userAgent(),
        ]);

        $lease->asset->update([
            'assigned_to' => $lease->lessee_id,
            'status' => 'in_use',
            'last_seen_at' => now(),
        ]);

        return redirect()->route('leases.show', $lease)->with('success', 'Lease e-signed successfully.');
    }

    public function markReturned(Request $request, Lease $lease)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        if (!$lease->signed_at || $lease->returned_at) {
            return redirect()->route('leases.show', $lease)->with('error', 'Lease cannot be returned in the current state.');
        }

        $validated = $request->validate([
            'returned_notes' => 'nullable|string',
        ]);

        $lease->update([
            'returned_at' => now(),
            'returned_by' => auth()->id(),
            'returned_notes' => $validated['returned_notes'] ?? null,
        ]);

        $lease->asset->update([
            'assigned_to' => null,
            'status' => 'available',
            'last_seen_at' => now(),
        ]);

        return redirect()->route('leases.show', $lease)->with('success', 'Asset marked as returned.');
    }

    private function authorizeLeaseAccess(Lease $lease): void
    {
        if (auth()->user()->isStaff() || $lease->lessee_id === auth()->id()) {
            return;
        }

        abort(403);
    }

    private function authorizeLeaseSign(Lease $lease): void
    {
        if ($lease->lessee_id !== auth()->id()) {
            abort(403);
        }

        if ($lease->status !== 'pending') {
            abort(403, 'This lease is no longer available for signing.');
        }
    }

    private function generateLeaseNumber(): string
    {
        $year = now()->format('Y');
        $count = Lease::whereYear('created_at', now()->year)->count() + 1;

        return 'LSE-' . $year . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
