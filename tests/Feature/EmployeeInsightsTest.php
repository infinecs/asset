<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\DigitalProduct;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Models\Workstation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Record checks, onboarding, the holdings statement, headcount and the people calendar.
 */
class EmployeeInsightsTest extends TestCase
{
    use RefreshDatabase;

    private Role $role;
    private Team $team;
    private Employee $boss;
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-25 10:00'));
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $ceo = Role::create(['name' => 'CEO', 'is_top_level' => true]);
        $this->role = Role::create(['name' => 'Engineer']);
        $this->team = Team::create(['name' => 'Platform']);
        $this->boss = $this->person('BIG BOSS', ['role_id' => $ceo->id, 'is_manager' => true, 'team_id' => null, 'manager_id' => null]);
    }

    /**
     * An employee with a complete record unless overridden.
     */
    private function person(string $name, array $attributes = []): Employee
    {
        $this->seq++;

        return Employee::create(array_merge([
            'name' => $name,
            'id_number' => 'INF' . str_pad((string) $this->seq, 3, '0', STR_PAD_LEFT),
            'email' => 'person' . $this->seq . '@example.com',
            'status' => 'active',
            'role_id' => $this->role->id,
            'team_id' => $this->team->id,
            'manager_id' => isset($this->boss) ? $this->boss->id : null,
            'join_date' => '2024-03-01',
            'date_of_birth' => '1990-06-15',
        ], $attributes));
    }

    private function asset(array $attributes = []): Asset
    {
        $this->seq++;

        return Asset::create(array_merge([
            'type' => 'laptop',
            'asset_tag' => 'ISSBM' . str_pad((string) $this->seq, 3, '0', STR_PAD_LEFT),
            'name' => 'Laptop ' . $this->seq,
            'status' => 'in_use',
        ], $attributes));
    }

    // ---- Names in capitals --------------------------------------------------------------------

    public function test_names_are_saved_in_capital_letters(): void
    {
        $form = ['name' => '  siti   noor binti Ahmad ', 'id_number_suffix' => '900', 'email' => 'siti@example.com', 'status' => 'active'];

        $this->post(route('employees.store'), $form)->assertRedirect();
        $siti = Employee::where('email', 'siti@example.com')->firstOrFail();
        $this->assertSame('SITI NOOR BINTI AHMAD', $siti->name);

        // Saving the same name in another case isn't a change.
        $this->put(route('employees.update', $siti), ['name' => 'Siti Noor binti Ahmad'] + $form)->assertRedirect();
        $this->assertSame('SITI NOOR BINTI AHMAD', $siti->fresh()->name);
        $this->assertFalse($siti->histories()->where('action', 'updated')->get()->contains(fn ($h) => isset($h->changes['name'])));

        $this->assertSame('ÉLODIE ÇA', Employee::normalizeName('élodie ça'));
    }

    // ---- Record checks ------------------------------------------------------------------------

    public function test_record_checks_find_and_explain_each_problem(): void
    {
        $gone = $this->person('GONE BOSS', ['status' => 'resigned', 'resigned_date' => '2026-10-01']);
        $cases = [
            'no_manager' => $this->person('NO MANAGER', ['manager_id' => null]),
            'no_team' => $this->person('NO TEAM', ['team_id' => null]),
            'no_role' => $this->person('NO ROLE', ['role_id' => null]),
            'no_join_date' => $this->person('NO JOIN', ['join_date' => null]),
            'no_birthday' => $this->person('NO BIRTHDAY', ['date_of_birth' => null]),
            'manager_left' => $this->person('ORPHAN', ['manager_id' => $gone->id]),
            'left_with_assets' => $withAssets = $this->person('LEFT WITH LAPTOP', ['status' => 'resigned', 'resigned_date' => '2026-10-01']),
            'left_with_licences' => $withLicence = $this->person('LEFT WITH LICENCE', ['status' => 'resigned', 'resigned_date' => '2026-10-01']),
            'left_still_seated' => $seated = $this->person('LEFT SEATED', ['status' => 'resigned', 'resigned_date' => '2026-10-01']),
            'left_with_reports' => $gone,
            'no_resigned_date' => $this->person('LEFT UNDATED', ['status' => 'resigned', 'resigned_date' => null]),
        ];
        $this->asset(['assigned_to' => $withAssets->id]);
        $withLicence->digitalProducts()->attach(DigitalProduct::create(['name' => 'Figma'])->id, ['assigned_at' => now()]);
        $location = Location::create(['name' => 'HQ', 'code' => 'HQ']);
        Workstation::create(['location_id' => $location->id, 'room' => 'RA', 'number' => 1, 'occupant_id' => $seated->id]);
        // Not flagged: opted out of gift cards, and a manager with no personal team.
        $this->person('OPTED OUT', ['date_of_birth' => null, 'gift_card_opt_out' => true]);
        $this->person('A MANAGER', ['is_manager' => true, 'team_id' => null]);

        foreach ($cases as $check => $employee) {
            $found = Employee::withRecordIssue($check)->pluck('name')->all();
            $this->assertContains($employee->name, $found, "{$check} should find {$employee->name}");
            $this->assertArrayHasKey($check, $employee->fresh()->recordIssues(), "{$check} should explain {$employee->name}");
        }

        $this->assertSame([], $this->boss->fresh()->recordIssues(), 'A top-level role needs no manager');
        $flagged = Employee::needsAttention()->pluck('name')->all();
        $this->assertNotContains('OPTED OUT', $flagged);
        $this->assertNotContains('A MANAGER', $flagged);
        $this->assertNotContains('BIG BOSS', $flagged);
        $this->assertSame('Has left but still holds 1 asset(s).', $withAssets->fresh()->recordIssues()['left_with_assets']);
    }

    public function test_list_shows_the_record_check_banner_and_filters_by_it(): void
    {
        $this->person('COMPLETE PERSON');
        $this->person('NO JOIN', ['join_date' => null]);

        $this->get(route('employees.index'))
            ->assertOk()
            ->assertSee('1 employee record(s) are incomplete or out of date')
            ->assertSee(route('employees.index', ['issue' => 'no_join_date']), false);

        $this->get(route('employees.index', ['issue' => 'any']))
            ->assertOk()
            ->assertSee('NO JOIN')
            ->assertDontSee('COMPLETE PERSON')
            ->assertSee('Records that need attention');

        $this->get(route('employees.show', Employee::where('name', 'NO JOIN')->first()))
            ->assertOk()
            ->assertSee('This record needs attention')
            ->assertSee('No join date, so work anniversaries');
    }

    // ---- Onboarding ---------------------------------------------------------------------------

    public function test_recent_joiners_get_the_onboarding_checklist(): void
    {
        $new = $this->person('NEW JOINER', ['join_date' => '2026-11-20']);
        $future = $this->person('STARTS SOON', ['join_date' => '2026-12-05']);
        $old = $this->person('OLD HAND', ['join_date' => '2020-01-01']);
        $done = $this->person('SET UP', ['join_date' => '2026-11-01', 'onboarded_at' => now()]);

        $this->assertTrue($new->isOnboarding());
        $this->assertTrue($future->isOnboarding());
        $this->assertFalse($old->isOnboarding());
        $this->assertFalse($done->isOnboarding());
        $this->assertEqualsCanonicalizing(['NEW JOINER', 'STARTS SOON'], Employee::onboarding()->pluck('name')->all());

        $steps = collect($new->onboardingSteps())->pluck('done', 'key');
        $this->assertSame(['desk' => false, 'equipment' => false, 'agreements' => false, 'licences' => false, 'profile' => true], $steps->all());

        $this->asset(['assigned_to' => $new->id, 'agreement_signed_at' => now()]);
        $this->asset(['assigned_to' => $new->id]);
        $steps = collect($new->fresh()->onboardingSteps())->keyBy('key');
        $this->assertTrue($steps['equipment']['done']);
        $this->assertFalse($steps['agreements']['done']);
        $this->assertSame('1 of 2 signed.', $steps['agreements']['detail']);

        $this->get(route('employees.index'))->assertSee('2 new joiner(s) are still being set up');
        $this->get(route('employees.index', ['onboarding' => 1]))->assertSee('Onboarding 2/5')->assertDontSee('OLD HAND');
        $this->get(route('employees.show', $new))->assertOk()->assertSee('Mark Onboarding Complete')->assertSee('No free desks');
        $location = Location::create(['name' => 'HQ', 'code' => 'HQ']);
        Workstation::create(['location_id' => $location->id, 'room' => 'RA', 'number' => 1]);
        $this->get(route('employees.show', $new))->assertOk()->assertSee('Choose a free desk')->assertSee('INFHQRAWS01');
        $this->get(route('employees.show', $old))->assertOk()->assertDontSee('Mark Onboarding Complete');
    }

    public function test_seating_a_new_joiner_at_a_free_desk(): void
    {
        $new = $this->person('NEW JOINER', ['join_date' => '2026-11-20']);
        $other = $this->person('SITTING ALREADY');
        $location = Location::create(['name' => 'HQ', 'code' => 'HQ']);
        $free = Workstation::create(['location_id' => $location->id, 'room' => 'RA', 'number' => 1]);
        $taken = Workstation::create(['location_id' => $location->id, 'room' => 'RA', 'number' => 2, 'occupant_id' => $other->id]);
        $later = Workstation::create(['location_id' => $location->id, 'room' => 'RA', 'number' => 3]);

        $this->post(route('employees.seat', $new), ['workstation_id' => $taken->id])->assertSessionHasErrors('workstation_id');
        $this->assertSame($other->id, $taken->fresh()->occupant_id);

        $this->post(route('employees.seat', $new), ['workstation_id' => $free->id])
            ->assertRedirect(route('employees.show', $new))
            ->assertSessionHas('success', 'NEW JOINER now sits at INFHQRAWS01.');
        $this->assertSame($new->id, $free->fresh()->occupant_id);

        // Seating them again moves them; the old desk becomes a hot desk.
        $this->post(route('employees.seat', $new), ['workstation_id' => $later->id])->assertRedirect();
        $this->assertNull($free->fresh()->occupant_id);
        $this->assertSame($new->id, $later->fresh()->occupant_id);
        $this->assertTrue(collect($new->fresh()->onboardingSteps())->firstWhere('key', 'desk')['done']);
    }

    public function test_marking_onboarding_complete_notes_open_steps(): void
    {
        $new = $this->person('NEW JOINER', ['join_date' => '2026-11-20']);

        $this->post(route('employees.onboarding.complete', $new))->assertRedirect(route('employees.show', $new));

        $this->assertNotNull($new->fresh()->onboarded_at);
        $this->assertFalse($new->fresh()->isOnboarding());
        $history = $new->histories()->where('action', 'onboarded')->first();
        $this->assertStringContainsString('Not done: Seat them at a desk', $history->notes);
    }

    // ---- Statement ----------------------------------------------------------------------------

    public function test_statement_lists_everything_held_and_becomes_a_clearance_form_for_leavers(): void
    {
        $person = $this->person('NUR AISYAH');
        $this->asset(['assigned_to' => $person->id, 'asset_tag' => 'ISSBM777', 'serial_number' => 'SN-12345']);
        $person->digitalProducts()->attach(DigitalProduct::create(['name' => 'Figma', 'plan' => 'Pro'])->id, ['assigned_at' => now()]);
        $location = Location::create(['name' => 'HQ', 'code' => 'HQ']);
        Workstation::create(['location_id' => $location->id, 'room' => 'RA', 'number' => 1, 'occupant_id' => $person->id]);

        $this->get(route('employees.statement', $person))
            ->assertOk()
            ->assertSee('Equipment &amp; Access Statement', false)
            ->assertSee('ISSBM777')
            ->assertSee('SN-12345')
            ->assertSee('INFHQRAWS01')
            ->assertSee('Figma')
            ->assertSee('3 item(s)')
            ->assertDontSee('Returned');

        $person->update(['status' => 'resigned', 'resigned_date' => '2026-11-30']);
        $this->get(route('employees.statement', $person))
            ->assertOk()
            ->assertSee('Offboarding Clearance')
            ->assertSee('Returned')
            ->assertSee('30 Nov 2026');
    }

    // ---- Headcount ----------------------------------------------------------------------------

    public function test_headcount_counts_joiners_leavers_and_turnover(): void
    {
        // Boss joined 2024-03-01 like everyone by default; four more over the year.
        $this->person('JOINED IN MARCH', ['join_date' => '2026-03-10']);
        $this->person('JOINED IN OCTOBER', ['join_date' => '2026-10-05']);
        $this->person('LEFT IN JUNE', ['join_date' => '2023-01-01', 'status' => 'resigned', 'resigned_date' => '2026-06-30']);
        $this->person('LEFT LONG AGO', ['join_date' => '2020-01-01', 'status' => 'resigned', 'resigned_date' => '2024-01-31']);
        $this->person('STARTS NEXT MONTH', ['join_date' => '2026-12-01']);
        $this->person('NO JOIN DATE', ['join_date' => null]);
        $this->person('LEFT UNDATED', ['status' => 'resigned', 'resigned_date' => null]);

        $response = $this->get(route('employees.headcount'))->assertOk();
        $series = collect($response->viewData('series'))->keyBy('key');
        $summary = $response->viewData('summary');

        // Dec 2025 to Nov 2026. At the start: boss, LEFT IN JUNE and NO JOIN DATE = 3.
        $this->assertCount(12, $series);
        $this->assertSame(3, $series['2025-12']['headcount']);
        $this->assertSame(1, $series['2026-03']['joined']);
        $this->assertSame(4, $series['2026-03']['headcount']);
        $this->assertSame(1, $series['2026-06']['left']);
        $this->assertSame(3, $series['2026-06']['headcount'], 'A leaver is off at the end of their last day');
        $this->assertSame(4, $series['2026-11']['headcount']);

        $this->assertSame(4, $summary['headcount']);
        $this->assertSame(1, $summary['change']);
        $this->assertSame(2, $summary['joined']);
        $this->assertSame(1, $summary['left']);
        $this->assertSame(1, $summary['starting']);
        $this->assertEqualsWithDelta(1 / ((3 + array_sum($series->pluck('headcount')->all())) / 13) * 100, $summary['turnover'], 0.1);
        $this->assertSame(['noJoinDate' => 1, 'noResignedDate' => 1], $response->viewData('caveats'));

        $response->assertSee('Headcount &amp; Turnover', false)->assertSee('LEFT IN JUNE');
    }

    public function test_headcount_filters_by_team(): void
    {
        $other = Team::create(['name' => 'Sales']);
        $this->person('PLATFORM PERSON');
        $this->person('SALES PERSON', ['team_id' => $other->id]);

        $summary = $this->get(route('employees.headcount', ['team' => $other->id]))->assertOk()->viewData('summary');

        $this->assertSame(1, $summary['headcount']);
    }

    // ---- People calendar ----------------------------------------------------------------------

    public function test_calendar_shows_birthdays_anniversaries_joins_and_last_days(): void
    {
        $this->person('BIRTHDAY PERSON', ['date_of_birth' => '1992-02-29', 'join_date' => '2025-07-01']);
        $this->person('FIVE YEARS', ['join_date' => '2022-02-10']);
        $this->person('SIX YEARS', ['join_date' => '2021-02-11']);
        $this->person('NEW IN FEB', ['join_date' => '2027-02-02', 'date_of_birth' => '1990-08-01']);
        $this->person('LEAVES IN FEB', ['status' => 'resigned', 'resigned_date' => '2027-02-26', 'date_of_birth' => '1990-02-03']);

        $events = $this->get(route('employees.calendar', ['month' => '2027-02']))->assertOk()->viewData('events');
        $byName = $events->groupBy(fn ($e) => $e['employee']->name)->map(fn ($list) => $list->map(fn ($e) => $e['type'] . '@' . $e['date']->format('m-d'))->all());

        // 29 Feb falls on the 28th in 2027; a leaver's birthday isn't shown.
        $this->assertSame(['birthday@02-28'], $byName['BIRTHDAY PERSON']);
        $this->assertSame(['anniversary@02-10'], $byName['FIVE YEARS']);
        $this->assertSame(['anniversary@02-11'], $byName['SIX YEARS']);
        $this->assertSame(['joining@02-02'], $byName['NEW IN FEB']);
        $this->assertSame(['leaving@02-26'], $byName['LEAVES IN FEB']);

        $anniversaries = $events->where('type', 'anniversary')->keyBy(fn ($e) => $e['employee']->name);
        $this->assertSame(['5 years', true], [$anniversaries['FIVE YEARS']['detail'], $anniversaries['FIVE YEARS']['milestone']]);
        $this->assertSame(['6 years', false], [$anniversaries['SIX YEARS']['detail'], $anniversaries['SIX YEARS']['milestone']]);
    }

    public function test_new_pages_are_for_admins_only(): void
    {
        $person = $this->person('SOMEONE');
        $this->actingAs(User::factory()->create(['role' => 'staff']));

        $this->get(route('employees.calendar'))->assertForbidden();
        $this->get(route('employees.headcount'))->assertForbidden();
        $this->get(route('employees.statement', $person))->assertForbidden();
        $this->post(route('employees.onboarding.complete', $person))->assertForbidden();
        $this->post(route('employees.seat', $person), ['workstation_id' => 1])->assertForbidden();
        $this->get(route('employees.index'))->assertOk()->assertDontSee('need attention')->assertDontSee('People Calendar');
    }
}
