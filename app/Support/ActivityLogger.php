<?php

namespace App\Support;

use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ActivityLogger
{
    public static function write(string $event, ?object $user = null, array $extra = [], ?Request $request = null): void
    {
        try {
            if (! Schema::hasTable('user_activity_logs')) return;
            $request ??= request();
            $agent = (string)$request->userAgent();
            UserActivityLog::create(array_merge([
                'user_id' => $user?->id, 'user_email' => $user?->email,
                'event' => $event, 'method' => $request->method(), 'route_name' => $request->route()?->getName(),
                'url_path' => '/'.ltrim($request->path(), '/'), 'ip_address' => $request->ip(),
                'forwarded_for' => $request->header('X-Forwarded-For'), 'user_agent' => $agent,
                'device' => self::device($agent), 'occurred_at' => now(),
            ], $extra));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private static function device(string $agent): string
    {
        $browser = str_contains($agent, 'Edg/') ? 'Edge' : (str_contains($agent, 'Chrome/') ? 'Chrome' : (str_contains($agent, 'Firefox/') ? 'Firefox' : (str_contains($agent, 'Safari/') ? 'Safari' : 'Other browser')));
        $platform = preg_match('/Android/i', $agent) ? 'Android' : (preg_match('/iPhone|iPad/i', $agent) ? 'iOS' : (preg_match('/Windows/i', $agent) ? 'Windows' : (preg_match('/Macintosh/i', $agent) ? 'macOS' : (preg_match('/Linux/i', $agent) ? 'Linux' : 'Unknown device'))));
        return $browser.' on '.$platform;
    }
}
