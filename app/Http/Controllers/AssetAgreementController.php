<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetAgreementController extends Controller
{
    public function show(string $token)
    {
        $asset = Asset::where('agreement_token', $token)->firstOrFail();
        $asset->load('assignedEmployee', 'brand', 'category');

        return view('agreements.show', compact('asset'));
    }

    public function store(Request $request, string $token)
    {
        $asset = Asset::where('agreement_token', $token)->firstOrFail();

        if ($asset->agreement_signed_at) {
            return redirect()->route('agreements.show', $token);
        }

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signature' => 'required|string|max:2000000',
            'agree' => 'accepted',
        ]);

        if (!str_starts_with($validated['signature'], 'data:image/png;base64,')) {
            return back()->withErrors(['signature' => 'Please provide your signature.'])->withInput();
        }

        $decoded = base64_decode(substr($validated['signature'], strlen('data:image/png;base64,')), true);

        if ($decoded === false || strlen($decoded) < 100) {
            return back()->withErrors(['signature' => 'Please provide your signature.'])->withInput();
        }

        $path = 'assets/signatures/' . $asset->id . '-' . now()->timestamp . '.png';
        Storage::disk('public')->put($path, $decoded);

        $asset->update([
            'agreement_signed_at' => now(),
            'agreement_signature_path' => $path,
            'agreement_signed_name' => $validated['signer_name'],
        ]);

        AssetHistory::create([
            'asset_id' => $asset->id,
            'user_id' => null,
            'action' => 'agreement_signed',
            'notes' => 'Equipment agreement digitally signed by ' . $validated['signer_name'],
        ]);

        return redirect()->route('agreements.show', $token)->with('success', 'Agreement signed successfully.');
    }
}
