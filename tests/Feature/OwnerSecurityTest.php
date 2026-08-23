<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OwnerSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_owner_security_page_without_current_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord', 'email' => 'owner@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.landlord.security', $owner->id))
            ->assertOk()
            ->assertSee('Security')
            ->assertSee('owner@example.com')
            ->assertSee('Current passwords are encrypted and cannot be displayed.');
    }

    public function test_admin_can_generate_a_one_time_temporary_owner_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'landlord', 'password' => Hash::make('OldPassword#1')]);

        $response = $this->actingAs($admin)->post(
            route('admin.landlord.security.reset-password', $owner->id),
            ['email_credentials' => 0],
        );

        $temporaryPassword = session('temporary_password');
        $response->assertRedirect(route('admin.landlord.security', $owner->id));
        $this->assertIsString($temporaryPassword);
        $this->assertNotSame('', $temporaryPassword);
        $this->assertTrue(Hash::check($temporaryPassword, $owner->fresh()->password));
        $this->assertFalse(Hash::check('OldPassword#1', $owner->fresh()->password));
    }

    public function test_owner_cannot_access_admin_security_page(): void
    {
        $owner = User::factory()->create(['role' => 'landlord']);

        $this->actingAs($owner)
            ->get(route('admin.landlord.security', $owner->id))
            ->assertForbidden();
    }
}
