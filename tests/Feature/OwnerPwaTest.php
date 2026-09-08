<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\Building;
use App\Models\LandlordAccountEntry;
use App\Models\Property;
use App\Models\User;
use App\Notifications\LandlordCreated;
use App\Support\OwnerStatementPdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerPwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_mobile_pwa_with_owned_property(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $building = Building::create(['building_name' => 'Marina Tower', 'address' => 'Dubai Marina']);
        Property::create([
            'landlord_id' => $owner->id,
            'building_id' => $building->id,
            'name' => 'Unit 3308',
            'status' => 'vacant',
        ]);

        $this->actingAs($owner)
            ->get(route('landlord.app'))
            ->assertOk()
            ->assertSee('Pattern Owner App')
            ->assertSee('Unit 3308')
            ->assertSee('Marina Tower')
            ->assertSee('Desktop Owner Portal')
            ->assertSee('Change Password')
            ->assertSee('data-language-toggle', false);
    }

    public function test_owner_dashboard_counts_cross_month_booking_occupancy_and_shows_personal_greeting(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 13:30:00', 'UTC'));

        try {
            $owner = User::factory()->create(['role' => 'landlord', 'name' => 'Abdulla Alhemeiri']);
            $building = Building::create(['building_name' => 'Binghatti Heights', 'address' => 'Dubai']);
            $unit = Property::create([
                'landlord_id' => $owner->id,
                'building_id' => $building->id,
                'name' => '402',
                'status' => 'vacant',
            ]);
            Booking::create([
                'property_id' => $unit->id,
                'booking_reference' => 'BK-CROSS-MONTH',
                'invoice_number' => 'INV-CROSS-MONTH',
                'invoice_status' => 'paid',
                'guest_name' => 'Long Stay Guest',
                'guest_email' => 'guest@example.com',
                'guest_phone' => '0500000000',
                'guest_passport_id_no' => 'P-CROSS-MONTH',
                'check_in' => '2026-08-07',
                'check_out' => '2026-11-05',
                'rent_amount' => 6000,
                'total_amount' => 6000,
                'status' => 'confirmed',
            ]);

            $this->actingAs($owner)->get(route('landlord.app'))
                ->assertOk()
                ->assertSee('Good evening,')
                ->assertSee('Abdulla')
                ->assertSee('Warm greetings from Sultan')
                ->assertSee('Pattern Vacation Homes Rental')
                ->assertSee('data-owner-welcome', false)
                ->assertSee('data-owner-id="'.$owner->id.'"', false)
                ->assertSee('data-greeting-period="evening"', false)
                ->assertSeeInOrder(['Occupancy', '100%']);

            $ownerScript = file_get_contents(public_path('assets/js/owner-pwa.js'));
            $this->assertStringContainsString("timeZone:'Asia/Dubai'", $ownerScript);
            $this->assertStringContainsString('owner-welcome-v2:', $ownerScript);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_non_owner_cannot_open_owner_pwa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('landlord.app'))->assertForbidden();
    }

    public function test_phone_owner_is_sent_to_app_and_can_choose_desktop_portal(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Android 15; Mobile)')
            ->post(route('login'), ['email' => $owner->email, 'password' => 'password'])
            ->assertRedirect(route('landlord.app'));
        auth()->logout();
        $this->actingAs($owner)->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
            ->get(route('landlord.dashboard'))->assertRedirect(route('landlord.app'));
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
            ->get(route('landlord.dashboard', ['desktop' => 1]))->assertOk();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)')
            ->get(route('landlord.dashboard'))->assertOk();
    }

    public function test_owner_app_has_premium_controls_and_statement_download(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);

        $this->actingAs($owner)->get(route('landlord.app'))->assertOk()
            ->assertSee('data-search="properties"', false)
            ->assertSee('data-calendar-next', false)
            ->assertSee('My Information')
            ->assertSee('Bank Details')
            ->assertSee(route('landlord.statement.pdf'), false);
        $this->get(route('landlord.statement.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_owner_booking_views_show_only_collected_rent_and_management_fee_per_invoice_period(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => 'Owner Unit 502']);
        $booking = Booking::create([
            'property_id' => $unit->id, 'booking_reference' => 'BK-OWNER-VIEW', 'guest_name' => 'Guest',
            'guest_email' => 'guest@example.com', 'guest_phone' => '0500000000', 'guest_passport_id_no' => 'P12345',
            'check_in' => '2026-09-01', 'check_out' => '2026-10-15', 'management_fee_percent' => 10,
            'rent_amount' => 1500, 'total_amount' => 2205, 'invoice_number' => 'INV-OWNER-ORIGINAL',
            'invoice_status' => 'partial', 'status' => 'confirmed',
        ]);
        $original = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-OWNER-ORIGINAL', 'invoice_type' => 'original',
            'issue_date' => '2026-09-01', 'period_from' => '2026-09-01', 'period_to' => '2026-09-30',
            'rent_amount' => 1000, 'vat_amount' => 50, 'fees' => ['DTCM Fee' => 30, 'Cleaning Fee' => 100, 'Security Deposit' => 500],
            'total_amount' => 1680, 'status' => 'paid',
        ]);
        $extension = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-OWNER-EXTENSION', 'invoice_type' => 'extension',
            'issue_date' => '2026-10-01', 'period_from' => '2026-10-01', 'period_to' => '2026-10-15',
            'rent_amount' => 500, 'vat_amount' => 25, 'total_amount' => 525, 'status' => 'partial',
        ]);
        $original->payments()->create(['payment_date' => '2026-09-02', 'amount' => 1680, 'rent_amount' => 1000, 'payment_method' => 'Bank Transfer']);
        $extension->payments()->create(['payment_date' => '2026-10-02', 'amount' => 420, 'rent_amount' => 400, 'payment_method' => 'Bank Transfer']);

        $this->actingAs($owner)->get(route('landlord.app'))->assertOk()
            ->assertSee('Original Booking')->assertSee('Extension')
            ->assertSee('Upcoming payout')->assertSee('Awaiting guest payment')
            ->assertSee('AED 1,000.00')->assertSee('- AED 100.00')->assertSee('AED 900.00')
            ->assertSee('AED 400.00')->assertSee('- AED 40.00')->assertSee('AED 360.00')
            ->assertDontSee('AED 1,680.00')->assertDontSee('AED 525.00');
        $this->actingAs($owner)->get(route('landlord.dashboard', ['desktop' => 1]))->assertOk()
            ->assertSee('Booking Income')->assertSee('Original Booking')->assertSee('Extension')
            ->assertDontSee('AED 1,680.00')->assertDontSee('AED 525.00');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.landlord.account-entry.store', $owner), [
            'entry_date' => '2026-09-30', 'type' => 'payout', 'amount' => 900,
            'booking_invoice_id' => $original->id, 'reference' => 'BANK-OWNER-001',
        ])->assertSessionHasNoErrors();
        $this->actingAs($owner)->get(route('landlord.app'))->assertOk()
            ->assertSee('Paid')->assertSee('BANK-OWNER-001')->assertSee('Paid to owner');
    }

    public function test_partial_booking_income_is_hidden_from_owner_statement_until_invoice_is_fully_paid(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        $unit = Property::create(['landlord_id' => $owner->id, 'name' => 'Statement Unit']);
        $booking = Booking::create([
            'property_id' => $unit->id, 'booking_reference' => 'BK-PARTIAL-HIDDEN', 'invoice_number' => 'INV-PARTIAL-HIDDEN',
            'guest_name' => 'Statement Guest', 'guest_email' => 'statement@example.com', 'guest_phone' => '0500000001',
            'guest_passport_id_no' => 'P67890', 'check_in' => '2026-09-01', 'check_out' => '2026-09-30',
            'rent_amount' => 1000, 'management_fee_percent' => 10, 'total_amount' => 1050,
            'invoice_status' => 'partial', 'status' => 'confirmed',
        ]);
        $invoice = BookingInvoice::create([
            'booking_id' => $booking->id, 'invoice_number' => 'INV-PARTIAL-HIDDEN', 'invoice_type' => 'original',
            'issue_date' => '2026-09-01', 'period_from' => '2026-09-01', 'period_to' => '2026-09-30',
            'rent_amount' => 1000, 'vat_amount' => 50, 'total_amount' => 1050, 'status' => 'partial',
        ]);
        $firstPayment = $invoice->payments()->create([
            'payment_date' => '2026-09-02', 'amount' => 500, 'rent_amount' => 476.19, 'payment_method' => 'Bank Transfer',
        ]);
        LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'property_id' => $unit->id, 'entry_date' => '2026-09-02',
            'type' => 'rent_income', 'direction' => 'credit', 'amount' => 476.19,
            'reference' => 'PAY-'.$firstPayment->id, 'description' => 'PARTIAL OWNER RENT ROW',
        ]);

        $this->actingAs($owner)->get(route('landlord.app'))->assertOk()->assertDontSee('PARTIAL OWNER RENT ROW');
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.landlord.account-statement', $owner))->assertOk()->assertDontSee('PARTIAL OWNER RENT ROW');
        $this->assertCount(0, OwnerStatementPdf::data($owner, '2026-09-01', '2026-09-30')['entries']);

        $invoice->payments()->create([
            'payment_date' => '2026-09-03', 'amount' => 550, 'rent_amount' => 523.81, 'payment_method' => 'Bank Transfer',
        ]);

        $this->actingAs($owner)->get(route('landlord.app'))->assertOk()->assertSee('PARTIAL OWNER RENT ROW');
        $this->assertCount(1, OwnerStatementPdf::data($owner, '2026-09-01', '2026-09-30')['entries']);
    }

    public function test_owner_welcome_email_contains_login_credentials_and_app_link(): void
    {
        $owner = User::factory()->create([
            'role' => 'landlord',
            'email' => 'owner@example.com',
        ]);

        $mail = (new LandlordCreated($owner, 'TempPass#2026'))->toMail($owner);
        $content = implode(' ', $mail->introLines);

        $this->assertStringContainsString('Username: owner@example.com', $content);
        $this->assertStringContainsString('Temporary password: TempPass#2026', $content);
        $this->assertSame(route('landlord.app'), $mail->actionUrl);
    }

    public function test_owner_app_assets_include_arabic_and_rtl_support(): void
    {
        $this->assertStringContainsString("'Home':'الرئيسية'", file_get_contents(public_path('assets/js/owner-pwa.js')));
        $this->assertStringContainsString('[dir=rtl]', file_get_contents(public_path('assets/css/owner-pwa-rtl.css')));
    }

    public function test_owner_sees_statement_descriptions_on_mobile_and_desktop(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        LandlordAccountEntry::create([
            'landlord_id' => $owner->id, 'entry_date' => '2026-08-26', 'type' => 'furnishing',
            'direction' => 'debit', 'amount' => 20000, 'reference' => 'FURN-001',
            'description' => 'Complete furnishing package recoverable from rental income',
        ]);

        $this->actingAs($owner)->get(route('landlord.app'))
            ->assertOk()->assertSee('Furnishing / Setup Cost')->assertSee('Complete furnishing package recoverable from rental income')->assertSee('FURN-001');
        $this->actingAs($owner)->get(route('landlord.dashboard'))
            ->assertOk()->assertSee('Furnishing / Setup Cost')->assertSee('Complete furnishing package recoverable from rental income')->assertSee('FURN-001');
    }

    public function test_future_rental_income_offsets_furnishing_cost_balance(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);
        LandlordAccountEntry::create(['landlord_id' => $owner->id, 'entry_date' => '2026-08-01', 'type' => 'furnishing', 'direction' => 'debit', 'amount' => 20000]);
        LandlordAccountEntry::create(['landlord_id' => $owner->id, 'entry_date' => '2026-09-01', 'type' => 'rent_income', 'direction' => 'credit', 'amount' => 7500]);
        LandlordAccountEntry::recalculateBalancesFor($owner->id);

        $this->assertSame(-12500.0, (float) LandlordAccountEntry::where('landlord_id', $owner->id)->latest('entry_date')->value('balance_after'));
    }
}
