<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildingCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_view_edit_update_and_delete_an_empty_building(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.building.store'), [
            'building_name' => 'CRUD Tower',
            'management_email' => 'management@example.com',
            'address' => 'Downtown Dubai',
            'city' => 'Dubai',
            'country' => 'UAE',
            'year_built' => 2024,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.building.index'));

        $building = Building::where('building_name', 'CRUD Tower')->firstOrFail();
        $this->get(route('admin.building.show', $building))->assertOk()->assertSee('management@example.com');
        $this->get(route('admin.building.edit', $building))->assertOk()->assertSee('CRUD Tower');

        $this->put(route('admin.building.update', $building), [
            'building_name' => 'Updated CRUD Tower',
            'management_email' => 'updated@example.com',
            'address' => 'Business Bay',
            'city' => 'Dubai',
            'country' => 'UAE',
            'year_built' => 2025,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.building.index'));

        $this->assertDatabaseHas('buildings', ['id' => $building->id, 'building_name' => 'Updated CRUD Tower', 'management_email' => 'updated@example.com']);
        $this->delete(route('admin.building.destroy', $building))->assertSessionHasNoErrors()->assertRedirect(route('admin.building.index'));
        $this->assertDatabaseMissing('buildings', ['id' => $building->id]);
    }

    public function test_building_with_units_cannot_be_deleted_and_landlord_lookup_uses_linked_units(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $building = Building::create(['building_name' => 'Occupied Tower', 'address' => 'Dubai']);
        Property::create(['landlord_id' => $owner->id, 'building_id' => $building->id, 'name' => '101', 'status' => 'vacant']);

        $this->actingAs($admin)->delete(route('admin.building.destroy', $building))
            ->assertSessionHasErrors('building');
        $this->assertDatabaseHas('buildings', ['id' => $building->id]);

        $this->get('/admin/buildings/by-landlord/'.$owner->id)
            ->assertOk()->assertJsonFragment(['id' => $building->id, 'building_name' => 'Occupied Tower']);
    }

    public function test_building_list_searches_management_email_and_shows_working_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $building = Building::create([
            'building_name' => 'Searchable Tower',
            'management_email' => 'special-management@example.com',
            'address' => 'Dubai',
        ]);

        $this->actingAs($admin)->get(route('admin.building.index', ['search' => 'special-management']))
            ->assertOk()
            ->assertSee(route('admin.building.show', $building))
            ->assertSee(route('admin.building.edit', $building))
            ->assertSee(route('admin.building.destroy', $building));
    }
}
