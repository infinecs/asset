<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Location;
use App\Models\User;
use App\Models\Workstation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkstationTest extends TestCase
{
    use RefreshDatabase;

    private Location $blockA;
    private Location $p2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->blockA = Location::create(['name' => 'Infinecs P1 A', 'code' => 'P1A']);
        $this->p2 = Location::create(['name' => 'Infinecs P2', 'code' => 'P2']);
    }

    private function workstation(int $number, ?string $room = 'RA', ?Location $location = null): Workstation
    {
        return Workstation::create(['location_id' => ($location ?? $this->blockA)->id, 'room' => $room, 'number' => $number]);
    }

    private function asset(string $type = 'monitor', array $attributes = []): Asset
    {
        static $n = 0;
        $n++;

        return Asset::create(array_merge([
            'type' => $type,
            'asset_tag' => 'ISSBM' . str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'name' => 'Asset ' . $n,
            'status' => 'available',
        ], $attributes));
    }

    private function assetForm(Asset $asset, array $overrides = []): array
    {
        return array_merge([
            'type' => $asset->type,
            'asset_tag_suffix' => substr($asset->asset_tag, 5),
            'name' => $asset->name,
            'status' => $asset->status,
            'location_id' => $asset->location_id,
            'workstation_id' => $asset->workstation_id,
        ], $overrides);
    }

    public function test_workstation_code_follows_the_office_labels(): void
    {
        $this->assertSame('INFP1ARAWS01', $this->workstation(1)->name);
        $this->assertSame('INFP1ARBWS12', $this->workstation(12, 'rb')->name, 'Room is upper-cased');
        $this->assertSame('INFP2WS03', $this->workstation(3, null, $this->p2)->name, 'Room is optional');
        $this->assertSame('INFP1ARAWS100', $this->workstation(100)->name);
    }

    public function test_creating_a_workstation_through_the_form(): void
    {
        $this->post(route('workstations.store'), ['location_id' => $this->blockA->id, 'room' => 'ra', 'number' => 7])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Workstation INFP1ARAWS07 created.');

        $this->assertDatabaseHas('workstations', ['name' => 'INFP1ARAWS07', 'room' => 'RA', 'number' => 7]);
    }

    public function test_workstation_codes_are_unique(): void
    {
        $this->workstation(1);

        $this->post(route('workstations.store'), ['location_id' => $this->blockA->id, 'room' => 'RA', 'number' => 1])
            ->assertSessionHasErrors(['number' => 'INFP1ARAWS01 already exists.']);

        $this->post(route('workstations.store'), ['location_id' => $this->blockA->id, 'room' => 'RB', 'number' => 1])
            ->assertSessionHasNoErrors();

        // Editing a workstation can keep its own code.
        $desk = Workstation::where('name', 'INFP1ARAWS01')->sole();
        $this->put(route('workstations.update', $desk), ['location_id' => $this->blockA->id, 'room' => 'RA', 'number' => 1, 'notes' => 'Window'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_location_needs_a_code_before_it_gets_workstations(): void
    {
        $noCode = Location::create(['name' => 'Off Site/WFH']);

        $this->post(route('workstations.store'), ['location_id' => $noCode->id, 'number' => 1])
            ->assertSessionHasErrors('location_id');
        $this->post(route('workstations.store'), ['mode' => 'bulk', 'location_id' => $noCode->id, 'from' => 1, 'to' => 3])
            ->assertSessionHasErrors('location_id');

        $this->assertSame(0, Workstation::count());
    }

    public function test_room_may_only_contain_letters_and_numbers(): void
    {
        $this->post(route('workstations.store'), ['location_id' => $this->blockA->id, 'room' => 'R-A', 'number' => 1])
            ->assertSessionHasErrors('room');
    }

    public function test_bulk_add_creates_a_rooms_workstations_and_skips_existing_codes(): void
    {
        $this->workstation(2);

        $this->post(route('workstations.store'), [
            'mode' => 'bulk', 'location_id' => $this->blockA->id, 'room' => 'ra', 'from' => 1, 'to' => 4,
        ])->assertRedirect()->assertSessionHas('success', '3 workstation(s) created. Skipped 1 that already existed.');

        $this->assertEqualsCanonicalizing(
            ['INFP1ARAWS01', 'INFP1ARAWS02', 'INFP1ARAWS03', 'INFP1ARAWS04'],
            Workstation::where('location_id', $this->blockA->id)->pluck('name')->all()
        );
    }

    public function test_location_codes_are_upper_cased_unique_and_rename_workstations(): void
    {
        $desk = $this->workstation(1);
        $monitor = $this->asset('monitor', ['workstation_id' => $desk->id]);
        $this->post(route('assets.move', $monitor), ['workstation_id' => null]);

        $this->put(route('locations.update', $this->blockA), ['name' => 'Infinecs P1 A', 'code' => 'p2'])
            ->assertSessionHasErrors('code');

        $this->put(route('locations.update', $this->blockA), ['name' => 'Infinecs P1 A', 'code' => 'p1b'])
            ->assertSessionHasNoErrors();

        $this->assertSame('P1B', $this->blockA->fresh()->code);
        $this->assertSame('INFP1BRAWS01', $desk->fresh()->name);
        $this->assertSame('INFP1ARAWS01', AssetMovement::sole()->from_label, 'Past moves keep the old code');
    }

    public function test_moving_a_monitor_logs_the_movement_and_follows_the_desk(): void
    {
        $from = $this->workstation(1);
        $to = $this->workstation(9, null, $this->p2);
        $monitor = $this->asset('monitor', ['workstation_id' => $from->id, 'location_id' => $this->blockA->id, 'status' => 'in_use']);

        $this->post(route('assets.move', $monitor), ['workstation_id' => $to->id, 'notes' => 'Desk swap'])
            ->assertSessionHas('success');

        $monitor->refresh();
        $this->assertSame($to->id, $monitor->workstation_id);
        $this->assertSame($this->p2->id, $monitor->location_id);

        $movement = AssetMovement::sole();
        $this->assertSame($from->id, $movement->from_workstation_id);
        $this->assertSame($to->id, $movement->to_workstation_id);
        $this->assertSame('INFP1ARAWS01', $movement->from_label);
        $this->assertSame('INFP2WS09', $movement->to_label);
        $this->assertSame('Desk swap', $movement->notes);

        $this->assertDatabaseHas('asset_histories', [
            'asset_id' => $monitor->id,
            'action' => 'moved',
            'notes' => 'Moved from INFP1ARAWS01 to INFP2WS09: Desk swap',
        ]);
    }

    public function test_placing_an_available_asset_marks_it_in_use_and_removing_frees_it(): void
    {
        $desk = $this->workstation(1);
        $monitor = $this->asset('monitor');

        $this->post(route('workstations.place-asset', $desk), ['asset_id' => $monitor->id])->assertSessionHas('success');
        $this->assertSame('in_use', $monitor->fresh()->status);
        $this->assertSame($desk->id, $monitor->fresh()->workstation_id);

        $this->post(route('assets.move', $monitor), ['workstation_id' => null])->assertSessionHas('success');
        $monitor->refresh();
        $this->assertNull($monitor->workstation_id);
        $this->assertSame('available', $monitor->status);
        $this->assertSame($this->blockA->id, $monitor->location_id, 'Location is kept when taken off the desk');

        $this->assertSame(2, AssetMovement::count());
        $this->assertNull(AssetMovement::latest('id')->first()->to_workstation_id);
    }

    public function test_removing_an_asset_that_is_also_assigned_to_someone_keeps_it_in_use(): void
    {
        $desk = $this->workstation(1);
        $monitor = $this->asset('monitor', ['workstation_id' => $desk->id, 'status' => 'in_use', 'assigned_to_other' => 'Reception']);

        $this->post(route('assets.move', $monitor), ['workstation_id' => null]);

        $this->assertSame('in_use', $monitor->fresh()->status);
    }

    public function test_portable_assets_cannot_be_placed_at_a_workstation(): void
    {
        $desk = $this->workstation(1);
        $laptop = $this->asset('laptop');

        $this->post(route('assets.move', $laptop), ['workstation_id' => $desk->id])->assertSessionHas('error');
        $this->post(route('workstations.place-asset', $desk), ['asset_id' => $laptop->id])->assertSessionHas('error');

        $this->assertNull($laptop->fresh()->workstation_id);
        $this->assertSame(0, AssetMovement::count());
    }

    public function test_scanning_places_several_assets_at_a_desk(): void
    {
        $desk = $this->workstation(1);
        $other = $this->workstation(2);
        $monitorA = $this->asset('monitor');
        $monitorB = $this->asset('monitor', ['workstation_id' => $other->id, 'status' => 'in_use']);
        $laptop = $this->asset('laptop');

        $this->post(route('workstations.place-asset', $desk), ['asset_ids' => [$monitorA->id, $monitorB->id, $laptop->id], 'notes' => 'New desk setup'])
            ->assertSessionHas('success', "2 assets placed at INFP1ARAWS01: {$monitorA->asset_tag}, {$monitorB->asset_tag}. Skipped {$laptop->asset_tag} (only desktops, monitors and speakerphones can be placed at a desk).");

        $this->assertSame($desk->id, $monitorA->fresh()->workstation_id);
        $this->assertSame($desk->id, $monitorB->fresh()->workstation_id);
        $this->assertNull($laptop->fresh()->workstation_id);
        $this->assertSame(2, AssetMovement::where('to_workstation_id', $desk->id)->where('notes', 'New desk setup')->count());
        $this->assertSame(1, AssetMovement::where('from_workstation_id', $other->id)->count());

        // Scanning them again changes nothing.
        $this->post(route('workstations.place-asset', $desk), ['asset_ids' => [$monitorA->id, $monitorB->id]])
            ->assertSessionHas('error', 'Nothing to place: they are already at INFP1ARAWS01.');
        $this->assertSame(2, AssetMovement::count());

        $this->post(route('workstations.place-asset', $desk), ['asset_ids' => range(1, 51)])->assertSessionHasErrors('asset_ids');
        $this->post(route('workstations.place-asset', $desk), [])->assertSessionHasErrors(['asset_id', 'asset_ids']);
    }

    public function test_desk_page_offers_equipment_scanning(): void
    {
        $desk = $this->workstation(1);
        $atDesk = $this->asset('monitor', ['workstation_id' => $desk->id]);
        $elsewhere = $this->asset('monitor', ['workstation_id' => $this->workstation(2)->id]);
        $this->asset('monitor', ['status' => 'retired']);

        $html = $this->get(route('workstations.show', $desk))->assertOk()
            ->assertSee('Scan asset labels')
            ->assertSee('Scan equipment')
            ->getContent();

        $this->assertSame(1, preg_match('/const placeable = (\[.*?\]);\n/', $html, $m));
        $placeable = json_decode($m[1], true);
        $this->assertSame([$elsewhere->id], array_column($placeable, 'id'), 'Only in-service fixed assets not already here');
        $this->assertSame('INFP1ARAWS02', $placeable[0]['from']);
        $this->assertSame(1, preg_match('/const here = (\[.*?\]);\n/', $html, $m));
        $this->assertSame([$atDesk->id], array_column(json_decode($m[1], true), 'id'));
        $this->assertSame(1, substr_count($html, 'id="qr-scanner"'), 'One camera overlay shared by both scanners');
    }

    public function test_moving_to_the_same_workstation_is_not_logged(): void
    {
        $desk = $this->workstation(1);
        $monitor = $this->asset('monitor', ['workstation_id' => $desk->id]);

        $this->post(route('assets.move', $monitor), ['workstation_id' => $desk->id])->assertSessionHas('error');

        $this->assertSame(0, AssetMovement::count());
    }

    public function test_creating_an_asset_at_a_workstation_logs_its_placement(): void
    {
        $desk = $this->workstation(1, null, $this->p2);

        $this->post(route('assets.store'), [
            'type' => 'monitor', 'asset_tag_suffix' => '500', 'name' => 'Monitor 500', 'status' => 'available',
            'location_id' => $this->blockA->id, 'workstation_id' => $desk->id,
        ])->assertSessionHasNoErrors();

        $monitor = Asset::where('asset_tag', 'ISSBM500')->sole();
        $this->assertSame($desk->id, $monitor->workstation_id);
        $this->assertSame($this->p2->id, $monitor->location_id, 'Location comes from the workstation');
        $this->assertSame('in_use', $monitor->status);

        $movement = AssetMovement::sole();
        $this->assertNull($movement->from_workstation_id);
        $this->assertSame($desk->id, $movement->to_workstation_id);
    }

    public function test_changing_the_workstation_on_the_edit_form_logs_a_movement(): void
    {
        $from = $this->workstation(1);
        $to = $this->workstation(2);
        $monitor = $this->asset('monitor', ['workstation_id' => $from->id, 'location_id' => $this->blockA->id, 'status' => 'in_use']);

        $this->put(route('assets.update', $monitor), $this->assetForm($monitor, ['workstation_id' => $to->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($to->id, $monitor->fresh()->workstation_id);
        $movement = AssetMovement::sole();
        $this->assertSame($from->id, $movement->from_workstation_id);
        $this->assertSame($to->id, $movement->to_workstation_id);

        // An unrelated edit doesn't log another move.
        $this->put(route('assets.update', $monitor), $this->assetForm($monitor->fresh(), ['name' => 'Renamed']));
        $this->assertSame(1, AssetMovement::count());
    }

    public function test_changing_an_asset_to_a_portable_type_takes_it_off_its_workstation(): void
    {
        $desk = $this->workstation(1);
        $asset = $this->asset('monitor', ['workstation_id' => $desk->id, 'status' => 'in_use']);

        $this->put(route('assets.update', $asset), $this->assetForm($asset, ['type' => 'laptop']))
            ->assertSessionHasNoErrors();

        $this->assertNull($asset->fresh()->workstation_id);
        $this->assertNull(AssetMovement::sole()->to_workstation_id);
    }

    public function test_moving_a_workstation_to_another_location_moves_its_equipment_and_renames_it(): void
    {
        $desk = $this->workstation(1);
        $monitor = $this->asset('monitor', ['workstation_id' => $desk->id, 'location_id' => $this->blockA->id]);

        $this->put(route('workstations.update', $desk), ['location_id' => $this->p2->id, 'room' => 'RA', 'number' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame('INFP2RAWS01', $desk->fresh()->name);
        $this->assertSame($this->p2->id, $monitor->fresh()->location_id);
    }

    public function test_workstations_and_locations_in_use_cannot_be_deleted(): void
    {
        $desk = $this->workstation(1);
        $this->asset('monitor', ['workstation_id' => $desk->id]);

        $this->delete(route('workstations.destroy', $desk))->assertSessionHas('error');
        $this->assertModelExists($desk);

        $this->delete(route('locations.destroy', $this->blockA))->assertSessionHas('error');
        $this->assertModelExists($this->blockA);

        $this->delete(route('locations.destroy-all'))->assertSessionHas('success', '1 location(s) deleted successfully. 1 location(s) with workstations were kept.');
        $this->assertModelExists($this->blockA);
        $this->assertModelMissing($this->p2);
    }

    public function test_deleting_a_workstation_keeps_its_movement_log_readable(): void
    {
        $desk = $this->workstation(1);
        $monitor = $this->asset('monitor', ['workstation_id' => $desk->id]);
        $this->post(route('assets.move', $monitor), ['workstation_id' => null]);

        $this->delete(route('workstations.destroy', $desk))->assertRedirect(route('workstations.index'));

        $this->get(route('workstations.movements'))->assertOk()->assertSee('INFP1ARAWS01');
    }

    public function test_pages_render(): void
    {
        $desk = $this->workstation(1);
        $other = $this->workstation(2, 'RB');
        $monitor = $this->asset('monitor', ['workstation_id' => $desk->id]);
        $laptop = $this->asset('laptop');
        $this->post(route('assets.move', $monitor), ['workstation_id' => $other->id]);

        $this->get(route('workstations.index'))->assertOk()->assertSeeInOrder(['INFP1ARAWS01', 'INFP1ARBWS02'])->assertSee($monitor->asset_tag);
        $this->get(route('workstations.index', ['room' => 'RB']))->assertOk()->assertSee('INFP1ARBWS02')->assertDontSee('INFP1ARAWS01');
        $this->get(route('workstations.index', ['occupancy' => 'empty']))->assertOk()->assertSee('INFP1ARAWS01')->assertDontSee('INFP1ARBWS02');
        $this->get(route('workstations.create'))->assertOk();
        $this->get(route('workstations.edit', $desk))->assertOk();
        $this->get(route('workstations.show', $desk))->assertOk()->assertSee('Movement History')->assertSee('to INFP1ARBWS02');
        $this->get(route('workstations.movements', ['search' => $monitor->asset_tag]))->assertOk()->assertSee('INFP1ARAWS01');
        $this->get(route('assets.show', $monitor))->assertOk()->assertSee('Move Asset')->assertSee('Recent moves');
        $this->get(route('assets.show', $laptop))->assertOk()->assertDontSee('Move Asset');
        $this->get(route('assets.edit', $monitor))->assertOk()->assertSee('asset_workstation', false);
        $this->get(route('assets.create'))->assertOk();
        $this->get(route('assets.index'))->assertOk();
        $this->get(route('locations.index'))->assertOk()->assertSee('P1A');
        $this->get(route('locations.edit', $this->blockA))->assertOk();
    }

    public function test_workstation_picker_cascades_office_room_desk(): void
    {
        $desk = $this->workstation(1);
        $this->workstation(2, 'RB');
        $this->workstation(3, null, $this->p2);
        $monitor = $this->asset('monitor', ['workstation_id' => $desk->id]);

        $tree = function (string $html): array {
            $this->assertSame(1, substr_count($html, 'const tree = '), 'Picker data is on the page once');
            preg_match('/const tree = (\[.*?\]);\n/s', $html, $m);

            return json_decode($m[1], true);
        };

        $offices = $tree($this->get(route('assets.edit', $monitor))->assertOk()
            ->assertSee('data-selected="' . $desk->id . '"', false)
            ->getContent());

        $this->assertSame(['Infinecs P1 A (P1A)', 'Infinecs P2 (P2)'], array_column($offices, 'label'));
        $this->assertSame(['RA', 'RB'], array_column($offices[0]['rooms'], 'room'));
        $this->assertSame('', $offices[1]['rooms'][0]['room'], 'Desks without a room get their own group');
        $this->assertSame(['id' => $desk->id, 'code' => 'INFP1ARAWS01', 'text' => 'WS01 · 1 asset'], $offices[0]['rooms'][0]['desks'][0]);

        // The move form hides the asset's current desk, and offers the desk scanner.
        $html = $this->get(route('assets.show', $monitor))->assertOk()
            ->assertSee('data-exclude="' . $desk->id . '"', false)
            ->assertSee('data-current-workstation="' . $desk->id . '"', false)
            ->assertSee('data-ws-scan="asset-move-form"', false)
            ->getContent();
        $this->assertSame(1, substr_count($html, 'id="qr-scanner"'));

        // One scanner and one copy of the picker data, however many move forms the page has.
        $this->asset('monitor', ['workstation_id' => $desk->id]);
        $html = $this->get(route('workstations.show', $desk))->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'data-ws-scan class='), 'A scan button in each asset\'s move form');
        $this->assertSame(1, substr_count($html, 'id="qr-scanner"'));
        $this->assertSame(1, substr_count($html, 'const tree = '));
    }

    public function test_desk_audit_reconciles_missing_and_found_equipment(): void
    {
        $desk = $this->workstation(1);
        $elsewhere = $this->workstation(2);
        $present = $this->asset('monitor', ['workstation_id' => $desk->id, 'status' => 'in_use']);
        $missing = $this->asset('monitor', ['workstation_id' => $desk->id, 'status' => 'in_use']);
        $strayed = $this->asset('monitor', ['workstation_id' => $elsewhere->id, 'status' => 'in_use']);
        $lost = $this->asset('desktop', ['status' => 'lost']);

        $this->post(route('workstations.audit.store', $desk), [
            'present' => [$present->id],
            'found' => [$strayed->id, $lost->id],
            'notes' => 'Quarterly check',
        ])->assertRedirect(route('workstations.show', $desk))
            ->assertSessionHas('success', 'Audit of INFP1ARAWS01 saved: 1 present, 1 missing, 2 found.');

        $this->assertSame($desk->id, $present->fresh()->workstation_id);

        // Missing: off the desk, but not "available": nobody knows where it is yet.
        $this->assertNull($missing->fresh()->workstation_id);
        $this->assertSame('in_use', $missing->fresh()->status);

        $this->assertSame($desk->id, $strayed->fresh()->workstation_id);
        $this->assertSame('in_use', $lost->fresh()->status, 'A lost asset found at a desk is back in use');

        $this->assertDatabaseHas('asset_movements', ['asset_id' => $strayed->id, 'from_label' => 'INFP1ARAWS02', 'to_label' => 'INFP1ARAWS01', 'notes' => 'Found here during desk audit']);
        $this->assertDatabaseHas('asset_movements', ['asset_id' => $missing->id, 'to_workstation_id' => null, 'notes' => 'Not found during desk audit']);

        $audit = $desk->audits()->first();
        $this->assertSame(1, $audit->present_count);
        $this->assertSame([$missing->asset_tag], array_column($audit->missing, 'tag'));
        $this->assertSame('INFP1ARAWS02', collect($audit->found)->firstWhere('id', $strayed->id)['from']);
        $this->assertTrue($audit->hasDiscrepancies());

        $this->get(route('workstations.show', $desk))->assertOk()
            ->assertSee('1 missing')->assertSee('2 found')->assertSee('Quarterly check');
    }

    public function test_a_clean_audit_changes_nothing_but_clears_the_audit_due_flag(): void
    {
        $desk = $this->workstation(1);
        $monitor = $this->asset('monitor', ['workstation_id' => $desk->id, 'status' => 'in_use']);
        $this->assertTrue($desk->isAuditDue(), 'Never audited counts as due');

        $this->post(route('workstations.audit.store', $desk), ['present' => [$monitor->id]])
            ->assertSessionHas('success', 'Audit of INFP1ARAWS01 saved: 1 present, 0 missing, 0 found.');

        $this->assertSame($desk->id, $monitor->fresh()->workstation_id);
        $this->assertSame(0, AssetMovement::count());
        $this->assertFalse($desk->fresh()->load('latestAudit')->isAuditDue());
    }

    public function test_audit_rejects_portable_assets_as_found(): void
    {
        $desk = $this->workstation(1);
        $laptop = $this->asset('laptop');

        $this->post(route('workstations.audit.store', $desk), ['found' => [$laptop->id]])->assertSessionHasErrors('found');

        $this->assertNull($laptop->fresh()->workstation_id);
        $this->assertSame(0, $desk->audits()->count());
    }

    public function test_list_can_be_filtered_by_audit_status(): void
    {
        $never = $this->workstation(1);
        $stale = $this->workstation(2);
        $recent = $this->workstation(3);
        $stale->audits()->create(['present_count' => 0])->forceFill(['created_at' => now()->subDays(Workstation::AUDIT_INTERVAL_DAYS + 1)])->save();
        $recent->audits()->create(['present_count' => 0]);

        $this->get(route('workstations.index', ['audit' => 'due']))->assertOk()
            ->assertSee('INFP1ARAWS01')->assertSee('INFP1ARAWS02')->assertDontSee('INFP1ARAWS03');
        $this->get(route('workstations.index', ['audit' => 'never']))->assertOk()
            ->assertSee('INFP1ARAWS01')->assertDontSee('INFP1ARAWS02')->assertDontSee('INFP1ARAWS03');
        $this->get(route('workstations.index'))->assertSee("2 workstation(s) haven't been audited", false);
    }

    public function test_room_view_groups_desks_by_office_and_room(): void
    {
        $this->workstation(1);
        $this->workstation(2, 'RB');
        $this->workstation(1, null, $this->p2);
        $this->asset('monitor', ['workstation_id' => $this->workstation(3)->id]);

        $this->get(route('workstations.index', ['view' => 'rooms']))->assertOk()
            ->assertSeeInOrder(['Infinecs P1 A', 'Room RA', 'WS01', 'WS03', 'Room RB', 'WS02', 'Infinecs P2', 'No room', 'WS01']);
        $this->get(route('workstations.index', ['view' => 'rooms', 'room' => 'RB']))->assertOk()
            ->assertSee('Room RB')->assertDontSee('Room RA');
    }

    public function test_workstations_and_movements_export_as_csv(): void
    {
        $desk = $this->workstation(1);
        $this->workstation(2, 'RB');
        $monitor = $this->asset('monitor');
        $this->post(route('workstations.place-asset', $desk), ['asset_id' => $monitor->id, 'notes' => 'New hire']);

        $csv = $this->get(route('workstations.export', ['room' => 'RA']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Code,Location,Room', $csv);
        $this->assertStringContainsString('INFP1ARAWS01,"Infinecs P1 A",RA,1', $csv);
        $this->assertStringContainsString("{$monitor->asset_tag} (monitor)", $csv);
        $this->assertStringNotContainsString('INFP1ARBWS02', $csv);

        $log = $this->get(route('workstations.movements.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("{$monitor->asset_tag},", $log);
        $this->assertStringContainsString('Storage,INFP1ARAWS01,"New hire"', $log);
    }

    public function test_qr_labels_print_for_one_desk_or_a_filtered_set(): void
    {
        $desk = $this->workstation(1);
        $this->workstation(2);
        $this->workstation(1, 'RB');

        $this->get(route('workstations.label', $desk))->assertOk()
            ->assertSee('INFP1ARAWS01')->assertSee('<svg', false)->assertSee('1 label(s)');
        $this->get(route('workstations.labels', ['room' => 'RA']))->assertOk()
            ->assertSee('INFP1ARAWS01')->assertSee('INFP1ARAWS02')->assertDontSee('INFP1ARBWS01')->assertSee('2 label(s)');
        $this->get(route('workstations.labels', ['room' => 'ZZ']))->assertRedirect()->assertSessionHas('error');
    }

    public function test_audit_page_and_employee_page_show_the_desk(): void
    {
        $employee = \App\Models\Employee::create(['name' => 'Jane Doe', 'id_number' => 'INF001', 'email' => 'jane@example.com', 'status' => 'active']);
        $desk = Workstation::create(['location_id' => $this->blockA->id, 'room' => 'RA', 'number' => 5, 'occupant_id' => $employee->id]);
        $monitor = $this->asset('monitor', ['workstation_id' => $desk->id]);
        $this->asset('monitor');

        $this->get(route('workstations.audit', $desk))->assertOk()
            ->assertSee('INFP1ARAWS05')->assertSee($monitor->asset_tag)->assertSee('Save Audit');
        $this->get(route('employees.show', $employee))->assertOk()
            ->assertSee('INFP1ARAWS05')->assertSee($monitor->asset_tag);
    }

    public function test_non_admins_cannot_manage_workstations(): void
    {
        $desk = $this->workstation(1);
        $monitor = $this->asset('monitor');
        $this->actingAs(User::factory()->create(['role' => 'staff']));

        $this->get(route('workstations.index'))->assertForbidden();
        $this->get(route('workstations.show', $desk))->assertForbidden();
        $this->get(route('workstations.movements'))->assertForbidden();
        $this->post(route('workstations.store'), ['location_id' => $this->blockA->id, 'number' => 99])->assertForbidden();
        $this->post(route('assets.move', $monitor), ['workstation_id' => $desk->id])->assertForbidden();
        $this->post(route('workstations.place-asset', $desk), ['asset_id' => $monitor->id])->assertForbidden();
        $this->get(route('workstations.audit', $desk))->assertForbidden();
        $this->post(route('workstations.audit.store', $desk), ['found' => [$monitor->id]])->assertForbidden();
        $this->get(route('workstations.export'))->assertForbidden();
        $this->get(route('workstations.movements.export'))->assertForbidden();
        $this->get(route('workstations.labels'))->assertForbidden();
        $this->get(route('workstations.label', $desk))->assertForbidden();

        $this->assertNull($monitor->fresh()->workstation_id);
    }
}
