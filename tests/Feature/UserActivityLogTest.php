<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_failed_login_logout_and_admin_action_capture_ip_and_device(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'audit@example.com']);
        $headers = ['REMOTE_ADDR' => '203.0.113.25', 'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/130.0'];

        $this->withServerVariables($headers)->post('/login', ['email' => 'audit@example.com', 'password' => 'wrong-password']);
        $this->withServerVariables($headers)->post('/login', ['email' => 'audit@example.com', 'password' => 'password'])->assertRedirect();
        $this->withServerVariables($headers)->post(route('admin.access-control.roles.store'), ['name' => 'Audit Test Role'])->assertSessionHasNoErrors();
        $this->withServerVariables($headers)->post('/logout')->assertRedirect();

        $this->assertTrue(UserActivityLog::where('event', 'login_failed')->where('user_email', 'audit@example.com')->exists());
        $this->assertTrue(UserActivityLog::where('event', 'login_success')->where('user_id', $admin->id)->exists());
        $action = UserActivityLog::where('event', 'admin_action')->firstOrFail();
        $this->assertSame('203.0.113.25', $action->ip_address);
        $this->assertSame('Chrome on Windows', $action->device);
        $this->assertSame('admin.access-control.roles.store', $action->route_name);
        $this->assertTrue(UserActivityLog::where('event', 'logout')->where('user_id', $admin->id)->exists());

        $this->actingAs($admin)->get(route('admin.activity-logs.index'))->assertOk()->assertSee('User Activity Logs')->assertSee('203.0.113.25');
    }

    public function test_activity_log_page_enriches_public_ips_with_approximate_location(): void
    {
        Cache::flush();
        Http::fake(['ipwho.is/*' => Http::response([
            'success' => true, 'city' => 'Dubai', 'region' => 'Dubai', 'country' => 'United Arab Emirates',
            'country_code' => 'AE', 'latitude' => 25.2048, 'longitude' => 55.2708,
            'timezone' => ['id' => 'Asia/Dubai'],
        ])]);
        $admin = User::factory()->create(['role' => 'admin']);
        UserActivityLog::create([
            'user_id' => $admin->id, 'user_email' => $admin->email, 'event' => 'login_success',
            'ip_address' => '8.8.8.8', 'device' => 'Chrome on Windows', 'occurred_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.activity-logs.index'))
            ->assertOk()->assertSee('Dubai, Dubai, United Arab Emirates')->assertSee('Approximate map');

        $log = UserActivityLog::where('ip_address', '8.8.8.8')->firstOrFail();
        $this->assertSame('Dubai', $log->location_city);
        $this->assertSame('AE', $log->location_country_code);
    }
}
