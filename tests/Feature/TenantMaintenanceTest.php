<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingTask;
use App\Models\Property;
use App\Models\User;
use App\Support\BookingTenantProfile;
use App\Support\MediaStorage;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function booking(): Booking
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => 'QR Unit']);

        return Booking::create(['property_id' => $unit->id, 'booking_reference' => uniqid('BK-'), 'invoice_number' => uniqid('INV-'), 'guest_name' => 'Guest', 'guest_email' => uniqid().'@example.com', 'guest_phone' => '12345', 'guest_passport_id_no' => 'P100', 'check_in' => today(), 'check_out' => today()->addDays(5), 'rent_amount' => 100, 'status' => 'checked_in']);
    }

    public function test_request_creates_task_and_status_is_shared_without_internal_data(): void
    {
        Storage::fake(MediaStorage::disk());
        $booking = $this->booking();
        $tenant = BookingTenantProfile::sync($booking);
        $tenant->forceFill(['tenant_profile_required' => false])->save();
        $this->actingAs($tenant)->post(route('tenant.maintenance.store'), [
            'booking_id' => $booking->id, 'title' => 'AC not cooling', 'description' => 'Bedroom AC blows warm air.', 'priority' => 'high',
            'assigned_to' => $tenant->id, 'status' => 'completed', 'property_id' => 'untrusted',
            'pictures' => [UploadedFile::fake()->image('ac.jpg')],
        ])->assertRedirect(route('tenant.maintenance.index'))->assertSessionHasNoErrors();
        $task = BookingTask::sole();
        $this->assertSame($booking->property_id, $task->property_id);
        $this->assertSame($tenant->id, $task->created_by);
        $this->assertSame('maintenance', $task->type);
        $this->assertSame('open', $task->status);
        $this->assertNull($task->assigned_to);
        $this->assertCount(1, $task->activities);
        Storage::disk(MediaStorage::disk())->assertExists(MediaStorage::path($task->pictures[0]));
        $task->update(['status' => 'completed', 'completion_notes' => 'SECRET_INTERNAL_NOTES']);
        $this->get(route('tenant.maintenance.index'))->assertOk()->assertSee('AC not cooling')->assertSee('Completed')->assertDontSee('SECRET_INTERNAL_NOTES');
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.task.show', $task))->assertOk()->assertSee('AC not cooling');
    }

    public function test_unrelated_and_inactive_bookings_cannot_be_used(): void
    {
        $booking = $this->booking();
        $tenant = BookingTenantProfile::sync($booking);
        $tenant->forceFill(['tenant_profile_required' => false])->save();
        $other = $this->booking();
        $data = ['booking_id' => $other->id, 'title' => 'Leak', 'description' => 'Kitchen leak', 'priority' => 'medium'];
        $this->actingAs($tenant)->post(route('tenant.maintenance.store'), $data)->assertNotFound();
        $booking->update(['status' => 'checked_out']);
        $data['booking_id'] = $booking->id;
        $this->post(route('tenant.maintenance.store'), $data)->assertStatus(422);
        $this->assertSame(0, BookingTask::count());
    }

    public function test_guest_cannot_see_another_guests_requests_and_uploads_are_validated(): void
    {
        $booking = $this->booking();
        $tenant = BookingTenantProfile::sync($booking);
        $tenant->forceFill(['tenant_profile_required' => false])->save();
        $other = User::factory()->create(['role' => 'tenant']);
        BookingTask::create(['booking_id' => $booking->id, 'property_id' => $booking->property_id, 'created_by' => $other->id, 'type' => 'maintenance', 'title' => 'PRIVATE_OTHER_REQUEST', 'status' => 'open']);
        $this->actingAs($tenant)->get(route('tenant.maintenance.index'))->assertOk()->assertDontSee('PRIVATE_OTHER_REQUEST');
        $this->post(route('tenant.maintenance.store'), ['booking_id' => $booking->id, 'title' => 'Leak', 'description' => 'Details', 'priority' => 'medium', 'pictures' => [UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml')]])->assertSessionHasErrors('pictures.0');
        $this->assertSame(1, BookingTask::count());
    }

    public function test_qr_registration_sends_email_verification_without_granting_a_session(): void
    {
        Notification::fake();
        $booking = $this->booking();
        $this->get(route('guest.access', $booking->property_id))->assertOk()->assertDontSee($booking->guest_email)->assertDontSee($booking->booking_reference);
        $this->post(route('guest.access.activate', $booking->property_id), ['email' => $booking->guest_email, 'booking_reference' => $booking->booking_reference])->assertSessionHas('status');
        $tenant = $booking->fresh()->tenant_id;
        $this->assertNotNull($tenant);
        Notification::assertSentTo(User::find($tenant), ResetPassword::class);
        $this->assertGuest();
        $this->assertTrue(User::find($tenant)->tenant_profile_required);
        $this->get(route('tenant.maintenance.index'))->assertRedirect(route('login'));
        $notification = Notification::sent(User::find($tenant), ResetPassword::class)->first();
        $password = 'Guest-setup-password-123!';
        $this->post(route('password.store'), ['email' => $booking->guest_email, 'token' => $notification->token, 'password' => $password, 'password_confirmation' => $password])->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check($password, User::find($tenant)->password));
        $this->post(route('password.store'), ['email' => $booking->guest_email, 'token' => $notification->token, 'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('email');
    }

    public function test_wrong_unit_and_wrong_identity_do_not_activate_accounts_and_qr_print_requires_admin(): void
    {
        Notification::fake();
        $booking = $this->booking();
        $other = $this->booking();
        $data = ['email' => $booking->guest_email, 'booking_reference' => $booking->booking_reference];
        $this->post(route('guest.access.activate', $other->property_id), $data)->assertSessionHas('status');
        $this->assertNull($booking->fresh()->tenant_id);
        User::factory()->create(['role' => 'admin', 'email' => $booking->guest_email]);
        $this->post(route('guest.access.activate', $booking->property_id), $data)->assertSessionHas('status');
        $this->assertNull($booking->fresh()->tenant_id);
        Notification::assertNothingSent();
        $this->get(route('admin.property.guest-qr', $booking->property_id))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.property.guest-qr', $booking->property_id))->assertOk()->assertSee('<svg', false)->assertSee(route('guest.access', $booking->property_id));
    }
}
