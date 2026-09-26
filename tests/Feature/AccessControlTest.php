<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_role_and_staff_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($admin->hasRole('Super Administrator'));

        $this->actingAs($admin)->post(route('admin.access-control.roles.store'), [
            'name' => 'Booking Officer', 'permissions' => ['dashboard', 'bookings'], 'manage' => ['bookings'],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $role = Role::findByName('Booking Officer');
        $this->assertTrue($role->hasPermissionTo('bookings.view'));
        $this->assertTrue($role->hasPermissionTo('bookings.manage'));
        $this->assertFalse($role->hasPermissionTo('accounting.view'));

        $this->post(route('admin.access-control.users.store'), [
            'name' => 'Booking User', 'email' => 'booking.user@example.com', 'phone' => '0500000000',
            'password' => 'Secure-Password-123!', 'password_confirmation' => 'Secure-Password-123!', 'role_id' => $role->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $staff = User::where('email', 'booking.user@example.com')->firstOrFail();
        $this->assertSame('admin', $staff->role);
        $this->assertTrue($staff->hasRole('Booking Officer'));
        $this->assertFalse($staff->hasRole('Super Administrator'));
    }

    public function test_permissions_allow_booking_access_and_block_accounting_and_role_management(): void
    {
        $super = User::factory()->create(['role' => 'admin']);
        $this->actingAs($super)->post(route('admin.access-control.roles.store'), [
            'name' => 'Booking Viewer', 'permissions' => ['dashboard', 'bookings'],
        ]);
        $viewer = User::factory()->create(['role' => 'admin']);
        $viewer->syncRoles(['Booking Viewer']);
        $this->assertSame('admin', $viewer->fresh()->role);
        $this->assertTrue($viewer->fresh()->can('bookings.view'));

        $this->actingAs($viewer)->get(route('admin.booking.index'))->assertOk();
        $this->get(route('admin.accounting.dashboard'))->assertForbidden();
        $this->get(route('admin.access-control.index'))->assertForbidden();
        $this->post(route('admin.booking.store'), [])->assertForbidden();
    }

    public function test_last_active_super_admin_cannot_remove_their_own_super_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertSame('admin', $admin->role);
        $role = Role::create(['name' => 'Read Only', 'guard_name' => 'web']);
        $role->givePermissionTo('dashboard.view');

        $this->actingAs($admin)->put(route('admin.access-control.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email,
            'role_id' => $role->id, 'is_active' => 1,
        ])->assertStatus(422);
        $this->assertTrue($admin->fresh()->hasRole('Super Administrator'));
    }

    public function test_super_admin_can_edit_and_delete_a_staff_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $role = Role::create(['name' => 'Operations', 'guard_name' => 'web']);
        $staff = User::factory()->create(['role' => 'admin', 'email' => 'old@example.com']);

        $this->actingAs($admin)->put(route('admin.access-control.users.update', $staff), [
            'name' => 'Updated User', 'email' => 'updated@example.com', 'phone' => '0501234567',
            'role_id' => $role->id, 'is_active' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => 'Updated User', 'email' => 'updated@example.com']);
        $this->assertTrue($staff->fresh()->hasRole('Operations'));

        $this->delete(route('admin.access-control.users.destroy', $staff))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSoftDeleted('users', ['id' => $staff->id]);
    }

    public function test_staff_user_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('admin.access-control.users.destroy', $admin))
            ->assertStatus(422);

        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }
}
