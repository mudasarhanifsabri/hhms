<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Property;
use App\Models\UnitDocument;
use App\Models\User;
use App\Notifications\DtcmPermitExpiring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DtcmPermitMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_urgent_dtcm_permits(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $building = Building::create(['building_name' => 'Marina Tower', 'address' => 'Dubai']);
        $urgent = Property::create(['landlord_id' => $owner->id, 'building_id' => $building->id, 'name' => 'Unit 101', 'status' => 'vacant']);
        $valid = Property::create(['landlord_id' => $owner->id, 'building_id' => $building->id, 'name' => 'Unit 202', 'status' => 'vacant']);
        $urgent->unitDocuments()->create(['type' => 'dtcm_permit', 'reference_no' => 'DTCM-URGENT', 'expires_at' => today()->addDays(7), 'file_path' => 'urgent.pdf']);
        $valid->unitDocuments()->create(['type' => 'dtcm_permit', 'reference_no' => 'DTCM-VALID', 'expires_at' => today()->addDays(60), 'file_path' => 'valid.pdf']);

        $this->actingAs($admin)->get(route('admin.property.dtcm-permits', ['status' => 'urgent']))
            ->assertOk()->assertSee('DTCM-URGENT')->assertDontSee('DTCM-VALID');
    }

    public function test_seven_day_reminder_is_sent_once_per_expiry_date(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit 303', 'status' => 'vacant']);
        $permit = $property->unitDocuments()->create(['type' => 'dtcm_permit', 'expires_at' => today()->addDays(7), 'file_path' => 'permit.pdf']);

        Artisan::call('dtcm:send-expiry-reminders');
        Artisan::call('dtcm:send-expiry-reminders');

        Notification::assertSentToTimes($admin, DtcmPermitExpiring::class, 1);
        $this->assertTrue($permit->fresh()->expiry_reminder_sent_for->equalTo($permit->expires_at));
    }

    public function test_owner_cannot_open_admin_dtcm_list(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $this->actingAs($owner)->get(route('admin.property.dtcm-permits'))->assertForbidden();
    }
}
