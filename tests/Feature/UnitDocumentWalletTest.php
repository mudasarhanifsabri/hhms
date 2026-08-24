<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Property;
use App\Models\UnitDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnitDocumentWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_document_with_expiry_to_unit_wallet(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit 3308', 'status' => 'vacant']);

        $this->actingAs($admin)->post(route('admin.property.document-wallet.store', $property), [
            'type' => 'insurance', 'owner_id' => $owner->id,
            'reference_no' => 'INS-3308', 'issue_date' => '2026-01-01',
            'expires_at' => '2027-01-01',
            'document' => UploadedFile::fake()->create('insurance.pdf', 100, 'application/pdf'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $document = UnitDocument::firstOrFail();
        $this->assertSame('Insurance', $document->title);
        $this->assertSame('INS-3308', $document->reference_no);
        Storage::disk('public')->assertExists($document->file_path);
    }

    public function test_custom_document_requires_a_custom_title(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit 402', 'status' => 'vacant']);

        $this->actingAs($admin)->post(route('admin.property.document-wallet.store', $property), [
            'type' => 'custom',
            'document' => UploadedFile::fake()->create('other.pdf', 50, 'application/pdf'),
        ])->assertSessionHasErrors('custom_title');
    }

    public function test_expiry_defaults_to_one_year_after_issue_date(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit 505', 'status' => 'vacant']);

        $this->actingAs($admin)->post(route('admin.property.document-wallet.store', $property), [
            'type' => 'dtcm_permit',
            'issue_date' => '2026-08-24',
            'document' => UploadedFile::fake()->create('permit.pdf', 50, 'application/pdf'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('2027-08-23', UnitDocument::firstOrFail()->expires_at->toDateString());
    }

    public function test_owner_sees_unit_wallet_in_desktop_and_mobile_portals(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $building = Building::create(['building_name' => 'Marina Tower', 'address' => 'Dubai Marina']);
        $property = Property::create(['landlord_id' => $owner->id, 'building_id' => $building->id, 'name' => 'Unit 3308', 'status' => 'vacant']);
        $property->unitDocuments()->create(['owner_id' => $owner->id, 'type' => 'title_deed', 'file_path' => 'documents/title.pdf']);

        $this->actingAs($owner)->get(route('landlord.dashboard'))->assertOk()->assertSee('Unit Document Wallet')->assertSee('Title Deed');
        $this->actingAs($owner)->get(route('landlord.app'))->assertOk()->assertSee('Document Wallet')->assertSee('Title Deed');
    }

    public function test_management_contract_dates_sync_to_noc_and_management_letter(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit 606', 'status' => 'vacant']);
        $noc = $property->unitDocuments()->create(['type' => 'noc', 'file_path' => 'noc.pdf']);
        $letter = $property->unitDocuments()->create(['type' => 'management_letter', 'file_path' => 'letter.pdf']);

        $this->actingAs($admin)->post(route('admin.property.document-wallet.store', $property), [
            'type' => 'management_contract',
            'issue_date' => '2026-08-24',
            'expires_at' => '2027-08-23',
            'document' => UploadedFile::fake()->create('contract.pdf', 50, 'application/pdf'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        foreach ([$noc->fresh(), $letter->fresh()] as $document) {
            $this->assertSame('2026-08-24', $document->issue_date->toDateString());
            $this->assertSame('2027-08-23', $document->expires_at->toDateString());
        }
    }

    public function test_management_contract_update_resyncs_existing_document_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => 'Unit 808', 'status' => 'vacant']);
        $noc = $property->unitDocuments()->create(['type' => 'noc', 'issue_date' => '2020-01-01', 'expires_at' => '2020-12-31', 'file_path' => 'noc.pdf']);
        $letter = $property->unitDocuments()->create(['type' => 'management_letter', 'issue_date' => '2020-01-01', 'expires_at' => '2020-12-31', 'file_path' => 'letter.pdf']);
        $contract = $property->unitDocuments()->create(['type' => 'management_contract', 'issue_date' => '2021-01-01', 'expires_at' => '2021-12-31', 'file_path' => 'contract.pdf']);

        $this->actingAs($admin)->put(route('admin.property.document-wallet.update', [$property, $contract]), [
            'type' => 'management_contract',
            'issue_date' => '2026-09-01',
            'expires_at' => '2027-08-31',
        ])->assertRedirect()->assertSessionHasNoErrors();

        foreach ([$noc->fresh(), $letter->fresh()] as $document) {
            $this->assertSame('2026-09-01', $document->issue_date->toDateString());
            $this->assertSame('2027-08-31', $document->expires_at->toDateString());
        }
    }
}
