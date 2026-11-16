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

    public function test_non_admins_cannot_use_the_floor_plan(): void
    {
        $this->withPlan();
        $desk = $this->desk(1);
        $this->actingAs(User::factory()->create(['role' => 'staff']));

        $this->get(route('workstations.floor-plan'))->assertForbidden();
        $this->post(route('locations.floor-plan.upload', $this->office), ['floor_plan' => UploadedFile::fake()->image('p.png')])->assertForbidden();
        $this->putJson(route('locations.floor-plan.layout', $this->office), ['positions' => [['id' => $desk->id, 'x' => 1, 'y' => 1]]])->assertForbidden();
        $this->delete(route('locations.floor-plan.destroy', $this->office))->assertForbidden();
    }
}
