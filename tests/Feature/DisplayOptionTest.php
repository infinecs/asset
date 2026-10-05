<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisplayOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_normalized_display_option_for_reuse(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->postJson(route('assets.displays.store'), ['name' => '14.0']);

        $response->assertOk()->assertJson([
            'value' => '14.0"',
            'text' => '14.0"',
        ]);
        $this->assertDatabaseHas('display_options', ['value' => '14.0"']);

        $this->get(route('assets.create'))
            ->assertOk()
            ->assertSee('14.0"');
    }

    public function test_display_name_must_be_a_numeric_measurement(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson(route('assets.displays.store'), ['name' => 'Fourteen inch'])
            ->assertUnprocessable();

        $this->assertDatabaseCount('display_options', 0);
    }

    public function test_only_admins_can_add_display_options(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manager']));

        $this->postJson(route('assets.displays.store'), ['name' => '14.0'])
            ->assertForbidden();

        $this->assertDatabaseCount('display_options', 0);
    }
}
