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

    public function test_owner_sees_unit_wallet_in_desktop_and_mobile_portals(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $building = Building::create(['building_name' => 'Marina Tower', 'address' => 'Dubai Marina']);
        $property = Property::create(['landlord_id' => $owner->id, 'building_id' => $building->id, 'name' => 'Unit 3308', 'status' => 'vacant']);
        $property->unitDocuments()->create(['owner_id' => $owner->id, 'type' => 'title_deed', 'file_path' => 'documents/title.pdf']);

        $this->actingAs($owner)->get(route('landlord.dashboard'))->assertOk()->assertSee('Unit Document Wallet')->assertSee('Title Deed');
        $this->actingAs($owner)->get(route('landlord.app'))->assertOk()->assertSee('Document Wallet')->assertSee('Title Deed');
    }
}
