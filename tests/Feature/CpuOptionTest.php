<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CpuOptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_cpu_option_for_reuse(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->postJson(route('assets.cpus.store'), ['name' => 'Intel Core Ultra 7 258V']);

        $response->assertOk()->assertJson([
            'value' => 'Intel Core Ultra 7 258V',
            'text' => 'Intel Core Ultra 7 258V',
            'group' => 'Intel Core Ultra Series 2',
        ]);
        $this->assertDatabaseHas('cpus', ['name' => 'Intel Core Ultra 7 258V']);

        $this->get(route('assets.create'))
            ->assertOk()
            ->assertSee('Intel Core Ultra 7 258V');
    }

    public function test_adding_an_existing_cpu_does_not_create_a_duplicate(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson(route('assets.cpus.store'), ['name' => 'Intel Core Ultra 7 258V'])->assertOk();
        $this->postJson(route('assets.cpus.store'), ['name' => 'Intel Core Ultra 7 258V'])->assertOk();

        $this->assertDatabaseCount('cpus', 1);
    }

    public function test_only_admins_can_add_cpu_options(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manager']));

        $this->postJson(route('assets.cpus.store'), ['name' => 'Custom CPU'])->assertForbidden();

        $this->assertDatabaseCount('cpus', 0);
    }

    public function test_cpu_option_name_is_required_and_limited_to_100_characters(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson(route('assets.cpus.store'), ['name' => ''])->assertUnprocessable();
        $this->postJson(route('assets.cpus.store'), ['name' => str_repeat('x', 101)])->assertUnprocessable();

        $this->assertDatabaseCount('cpus', 0);
    }
}
