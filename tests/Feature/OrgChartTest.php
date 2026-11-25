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
        $boss = $this->employee('BIG BOSS', ['is_manager' => true]);
        $manager = $this->employee('MIDDLE MANAGER', ['is_manager' => true, 'manager_id' => $boss->id]);
        $report = $this->employee('DIRECT REPORT', ['is_manager' => true, 'manager_id' => $manager->id]);
        $this->employee('GRAND REPORT', ['manager_id' => $report->id]);
        $this->employee('RESIGNED REPORT', ['manager_id' => $manager->id, 'status' => 'resigned']);

        $this->get(route('employees.individual-org-chart', $manager))
            ->assertOk()
            ->assertSeeInOrder(['BIG BOSS', 'MIDDLE MANAGER', 'DIRECT REPORT', 'GRAND REPORT'])
            ->assertDontSee('RESIGNED REPORT')
            ->assertSee('org-node-current', false);
    }

    public function test_levels_limit_the_depth_and_count_hidden_reports(): void
    {
        $manager = $this->employee('MIDDLE MANAGER', ['is_manager' => true]);
        $report = $this->employee('DIRECT REPORT', ['manager_id' => $manager->id]);
        $this->employee('GRAND REPORT', ['manager_id' => $report->id]);

        $this->get(route('employees.individual-org-chart', ['employee' => $manager, 'levels' => 1]))
            ->assertOk()
            ->assertSee('DIRECT REPORT')
            ->assertDontSee('GRAND REPORT')
            ->assertSee('+1 more below');
    }

    public function test_employee_without_reports_has_no_chart(): void
    {
        $manager = $this->employee('LONELY MANAGER', ['is_manager' => true]);
        $this->employee('FORMER REPORT', ['manager_id' => $manager->id, 'status' => 'resigned']);
        $staff = $this->employee('STAFF MEMBER');

        $this->get(route('employees.individual-org-chart', $manager))->assertNotFound();
        $this->get(route('employees.individual-org-chart', $staff))->assertNotFound();

        $this->get(route('employees.show', $staff))
            ->assertOk()
            ->assertDontSee(route('employees.individual-org-chart', $staff));
    }

    public function test_employee_without_a_manager_sits_under_the_top_level_role(): void
    {
        $ceo = $this->employee('THE CEO', ['role_id' => Role::create(['name' => 'CEO', 'is_top_level' => true])->id]);
        $manager = $this->employee('ORPHAN MANAGER', ['is_manager' => true]);
        $this->employee('DIRECT REPORT', ['manager_id' => $manager->id]);

        $this->get(route('employees.individual-org-chart', $manager))
            ->assertOk()
            ->assertSeeInOrder(['THE CEO', 'ORPHAN MANAGER', 'DIRECT REPORT']);

        // The top-level employee gets a chart even without direct reports of their own.
        $this->get(route('employees.individual-org-chart', $ceo))
            ->assertOk()
            ->assertSeeInOrder(['THE CEO', 'ORPHAN MANAGER']);
    }

    public function test_circular_manager_chain_does_not_loop_forever(): void
    {
        $a = $this->employee('ALPHA', ['is_manager' => true]);
        $b = $this->employee('BRAVO', ['is_manager' => true, 'manager_id' => $a->id]);
        $a->update(['manager_id' => $b->id]);

        $this->get(route('employees.individual-org-chart', $a))
            ->assertOk()
            ->assertSeeInOrder(['BRAVO', 'ALPHA']);
    }

    public function test_direct_reports_card_counts_active_reports_and_lists_former_separately(): void
    {
        $manager = $this->employee('MIDDLE MANAGER', ['is_manager' => true]);
        $this->employee('CURRENT REPORT', ['manager_id' => $manager->id]);
        $this->employee('FORMER REPORT', ['manager_id' => $manager->id, 'status' => 'resigned']);

        $this->get(route('employees.show', $manager))
            ->assertOk()
            ->assertSee('Direct Reports (1)')
            ->assertSeeInOrder(['CURRENT REPORT', 'Former', 'FORMER REPORT'])
            ->assertSee(route('employees.individual-org-chart', $manager));
    }
}
