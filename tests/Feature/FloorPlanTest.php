<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Models\Workstation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FloorPlanTest extends TestCase
{
    use RefreshDatabase;

    private Location $office;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->office = Location::create(['name' => 'Infinecs P1 A', 'code' => 'P1A']);
    }

    private function desk(int $number, ?Location $location = null, array $attributes = []): Workstation
    {
        return Workstation::create(array_merge([
            'location_id' => ($location ?? $this->office)->id,
            'room' => 'RA',
            'number' => $number,
        ], $attributes));
    }

    private function withPlan(): Location
    {
        $path = UploadedFile::fake()->image('plan.png', 1200, 800)->store('floor-plans', 'public');
        $this->office->update(['floor_plan_path' => $path]);

        return $this->office;
    }

    public function test_page_asks_for_a_floor_plan_when_the_location_has_none(): void
    {
        $this->desk(1);

        $this->get(route('workstations.floor-plan', ['location' => $this->office->id]))
            ->assertOk()
            ->assertSee('No floor plan for Infinecs P1 A yet')
            ->assertSee(route('locations.floor-plan.upload', $this->office), false);
    }

    public function test_page_shows_the_plan_with_its_desks(): void
    {
        $this->withPlan();
        $employee = Employee::create(['name' => 'Nur Aisyah', 'id_number' => 'INF001', 'email' => 'aisyah@example.com', 'status' => 'active']);
        $this->desk(1, null, ['occupant_id' => $employee->id, 'plan_x' => 25.5, 'plan_y' => 40]);
        $this->desk(2);

        $response = $this->get(route('workstations.floor-plan', ['location' => $this->office->id]))->assertOk();

        $response->assertSee('storage/' . $this->office->floor_plan_path, false);

        preg_match('~<script type="application/json" id="floor-plan-data">(.*?)</script>~s', $response->getContent(), $m);
        $data = json_decode($m[1] ?? 'null', true);

        $this->assertSame(route('locations.floor-plan.layout', $this->office), $data['saveUrl']);
        $this->assertSame(['INFP1ARAWS01', 'INFP1ARAWS02'], array_column($data['desks'], 'code'));
        $this->assertSame(['Nur Aisyah', 'assigned', 25.5, 40.0], [
            $data['desks'][0]['occupant'], $data['desks'][0]['status'], (float) $data['desks'][0]['x'], (float) $data['desks'][0]['y'],
        ]);
        $this->assertSame(['empty', null], [$data['desks'][1]['status'], $data['desks'][1]['x']]);
    }

    public function test_page_defaults_to_a_location_with_a_floor_plan(): void
    {
        Location::create(['name' => 'Aaa Empty Office', 'code' => 'AAA']);
        $this->withPlan();

        $this->get(route('workstations.floor-plan'))
            ->assertOk()
            ->assertSee('Floor plan of Infinecs P1 A');
    }

    public function test_uploading_a_floor_plan_stores_it(): void
    {
        $this->post(route('locations.floor-plan.upload', $this->office), [
            'floor_plan' => UploadedFile::fake()->image('plan.jpg', 1600, 900),
        ])->assertRedirect(route('workstations.floor-plan', ['location' => $this->office->id]));

        $path = $this->office->fresh()->floor_plan_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_replacing_the_image_deletes_the_old_one_and_keeps_positions(): void
    {
        $old = $this->withPlan()->floor_plan_path;
        $desk = $this->desk(1, null, ['plan_x' => 10, 'plan_y' => 20]);

        $this->travel(5)->seconds();
        $this->post(route('locations.floor-plan.upload', $this->office), [
            'floor_plan' => UploadedFile::fake()->image('new-plan.png', 1600, 900),
        ])->assertRedirect();

        $new = $this->office->fresh()->floor_plan_path;
        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
        $this->assertSame([10.0, 20.0], [$desk->fresh()->plan_x, $desk->fresh()->plan_y]);
    }

    public function test_upload_rejects_non_images(): void
    {
        $this->post(route('locations.floor-plan.upload', $this->office), [
            'floor_plan' => UploadedFile::fake()->create('plan.pdf', 200, 'application/pdf'),
        ])->assertSessionHasErrors('floor_plan');

        $this->assertNull($this->office->fresh()->floor_plan_path);
    }

    public function test_saving_the_layout_places_moves_and_removes_desks(): void
    {
        $this->withPlan();
        $a = $this->desk(1);
        $b = $this->desk(2, null, ['plan_x' => 5, 'plan_y' => 5]);
        $c = $this->desk(3, null, ['plan_x' => 50, 'plan_y' => 50]);

        $this->putJson(route('locations.floor-plan.layout', $this->office), [
            'positions' => [
                ['id' => $a->id, 'x' => 12.3456, 'y' => 78.9],
                ['id' => $b->id, 'x' => 60, 'y' => 30],
                ['id' => $c->id, 'x' => null, 'y' => null],
            ],
        ])->assertOk()->assertJson(['placed' => 2]);

        $this->assertSame([12.346, 78.9], [$a->fresh()->plan_x, $a->fresh()->plan_y]);
        $this->assertSame([60.0, 30.0], [$b->fresh()->plan_x, $b->fresh()->plan_y]);
        $this->assertFalse($c->fresh()->isPlaced());
    }

    public function test_layout_saves_resized_desks_and_resets_sizes(): void
    {
        $this->withPlan();
        $sized = $this->desk(1);
        $reset = $this->desk(2, null, ['plan_x' => 40, 'plan_y' => 40, 'plan_w' => 8, 'plan_h' => 6]);
        $unplaced = $this->desk(3, null, ['plan_x' => 60, 'plan_y' => 60, 'plan_w' => 8, 'plan_h' => 6]);

        $this->putJson(route('locations.floor-plan.layout', $this->office), [
            'positions' => [
                ['id' => $sized->id, 'x' => 20, 'y' => 30, 'w' => 12.5, 'h' => 7.25],
                ['id' => $reset->id, 'x' => 40, 'y' => 40, 'w' => null, 'h' => null],
                // Off the plan: a stray size is dropped along with the position.
                ['id' => $unplaced->id, 'x' => null, 'y' => null, 'w' => 8, 'h' => 6],
            ],
        ])->assertOk();

        $this->assertSame([12.5, 7.25], [$sized->fresh()->plan_w, $sized->fresh()->plan_h]);
        $this->assertSame([null, null], [$reset->fresh()->plan_w, $reset->fresh()->plan_h]);
        $this->assertSame([null, null, null, null], [
            $unplaced->fresh()->plan_x, $unplaced->fresh()->plan_y, $unplaced->fresh()->plan_w, $unplaced->fresh()->plan_h,
        ]);

        // Width and height go together, and can't be tiny or larger than the plan.
        foreach ([['w' => 10, 'h' => null], ['w' => 0.1, 'h' => 5], ['w' => 150, 'h' => 5]] as $size) {
            $this->putJson(route('locations.floor-plan.layout', $this->office), [
                'positions' => [['id' => $sized->id, 'x' => 20, 'y' => 30] + $size],
            ])->assertUnprocessable();
        }
        $this->assertSame([12.5, 7.25], [$sized->fresh()->plan_w, $sized->fresh()->plan_h]);
    }

    public function test_layout_saves_vertical_desks_and_turns_them_back(): void
    {
        $this->withPlan();
        $turned = $this->desk(1);
        $back = $this->desk(2, null, ['plan_x' => 40, 'plan_y' => 40, 'plan_rotation' => 90]);
        $unplaced = $this->desk(3, null, ['plan_x' => 60, 'plan_y' => 60, 'plan_rotation' => 90]);

        $this->putJson(route('locations.floor-plan.layout', $this->office), [
            'positions' => [
                ['id' => $turned->id, 'x' => 20, 'y' => 30, 'w' => 12.5, 'h' => 7.25, 'r' => 90],
                // Left out = horizontal.
                ['id' => $back->id, 'x' => 40, 'y' => 40],
                // Off the plan: a stray rotation is dropped along with the position.
                ['id' => $unplaced->id, 'x' => null, 'y' => null, 'r' => 90],
            ],
        ])->assertOk();

        $this->assertSame([90, 12.5, 7.25], [$turned->fresh()->plan_rotation, $turned->fresh()->plan_w, $turned->fresh()->plan_h]);
        $this->assertSame(0, $back->fresh()->plan_rotation);
        $this->assertSame(0, $unplaced->fresh()->plan_rotation);
        $this->assertSame(90, $this->planData()['desks'][0]['r']);

        // Only horizontal or vertical.
        $this->putJson(route('locations.floor-plan.layout', $this->office), [
            'positions' => [['id' => $turned->id, 'x' => 20, 'y' => 30, 'r' => 45]],
        ])->assertUnprocessable()->assertJsonValidationErrors('positions.0.r');
        $this->assertSame(90, $turned->fresh()->plan_rotation);

        // Removing the plan turns every desk back.
        $this->delete(route('locations.floor-plan.destroy', $this->office))->assertRedirect();
        $this->assertSame(0, $turned->fresh()->plan_rotation);
    }

    public function test_layout_creates_copied_desks_numbered_after_the_room(): void
    {
        $this->withPlan();
        $source = $this->desk(1, null, ['plan_x' => 10, 'plan_y' => 20, 'plan_w' => 5, 'plan_h' => 3, 'plan_rotation' => 90]);
        $this->desk(7); // the highest number in room RA
        $this->desk(3, null, ['room' => 'RB']);

        $response = $this->putJson(route('locations.floor-plan.layout', $this->office), [
            'positions' => [['id' => $source->id, 'x' => 10, 'y' => 20, 'w' => 5, 'h' => 3, 'r' => 90]],
            'new_desks' => [
                ['key' => 'new-1', 'room' => 'RA', 'x' => 15, 'y' => 20, 'w' => 5, 'h' => 3, 'r' => 90],
                ['key' => 'new-2', 'room' => 'RA', 'x' => 20, 'y' => 20, 'w' => 5, 'h' => 3, 'r' => 90],
                ['key' => 'new-3', 'room' => 'RB', 'x' => 50, 'y' => 50],
            ],
        ])->assertOk()
            ->assertJsonPath('created.new-1.code', 'INFP1ARAWS08')
            ->assertJsonPath('created.new-2.code', 'INFP1ARAWS09')
            ->assertJsonPath('created.new-3.code', 'INFP1ARBWS04')
            ->assertJsonPath('created.new-1.r', 90);

        $this->assertStringContainsString('Added 3 desk(s)', $response->json('message'));
        $copy = Workstation::where('name', 'INFP1ARAWS09')->firstOrFail();
        $this->assertSame([20.0, 20.0, 5.0, 3.0, 90, null], [$copy->plan_x, $copy->plan_y, $copy->plan_w, $copy->plan_h, $copy->plan_rotation, $copy->occupant_id]);
        $this->assertSame(6, $this->office->workstations()->count());

        // A copy must be placed on the plan.
        $this->putJson(route('locations.floor-plan.layout', $this->office), [
            'positions' => [],
            'new_desks' => [['key' => 'new-4', 'room' => 'RA', 'x' => null, 'y' => 20]],
        ])->assertUnprocessable()->assertJsonValidationErrors('new_desks.0.x');
        $this->assertSame(6, $this->office->workstations()->count());
    }

    public function test_page_flags_desks_that_need_attention(): void
    {
        $this->withPlan();
        $left = $this->employee('Gone Person', 'INF003', 'resigned');
        $seated = $this->employee('Nur Aisyah', 'INF001');
        $other = $this->employee('Ali Bakar', 'INF002');
        $leftDesk = $this->desk(1, null, ['occupant_id' => $left->id]);
        $bareDesk = $this->desk(2, null, ['occupant_id' => $seated->id]);
        $mixedDesk = $this->desk(3);
        $fineDesk = $this->desk(4, null, ['occupant_id' => $other->id]);
        $this->assetAt($leftDesk);
        $this->assetAt($mixedDesk, ['asset_tag' => 'ISSBM900', 'assigned_to' => $other->id]);
        $this->assetAt($fineDesk, ['assigned_to' => $other->id]);

        $warnings = collect($this->planData()['desks'])->pluck('warnings', 'code');

        $this->assertSame(['Gone Person has left the company but is still seated here.'], $warnings['INFP1ARAWS01']);
        $this->assertSame(['Nur Aisyah sits here, but no equipment is recorded at the desk.'], $warnings['INFP1ARAWS02']);
        $this->assertSame(['ISSBM900 is assigned to Ali Bakar, who does not sit here.'], $warnings['INFP1ARAWS03']);
        $this->assertSame([], $warnings['INFP1ARAWS04']);

        // The list's "needs attention" filter finds the same desks.
        $this->assertEqualsCanonicalizing(
            [$leftDesk->id, $bareDesk->id, $mixedDesk->id],
            Workstation::needsAttention()->pluck('id')->all(),
        );
        $this->get(route('workstations.index', ['attention' => 1]))
            ->assertOk()
            ->assertSee('INFP1ARAWS03')
            ->assertDontSee('INFP1ARAWS04');
        $this->get(route('workstations.show', $mixedDesk))->assertOk()->assertSee('This desk needs attention');
    }

    public function test_page_sends_how_long_since_each_desk_was_audited(): void
    {
        $this->withPlan();
        $audited = $this->desk(1);
        $this->desk(2);
        $audit = \App\Models\WorkstationAudit::create(['workstation_id' => $audited->id, 'user_id' => auth()->id(), 'present_count' => 0, 'missing' => [], 'found' => []]);
        $audit->forceFill(['created_at' => now()->subDays(80)])->save();

        $data = $this->planData();

        $this->assertSame([80, null], array_column($data['desks'], 'audit_age'));
        $this->assertSame(['interval' => Workstation::AUDIT_INTERVAL_DAYS, 'soon' => Workstation::AUDIT_DUE_SOON_DAYS], $data['audit']);
    }

    public function test_print_page_shows_the_plan_with_labelled_desks(): void
    {
        $this->withPlan();
        $employee = $this->employee('Nur Aisyah', 'INF001');
        $this->desk(1, null, ['occupant_id' => $employee->id, 'plan_x' => 25, 'plan_y' => 40, 'plan_rotation' => 90]);
        $this->desk(2);

        $this->get(route('workstations.floor-plan.print', ['location' => $this->office->id, 'colour' => 'audit']))
            ->assertOk()
            ->assertSee('Infinecs P1 A: Floor Plan')
            ->assertSee('storage/' . $this->office->floor_plan_path, false)
            ->assertSee('class="by-audit', false)
            ->assertSee('rotate(90deg)', false)
            ->assertSee('Nur Aisyah')
            ->assertSee('1 of 2 desk(s) on the plan');

        $noPlan = Location::create(['name' => 'Infinecs P2', 'code' => 'P2']);
        $this->get(route('workstations.floor-plan.print', ['location' => $noPlan->id]))
            ->assertRedirect(route('workstations.floor-plan', ['location' => $noPlan->id]));
    }

    private function assetAt(Workstation $desk, array $attributes = []): \App\Models\Asset
    {
        static $n = 0;
        $n++;

        return \App\Models\Asset::create(array_merge([
            'type' => 'monitor',
            'asset_tag' => 'ISSBM' . str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'name' => 'Monitor ' . $n,
            'status' => 'in_use',
            'workstation_id' => $desk->id,
            'location_id' => $desk->location_id,
        ], $attributes));
    }

    public function test_layout_rejects_desks_from_another_location_and_bad_positions(): void
    {
        $this->withPlan();
        $mine = $this->desk(1);
        $other = $this->desk(1, Location::create(['name' => 'Infinecs P2', 'code' => 'P2']));

        $this->putJson(route('locations.floor-plan.layout', $this->office), [
            'positions' => [['id' => $mine->id, 'x' => 10, 'y' => 10], ['id' => $other->id, 'x' => 20, 'y' => 20]],
        ])->assertUnprocessable()->assertJsonValidationErrors('positions');

        $this->putJson(route('locations.floor-plan.layout', $this->office), [
            'positions' => [['id' => $mine->id, 'x' => 120, 'y' => 10]],
        ])->assertUnprocessable();

        $this->putJson(route('locations.floor-plan.layout', $this->office), [
            'positions' => [['id' => $mine->id, 'x' => 10, 'y' => null]],
        ])->assertUnprocessable();

        $this->assertFalse($mine->fresh()->isPlaced());
        $this->assertFalse($other->fresh()->isPlaced());
    }

    public function test_layout_needs_a_floor_plan(): void
    {
        $desk = $this->desk(1);

        $this->putJson(route('locations.floor-plan.layout', $this->office), [
            'positions' => [['id' => $desk->id, 'x' => 10, 'y' => 10]],
        ])->assertUnprocessable()->assertJsonValidationErrors('positions');
    }

    public function test_removing_the_floor_plan_deletes_the_image_and_unplaces_desks(): void
    {
        $path = $this->withPlan()->floor_plan_path;
        $desk = $this->desk(1, null, ['plan_x' => 10, 'plan_y' => 20, 'plan_w' => 8, 'plan_h' => 6]);

        $this->delete(route('locations.floor-plan.destroy', $this->office))->assertRedirect();

        $this->assertNull($this->office->fresh()->floor_plan_path);
        Storage::disk('public')->assertMissing($path);
        $this->assertFalse($desk->fresh()->isPlaced());
        $this->assertNull($desk->fresh()->plan_w);
    }

    public function test_moving_a_desk_to_another_location_takes_it_off_the_plan(): void
    {
        $desk = $this->desk(1, null, ['plan_x' => 10, 'plan_y' => 20]);
        $p2 = Location::create(['name' => 'Infinecs P2', 'code' => 'P2']);

        $desk->update(['location_id' => $p2->id]);
        $this->assertFalse($desk->fresh()->isPlaced());

        $desk->update(['plan_x' => 30, 'plan_y' => 40, 'notes' => 'By the window']);
        $this->assertTrue($desk->fresh()->isPlaced());
    }

    public function test_workstation_pages_link_to_the_floor_plan(): void
    {
        $desk = $this->desk(1, null, ['plan_x' => 10, 'plan_y' => 20]);

        $this->get(route('workstations.index'))->assertOk()->assertSee(route('workstations.floor-plan'), false);
        $this->get(route('workstations.show', $desk))->assertOk()->assertSee('Show on Floor Plan');
    }

    private function employee(string $name, string $idNumber, string $status = 'active'): Employee
    {
        return Employee::create(['name' => $name, 'id_number' => $idNumber, 'email' => strtolower($idNumber) . '@example.com', 'status' => $status]);
    }

    private function planData(): array
    {
        $response = $this->get(route('workstations.floor-plan', ['location' => $this->office->id]))->assertOk();
        preg_match('~<script type="application/json" id="floor-plan-data">(.*?)</script>~s', $response->getContent(), $m);

        return json_decode($m[1] ?? 'null', true);
    }

    public function test_page_lists_active_employees_and_where_they_sit(): void
    {
        $this->withPlan();
        $seated = $this->employee('Nur Aisyah', 'INF001');
        $this->employee('Ali Bakar', 'INF002');
        $this->employee('Gone Person', 'INF003', 'resigned');
        $leftButSeated = $this->employee('Still Seated', 'INF004', 'resigned');
        $desk = $this->desk(1, null, ['occupant_id' => $seated->id]);
        $this->desk(2, null, ['occupant_id' => $leftButSeated->id]);

        $data = $this->planData();

        $this->assertSame(['Ali Bakar', 'Nur Aisyah', 'Still Seated'], array_column($data['employees'], 'name'));
        $aisyah = collect($data['employees'])->firstWhere('name', 'Nur Aisyah');
        $this->assertSame([['id' => $desk->id, 'code' => 'INFP1ARAWS01']], $aisyah['desks']);
        $this->assertFalse(collect($data['employees'])->firstWhere('name', 'Still Seated')['active']);
        $this->assertSame(route('workstations.occupant', $desk), $data['desks'][0]['occupant_url']);
    }

    public function test_assigning_an_occupant_from_the_floor_plan(): void
    {
        $employee = $this->employee('Nur Aisyah', 'INF001');
        $desk = $this->desk(1, null, ['plan_x' => 10, 'plan_y' => 20]);

        $this->putJson(route('workstations.occupant', $desk), ['occupant_id' => $employee->id])
            ->assertOk()
            ->assertJsonPath('message', 'Nur Aisyah now sits at INFP1ARAWS01.')
            ->assertJsonPath('desks.0.occupant', 'Nur Aisyah')
            ->assertJsonPath('desks.0.status', 'assigned');

        $this->assertSame($employee->id, $desk->fresh()->occupant_id);
        $this->assertSame([10.0, 20.0], [$desk->fresh()->plan_x, $desk->fresh()->plan_y]);

        $this->putJson(route('workstations.occupant', $desk), ['occupant_id' => null])
            ->assertOk()
            ->assertJsonPath('message', 'INFP1ARAWS01 is now a hot desk.')
            ->assertJsonPath('desks.0.status', 'empty');
        $this->assertNull($desk->fresh()->occupant_id);
    }

    public function test_seating_someone_who_sits_elsewhere_needs_a_move(): void
    {
        $employee = $this->employee('Nur Aisyah', 'INF001');
        $old = $this->desk(1, null, ['occupant_id' => $employee->id]);
        $new = $this->desk(2);

        $this->putJson(route('workstations.occupant', $new), ['occupant_id' => $employee->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('occupant_id');
        $this->assertSame($employee->id, $old->fresh()->occupant_id);
        $this->assertNull($new->fresh()->occupant_id);

        $this->putJson(route('workstations.occupant', $new), ['occupant_id' => $employee->id, 'move' => true])
            ->assertOk()
            ->assertJsonPath('message', 'Nur Aisyah now sits at INFP1ARAWS02. INFP1ARAWS01 is now a hot desk.')
            ->assertJsonCount(2, 'desks');
        $this->assertNull($old->fresh()->occupant_id);
        $this->assertSame($employee->id, $new->fresh()->occupant_id);
    }

    public function test_only_active_employees_can_be_seated(): void
    {
        $resigned = $this->employee('Gone Person', 'INF003', 'resigned');
        $desk = $this->desk(1);

        $this->putJson(route('workstations.occupant', $desk), ['occupant_id' => $resigned->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('occupant_id');
        $this->putJson(route('workstations.occupant', $desk), ['occupant_id' => 999999])
            ->assertUnprocessable();
        $this->assertNull($desk->fresh()->occupant_id);
    }

    public function test_non_admins_cannot_use_the_floor_plan(): void
    {
        $this->withPlan();
        $desk = $this->desk(1);
        $this->actingAs(User::factory()->create(['role' => 'staff']));

        $this->get(route('workstations.floor-plan'))->assertForbidden();
        $this->get(route('workstations.floor-plan.print', ['location' => $this->office->id]))->assertForbidden();
        $this->post(route('locations.floor-plan.upload', $this->office), ['floor_plan' => UploadedFile::fake()->image('p.png')])->assertForbidden();
        $this->putJson(route('locations.floor-plan.layout', $this->office), ['positions' => [['id' => $desk->id, 'x' => 1, 'y' => 1]]])->assertForbidden();
        $this->delete(route('locations.floor-plan.destroy', $this->office))->assertForbidden();
        $this->putJson(route('workstations.occupant', $desk), ['occupant_id' => null])->assertForbidden();
    }
}
