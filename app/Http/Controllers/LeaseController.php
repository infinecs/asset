<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\User;
use Illuminate\Http\Request;

class LeaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Lease::with(['asset', 'lessee', 'issuer', 'returnedBy'])->latest();

        $unsignedSearch = trim((string) $request->query('unsigned_search', ''));
        $signedSearch = trim((string) $request->query('signed_search', ''));

        if (!auth()->user()->isStaff()) {
            $query->where('lessee_id', auth()->id());
        }

        $unsignedLeasesQuery = (clone $query)
            ->where('status', '!=', 'signed');

        if ($unsignedSearch !== '') {
            $unsignedLeasesQuery->where(function ($filterQuery) use ($unsignedSearch) {
                $filterQuery->where('lease_number', 'like', '%' . $unsignedSearch . '%')
                    ->orWhere('status', 'like', '%' . $unsignedSearch . '%')
                    ->orWhereHas('asset', function ($assetQuery) use ($unsignedSearch) {
                        $assetQuery->where('name', 'like', '%' . $unsignedSearch . '%')
                            ->orWhere('asset_tag', 'like', '%' . $unsignedSearch . '%');
                    })
                    ->orWhereHas('lessee', function ($lesseeQuery) use ($unsignedSearch) {
                        $lesseeQuery->where('name', 'like', '%' . $unsignedSearch . '%');
                    });
            });
        }

        $unsignedLeases = $unsignedLeasesQuery
            ->paginate(10, ['*'], 'unsigned_page')
            ->appends(['unsigned_search' => $unsignedSearch, 'signed_search' => $signedSearch]);

        $signedLeasesQuery = (clone $query)
            ->where('status', 'signed')
            ->whereHas('asset', function ($assetQuery) {
                $assetQuery->where('status', 'in_use');
            });

        if ($signedSearch !== '') {
            $signedLeasesQuery->where(function ($filterQuery) use ($signedSearch) {
                $filterQuery->where('lease_number', 'like', '%' . $signedSearch . '%')
                    ->orWhereHas('asset', function ($assetQuery) use ($signedSearch) {
                        $assetQuery->where('name', 'like', '%' . $signedSearch . '%')
                            ->orWhere('asset_tag', 'like', '%' . $signedSearch . '%');
                    })
                    ->orWhereHas('lessee', function ($lesseeQuery) use ($signedSearch) {
                        $lesseeQuery->where('name', 'like', '%' . $signedSearch . '%');
                    });
            });
        }

        $signedLeases = $signedLeasesQuery
            ->paginate(10, ['*'], 'signed_page')
            ->appends(['unsigned_search' => $unsignedSearch, 'signed_search' => $signedSearch]);

        return view('leases.index', compact('unsignedLeases', 'signedLeases', 'unsignedSearch', 'signedSearch'));
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
