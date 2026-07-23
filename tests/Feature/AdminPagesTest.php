<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\PropertyOwnerDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('mainAdminPageRoutes')]
    public function test_main_admin_pages_render(string $route): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route($route))
            ->assertOk();
    }

    public function test_property_show_and_edit_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $landlord = User::factory()->create(['role' => 'landlord']);
        $building = Building::create([
            'building_name' => 'Test Building',
            'address' => 'Test Address',
        ]);
        $property = Property::create([
            'landlord_id' => $landlord->id,
            'building_id' => $building->id,
            'name' => 'Test Property',
            'status' => 'vacant',
            'photos' => ['property_photos/test.jpg'],
            'amenities' => ['WiFi'],
            'security_utilities' => ['CCTV'],
            'additional_features' => ['Balcony'],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.property.show', $property->id))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.property.edit', $property->id))
            ->assertOk();
    }

    public function test_user_profile_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $landlord = User::factory()->create(['role' => 'landlord']);
        $tenant = User::factory()->create(['role' => 'tenant']);
        $agent = User::factory()->create(['role' => 'agent', 'agent_commission' => 5]);
        $maintainer = User::factory()->create(['role' => 'maintainer']);
        $building = Building::create([
            'building_name' => 'Profile Test Building',
            'address' => 'Test Address',
        ]);

        Property::create([
            'landlord_id' => $landlord->id,
            'building_id' => $building->id,
            'name' => 'Profile Test Property',
            'status' => 'rented',
            'rent' => 5500,
        ]);

        $profileRoutes = [
            route('admin.landlord.show', $landlord->id),
            route('admin.tenant.show', $tenant->id),
            route('admin.agent.show', $agent->id),
            route('admin.maintainer.show', $maintainer->id),
        ];

        foreach ($profileRoutes as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk();
        }
    }

    public function test_admin_can_record_landlord_account_entries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $landlord = User::factory()->create(['role' => 'landlord']);
        $building = Building::create([
            'building_name' => 'Ledger Test Building',
            'address' => 'Test Address',
        ]);
        $property = Property::create([
            'landlord_id' => $landlord->id,
            'building_id' => $building->id,
            'name' => 'Ledger Test Property',
            'status' => 'rented',
            'rent' => 8000,
        ]);

        $this->actingAs($admin)->post(route('admin.landlord.account-entry.store', $landlord->id), [
            'entry_date' => '2026-07-01',
            'type' => 'rent_income',
            'amount' => 8000,
            'property_id' => $property->id,
            'reference' => 'RENT-TEST',
            'description' => 'Rent received',
        ])->assertRedirect(route('admin.landlord.show', $landlord->id));

        $this->actingAs($admin)->post(route('admin.landlord.account-entry.store', $landlord->id), [
            'entry_date' => '2026-07-02',
            'type' => 'payout',
            'amount' => 5000,
            'property_id' => $property->id,
            'reference' => 'TRF-TEST',
            'description' => 'Owner payout',
        ])->assertRedirect(route('admin.landlord.show', $landlord->id));

        $this->assertDatabaseHas('landlord_account_entries', [
            'landlord_id' => $landlord->id,
            'type' => 'rent_income',
            'direction' => 'credit',
            'balance_after' => 8000,
        ]);

        $this->assertDatabaseHas('landlord_account_entries', [
            'landlord_id' => $landlord->id,
            'type' => 'payout',
            'direction' => 'debit',
            'balance_after' => 3000,
        ]);

        $this->assertSame(3000.0, (float) LandlordAccountEntry::where('landlord_id', $landlord->id)->latest('entry_date')->first()->balance_after);
    }

    public function test_admin_can_generate_and_owner_can_sign_property_documents(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $landlord = User::factory()->create([
            'role' => 'landlord',
            'eid_passport_no' => 'EID12345',
        ]);
        $building = Building::create([
            'building_name' => 'Signature Test Building',
            'address' => 'Dubai Marina',
        ]);
        $property = Property::create([
            'landlord_id' => $landlord->id,
            'building_id' => $building->id,
            'name' => 'Signature Test Unit 101',
            'status' => 'rented',
            'rent' => 9000,
            'management_fee' => 900,
            'dtcm_permit_no' => 'DTCM-TEST',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.property.owner-documents.index', $property->id))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.property.owner-documents.store', $property->id), [
                'furniture_amount' => 10000,
                'startup_dtcm_fee' => 3000,
            ])
            ->assertRedirect(route('admin.property.owner-documents.index', $property->id));

        $this->assertSame(3, PropertyOwnerDocument::where('property_id', $property->id)->count());

        $document = PropertyOwnerDocument::where('property_id', $property->id)
            ->where('type', 'management_contract')
            ->firstOrFail();

        $this->assertSame(500.0, (float) $document->vat_amount);
        $this->assertSame(13500.0, (float) $document->total_amount);

        $this->get(route('owner-documents.show', $document->signing_token))
            ->assertOk();

        $signature = 'data:image/png;base64,' . base64_encode(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        $this->post(route('owner-documents.sign', $document->signing_token), [
            'signed_by_name' => $landlord->name,
            'signature_data' => $signature,
        ])->assertRedirect(route('owner-documents.show', $document->signing_token));

        $document->refresh();

        $this->assertSame('signed', $document->status);
        $this->assertNotNull($document->signed_at);
        $this->assertNotNull($document->signed_document_path);
        Storage::disk('public')->assertExists($document->signed_document_path);
    }

    public static function mainAdminPageRoutes(): array
    {
        return [
            'dashboard' => ['admin.dashboard'],
            'landlord index' => ['admin.landlord.index'],
            'landlord grid' => ['admin.landlord.grid'],
            'landlord create' => ['admin.landlord.create'],
            'tenant index' => ['admin.tenant.index'],
            'tenant grid' => ['admin.tenant.grid'],
            'tenant create' => ['admin.tenant.create'],
            'agent index' => ['admin.agent.index'],
            'agent grid' => ['admin.agent.grid'],
            'agent create' => ['admin.agent.create'],
            'maintainer index' => ['admin.maintainer.index'],
            'maintainer grid' => ['admin.maintainer.grid'],
            'maintainer create' => ['admin.maintainer.create'],
            'property index' => ['admin.property.index'],
            'property grid' => ['admin.property.grid'],
            'property create' => ['admin.property.create'],
            'building index' => ['admin.building.index'],
        ];
    }
}
