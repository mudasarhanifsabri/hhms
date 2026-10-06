<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\UnitDocument;
use App\Models\UnitManagementCancellation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnitManagementCancellationTest extends TestCase
{
    use RefreshDatabase;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL5WQAAAABJRU5ErkJggg==';

    public function test_admin_can_create_and_track_a_cancellation(): void
    {
        Mail::fake();
        [$admin, $owner, $property] = $this->records();

        $this->actingAs($admin)->post(route('admin.property.cancellations.store', $property), [
            'owner_id' => $owner->id,
            'owner_email' => 'owner@example.com',
            'letter_date' => '2026-10-05',
            'effective_date' => '2026-10-31',
            'property_description' => 'Unit 501, Multaqa Avenue 2, Mirdif, Dubai',
            'company_signer_name' => 'Authorized Manager',
            'company_signer_email' => 'manager@pattern.ae',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $cancellation = UnitManagementCancellation::firstOrFail();
        $this->assertSame('sent_to_owner', $cancellation->status);
        $this->assertNotEmpty($cancellation->owner_token);
        $this->assertNotEmpty($cancellation->company_token);

        $this->actingAs($admin)
            ->get(route('admin.property.cancellations.show', [$property, $cancellation]))
            ->assertOk()
            ->assertSee('Cancellation Tracking')
            ->assertSee('Copy Link');
    }

    public function test_owner_and_company_sign_in_order_and_generate_final_pdf(): void
    {
        Mail::fake();
        Storage::fake('public');
        config(['hhms.media_disk' => 'public']);
        [, $owner, $property] = $this->records();
        $cancellation = $this->cancellation($property, $owner);

        $this->post(route('unit-cancellations.sign', $cancellation->company_token), [
            'signed_by_name' => 'Authorized Manager',
            'signature_data' => self::SIGNATURE,
            'accepted' => '1',
        ])->assertSessionHasErrors('signature_data');

        $this->get(route('unit-cancellations.show', $cancellation->owner_token))
            ->assertOk()->assertSee('height:420px', false)->assertSee('Owner Signature');
        $this->post(route('unit-cancellations.sign', $cancellation->owner_token), [
            'signed_by_name' => $owner->name,
            'signature_data' => self::SIGNATURE,
            'accepted' => '1',
        ])->assertRedirect();

        $this->assertSame('awaiting_company_signature', $cancellation->fresh()->status);
        $this->post(route('unit-cancellations.sign', $cancellation->company_token), [
            'signed_by_name' => 'Authorized Manager',
            'signature_data' => self::SIGNATURE,
            'accepted' => '1',
        ])->assertRedirect();

        $cancellation->refresh();
        $this->assertSame('fully_signed', $cancellation->status);
        $this->assertNotNull($cancellation->completed_at);
        $this->assertNotNull($cancellation->document_hash);
        Storage::disk('public')->assertExists($cancellation->final_document_path);
        $this->assertDatabaseHas('unit_documents', [
            'property_id' => $property->id,
            'reference_no' => $cancellation->reference_no,
            'type' => 'custom',
        ]);
        $this->get(route('unit-cancellations.pdf', $cancellation->owner_token))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    private function records(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord', 'email' => 'owner@example.com']);
        $property = Property::create(['landlord_id' => $owner->id, 'name' => '501', 'status' => 'vacant']);

        return [$admin, $owner, $property];
    }

    private function cancellation(Property $property, User $owner): UnitManagementCancellation
    {
        return UnitManagementCancellation::create([
            'property_id' => $property->id,
            'owner_id' => $owner->id,
            'reference_no' => 'CXL-261006-ABCDE',
            'letter_date' => '2026-10-05',
            'effective_date' => '2026-10-31',
            'property_description' => 'Unit 501, Mirdif, Dubai',
            'owner_name' => $owner->name,
            'owner_email' => $owner->email,
            'company_signer_name' => 'Authorized Manager',
            'company_signer_email' => 'manager@pattern.ae',
            'status' => 'sent_to_owner',
            'owner_token' => str_repeat('o', 64),
            'company_token' => str_repeat('c', 64),
            'owner_sent_at' => now(),
            'expires_at' => now()->addDays(30)->toDateString(),
        ]);
    }
}
