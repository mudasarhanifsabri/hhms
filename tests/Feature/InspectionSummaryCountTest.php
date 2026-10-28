<?php

namespace Tests\Feature;

use App\Models\BookingInspection;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectionSummaryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_is_recalculated_from_fresh_item_values(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['name' => '812', 'landlord_id' => $owner->id]);
        $inspection = BookingInspection::create([
            'property_id' => $property->id,
            'inspection_number' => 'INSP-SUMMARY-TEST',
            'type' => 'check_out',
            'status' => 'draft',
            'total_items' => 2,
            'good_items' => 0,
            'issue_items' => 0,
            'na_items' => 2,
        ]);
        $good = $inspection->items()->create(['area' => 'Kitchen', 'item' => 'Fridge', 'condition' => 'na', 'sort_order' => 1]);
        $issue = $inspection->items()->create(['area' => 'Kitchen', 'item' => 'Sink', 'condition' => 'na', 'sort_order' => 2]);

        // Load the old values first, matching the maintainer submission flow.
        $inspection->load('items');
        $good->update(['condition' => 'good']);
        $issue->update(['condition' => 'issue']);
        $inspection->recalculateSummary();

        $this->assertSame(2, $inspection->total_items);
        $this->assertSame(1, $inspection->good_items);
        $this->assertSame(1, $inspection->issue_items);
        $this->assertSame(0, $inspection->na_items);
        $this->assertSame(1, $inspection->fresh()->issue_items);
    }
}
