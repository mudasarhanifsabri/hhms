<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use App\Notifications\LandlordCreated;
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

    public function test_non_owner_cannot_open_owner_pwa(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('landlord.app'))->assertForbidden();
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
}
