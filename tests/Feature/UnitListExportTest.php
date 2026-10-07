<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitListExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_units_list_exports_excel_and_pdf_with_statement_balances(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord', 'name' => 'Export Owner']);
        $building = Building::create(['building_name' => 'Export Tower', 'address' => 'Dubai']);
        $property = Property::create(['landlord_id' => $owner->id, 'building_id' => $building->id, 'name' => 'Unit 1201', 'status' => 'vacant', 'rent' => 12000]);
        Property::create(['landlord_id' => $owner->id, 'building_id' => $building->id, 'name' => 'Hidden Unit', 'status' => 'rented']);

        LandlordAccountEntry::create(['landlord_id' => $owner->id, 'property_id' => $property->id, 'entry_date' => '2026-10-01', 'type' => 'adjustment_credit', 'direction' => 'credit', 'amount' => 5000, 'description' => 'Opening owner credit']);
        LandlordAccountEntry::create(['landlord_id' => $owner->id, 'property_id' => $property->id, 'entry_date' => '2026-10-02', 'type' => 'maintenance', 'direction' => 'debit', 'amount' => 750, 'description' => 'Repair charge']);

        $excel = $this->actingAs($admin)->get(route('admin.property.export.excel', ['status' => 'available']));
        $excel->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $excel->streamedContent();
        $this->assertStringContainsString('Unit 1201', $content);
        $this->assertStringContainsString('4250', $content);
        $this->assertStringContainsString('Due to Owner', $content);
        $this->assertStringNotContainsString('Hidden Unit', $content);

        $this->actingAs($admin)->get(route('admin.property.export.pdf', ['status' => 'available']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="units-statement-balances-'.now()->format('Y-m-d').'.pdf"');
    }
}
