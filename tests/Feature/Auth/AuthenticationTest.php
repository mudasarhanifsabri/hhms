<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['role' => 'tenant']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route($user->role.'.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_maintainers_are_remembered_without_selecting_keep_me_signed_in(): void
    {
        $maintainer = User::factory()->create([
            'role' => 'maintainer',
            'remember_token' => null,
        ]);

        $response = $this->post('/login', [
            'email' => $maintainer->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame((string) $maintainer->id, (string) auth()->id());
        $response->assertRedirect(route('maintainer.dashboard', absolute: false));
        $this->assertNotNull($maintainer->fresh()->remember_token);
    }

    public function test_other_users_are_not_remembered_unless_they_request_it(): void
    {
        $tenant = User::factory()->create([
            'role' => 'tenant',
            'remember_token' => null,
        ]);

        $this->post('/login', [
            'email' => $tenant->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame((string) $tenant->id, (string) auth()->id());
        $this->assertNull($tenant->fresh()->remember_token);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
