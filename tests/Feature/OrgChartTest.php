<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function employee(string $name, array $attributes = []): Employee
    {
        static $n = 0;
        $n++;

        return Employee::create(array_merge([
            'name' => $name,
            'id_number' => 'EMP' . $n,
            'email' => "employee{$n}@example.com",
            'status' => 'active',
        ], $attributes));
    }

    public function test_manager_chart_shows_manager_chain_and_everyone_below(): void
    {
        $boss = $this->employee('Big Boss', ['is_manager' => true]);
        $manager = $this->employee('Middle Manager', ['is_manager' => true, 'manager_id' => $boss->id]);
        $report = $this->employee('Direct Report', ['is_manager' => true, 'manager_id' => $manager->id]);
        $this->employee('Grand Report', ['manager_id' => $report->id]);
        $this->employee('Resigned Report', ['manager_id' => $manager->id, 'status' => 'resigned']);

        $this->get(route('employees.individual-org-chart', $manager))
            ->assertOk()
            ->assertSeeInOrder(['Big Boss', 'Middle Manager', 'Direct Report', 'Grand Report'])
            ->assertDontSee('Resigned Report')
            ->assertSee('org-node-current', false);
    }

    public function test_levels_limit_the_depth_and_count_hidden_reports(): void
    {
        $manager = $this->employee('Middle Manager', ['is_manager' => true]);
        $report = $this->employee('Direct Report', ['manager_id' => $manager->id]);
        $this->employee('Grand Report', ['manager_id' => $report->id]);

        $this->get(route('employees.individual-org-chart', ['employee' => $manager, 'levels' => 1]))
            ->assertOk()
            ->assertSee('Direct Report')
            ->assertDontSee('Grand Report')
            ->assertSee('+1 more below');
    }

    public function test_employee_without_reports_has_no_chart(): void
    {
        $manager = $this->employee('Lonely Manager', ['is_manager' => true]);
        $this->employee('Former Report', ['manager_id' => $manager->id, 'status' => 'resigned']);
        $staff = $this->employee('Staff Member');

        $this->get(route('employees.individual-org-chart', $manager))->assertNotFound();
        $this->get(route('employees.individual-org-chart', $staff))->assertNotFound();

        $this->get(route('employees.show', $staff))
            ->assertOk()
            ->assertDontSee(route('employees.individual-org-chart', $staff));
    }

    public function test_employee_without_a_manager_sits_under_the_top_level_role(): void
    {
        $ceo = $this->employee('The Ceo', ['role_id' => Role::create(['name' => 'CEO', 'is_top_level' => true])->id]);
        $manager = $this->employee('Orphan Manager', ['is_manager' => true]);
        $this->employee('Direct Report', ['manager_id' => $manager->id]);

        $this->get(route('employees.individual-org-chart', $manager))
            ->assertOk()
            ->assertSeeInOrder(['The Ceo', 'Orphan Manager', 'Direct Report']);

        // The top-level employee gets a chart even without direct reports of their own.
        $this->get(route('employees.individual-org-chart', $ceo))
            ->assertOk()
            ->assertSeeInOrder(['The Ceo', 'Orphan Manager']);
    }

    public function test_circular_manager_chain_does_not_loop_forever(): void
    {
        $a = $this->employee('Alpha', ['is_manager' => true]);
        $b = $this->employee('Bravo', ['is_manager' => true, 'manager_id' => $a->id]);
        $a->update(['manager_id' => $b->id]);

        $this->get(route('employees.individual-org-chart', $a))
            ->assertOk()
            ->assertSeeInOrder(['Bravo', 'Alpha']);
    }

    public function test_direct_reports_card_counts_active_reports_and_lists_former_separately(): void
    {
        $manager = $this->employee('Middle Manager', ['is_manager' => true]);
        $this->employee('Current Report', ['manager_id' => $manager->id]);
        $this->employee('Former Report', ['manager_id' => $manager->id, 'status' => 'resigned']);

        $this->get(route('employees.show', $manager))
            ->assertOk()
            ->assertSee('Direct Reports (1)')
            ->assertSeeInOrder(['Current Report', 'Former', 'Former Report'])
            ->assertSee(route('employees.individual-org-chart', $manager));
    }
}
