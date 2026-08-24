<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyFinancialDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_can_be_created_when_optional_financial_values_are_empty(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $building = Building::create(['building_name' => 'Test Tower', 'address' => 'Dubai']);

        $this->actingAs($admin)->post(route('admin.property.store'), [
            'landlord_id' => $owner->id,
            'building_id' => $building->id,
            'name' => 'Unit 1113',
            'category' => '1 BHK',
            'utilities_cap' => '',
            'management_fee_percent' => '',
        ])->assertRedirect(route('admin.property.index'))->assertSessionHasNoErrors();

        $property = Property::where('name', 'Unit 1113')->firstOrFail();
        $this->assertSame('0.00', $property->utilities_cap);
        $this->assertSame('0.00', $property->management_fee_percent);
    }
}
