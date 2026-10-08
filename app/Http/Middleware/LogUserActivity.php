<?php

namespace App\Http\Middleware;

use App\Support\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);
        $response = $next($request);
        if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            ActivityLogger::write('admin_action', $request->user(), [
                'status_code' => $response->getStatusCode(),
                'duration_ms' => (int)round((microtime(true) - $started) * 1000),
                'description' => str($request->route()?->getName() ?: $request->path())->replace(['admin.', '.', '-'], ['', ' ', ' '])->headline(),
                'metadata' => ['route_parameters' => collect($request->route()?->parameters() ?? [])->map(fn ($value) => is_object($value) ? $value->getKey() : $value)->all()],
            ], $request);
        }
        return $response;
    }
}
