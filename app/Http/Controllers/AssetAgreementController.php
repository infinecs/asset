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

    /**
     * Superadmin-only look at the signing page for an asset, in its unsigned state.
     * Nothing is saved and the form cannot be submitted.
     */
    public function preview(Asset $asset)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $asset->load('assignedEmployee', 'brand', 'category');

        // Show the signing form even when the asset has already been signed (in memory only).
        $asset->agreement_signed_at = null;

        return view('agreements.show', ['asset' => $asset, 'preview' => true]);
    }

    public function store(Request $request, string $token)
    {
        $asset = Asset::where('agreement_token', $token)->firstOrFail();

        if ($asset->agreement_signed_at) {
            return redirect()->route('agreements.show', $token);
        }

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signature_method' => 'required|in:draw,upload',
            'signature' => 'required_if:signature_method,draw|nullable|string|max:2000000',
            'signature_file' => 'required_if:signature_method,upload|nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'agree' => 'accepted',
        ], [
            'signature.required_if' => 'Please draw your signature.',
            'signature_file.required_if' => 'Please upload a photo of your signature.',
            'signature_file.image' => 'The signature upload must be an image.',
            'signature_file.mimes' => 'The signature photo must be a JPG, PNG or WEBP image.',
            'signature_file.max' => 'The signature photo must not be larger than 5 MB.',
        ]);

        $basePath = 'assets/signatures/' . $asset->id . '-' . now()->timestamp;

        if ($validated['signature_method'] === 'upload') {
            $file = $request->file('signature_file');
            $extension = strtolower($file->extension() ?: 'png');
            $path = $basePath . '.' . $extension;
            Storage::disk('public')->putFileAs(dirname($path), $file, basename($path));
        } else {
            if (!str_starts_with($validated['signature'], 'data:image/png;base64,')) {
                return back()->withErrors(['signature' => 'Please provide your signature.'])->withInput();
            }

            $decoded = base64_decode(substr($validated['signature'], strlen('data:image/png;base64,')), true);

            if ($decoded === false || strlen($decoded) < 100) {
                return back()->withErrors(['signature' => 'Please provide your signature.'])->withInput();
            }

            $path = $basePath . '.png';
            Storage::disk('public')->put($path, $decoded);
        }

        $asset->update([
            'agreement_signed_at' => now(),
            'agreement_signature_path' => $path,
            'agreement_signed_name' => $validated['signer_name'],
        ]);

        AssetHistory::create([
            'asset_id' => $asset->id,
            'user_id' => null,
            'action' => 'agreement_signed',
            'notes' => 'Equipment agreement digitally signed by ' . $validated['signer_name']
                . ($validated['signature_method'] === 'upload' ? ' (uploaded signature photo)' : ''),
        ]);

        return redirect()->route('agreements.show', $token)->with('success', 'Agreement signed successfully.');
    }
}
