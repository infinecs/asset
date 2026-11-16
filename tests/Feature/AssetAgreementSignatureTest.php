<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetAgreementSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function pendingAsset(): Asset
    {
        return Asset::create([
            'type' => 'laptop',
            'asset_tag' => 'ISSBM901',
            'name' => 'Laptop',
            'status' => 'available',
            'agreement_token' => Str::random(48),
            'agreement_sent_at' => now(),
        ]);
    }

    public function test_signing_page_offers_draw_and_upload(): void
    {
        $asset = $this->pendingAsset();

        $this->get(route('agreements.show', $asset->agreement_token))
            ->assertOk()
            ->assertSee('Draw signature')
            ->assertSee('Upload photo')
            ->assertSee('name="signature_file"', false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_agreement_can_be_signed_with_an_uploaded_photo(): void
    {
        $asset = $this->pendingAsset();

        $this->post(route('agreements.store', $asset->agreement_token), [
            'signer_name' => 'Jane Doe',
            'signature_method' => 'upload',
            'signature_file' => UploadedFile::fake()->image('signature.jpg', 400, 150),
            'agree' => '1',
        ])->assertRedirect(route('agreements.show', $asset->agreement_token));

        $asset->refresh();
        $this->assertNotNull($asset->agreement_signed_at);
        $this->assertSame('Jane Doe', $asset->agreement_signed_name);
        $this->assertStringEndsWith('.jpg', $asset->agreement_signature_path);
        Storage::disk('public')->assertExists($asset->agreement_signature_path);
    }

    public function test_agreement_can_still_be_signed_by_drawing(): void
    {
        $asset = $this->pendingAsset();
        $png = base64_encode(UploadedFile::fake()->image('sig.png', 300, 100)->getContent());

        $this->post(route('agreements.store', $asset->agreement_token), [
            'signer_name' => 'Jane Doe',
            'signature_method' => 'draw',
            'signature' => 'data:image/png;base64,' . $png,
            'agree' => '1',
        ])->assertRedirect(route('agreements.show', $asset->agreement_token));

        $asset->refresh();
        $this->assertNotNull($asset->agreement_signed_at);
        $this->assertStringEndsWith('.png', $asset->agreement_signature_path);
        Storage::disk('public')->assertExists($asset->agreement_signature_path);
    }

    public function test_superadmin_can_preview_the_signing_page(): void
    {
        $asset = $this->pendingAsset();
        $asset->update(['agreement_signed_at' => now(), 'agreement_signed_name' => 'Jane Doe']);
        $this->actingAs(User::factory()->create(['role' => 'superadmin']));

        $this->get(route('assets.show', $asset))
            ->assertOk()
            ->assertSee('Preview Signing Page')
            ->assertSee(route('assets.agreement.preview', $asset), false);

        // Shows the unsigned signing form even though this asset is already signed.
        $this->get(route('assets.agreement.preview', $asset))
            ->assertOk()
            ->assertSee('class="preview-banner', false)
            ->assertSee('Upload photo')
            ->assertSee('data-preview="1"', false);

        $this->assertNotNull($asset->fresh()->agreement_signed_at);
    }

    public function test_preview_is_hidden_and_blocked_for_other_roles(): void
    {
        $asset = $this->pendingAsset();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get(route('assets.show', $asset))
            ->assertOk()
            ->assertDontSee('Preview Signing Page');

        $this->get(route('assets.agreement.preview', $asset))->assertForbidden();
    }

    public function test_public_signing_page_is_not_in_preview_mode(): void
    {
        $asset = $this->pendingAsset();

        $this->get(route('agreements.show', $asset->agreement_token))
            ->assertOk()
            ->assertDontSee('class="preview-banner', false)
            ->assertSee('data-preview="0"', false);
    }

    public function test_upload_method_requires_an_image_file(): void
    {
        $asset = $this->pendingAsset();

        $this->post(route('agreements.store', $asset->agreement_token), [
            'signer_name' => 'Jane Doe',
            'signature_method' => 'upload',
            'agree' => '1',
        ])->assertSessionHasErrors('signature_file');

        $this->post(route('agreements.store', $asset->agreement_token), [
            'signer_name' => 'Jane Doe',
            'signature_method' => 'upload',
            'signature_file' => UploadedFile::fake()->create('signature.pdf', 100, 'application/pdf'),
            'agree' => '1',
        ])->assertSessionHasErrors('signature_file');

        $this->assertNull($asset->fresh()->agreement_signed_at);
    }
}
