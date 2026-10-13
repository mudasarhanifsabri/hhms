<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class IpLocationResolver
{
    public static function resolve(?string $ip): ?array
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        return Cache::remember('activity-ip-location:'.$ip, now()->addDays(30), function () use ($ip) {
            try {
                $response = Http::acceptJson()->connectTimeout(1)->timeout(2)->get('https://ipwho.is/'.$ip, [
                    'fields' => 'success,country,country_code,region,city,latitude,longitude,timezone.id',
                ]);
                if (! $response->successful() || ! $response->json('success')) return null;

                return [
                    'location_city' => $response->json('city'),
                    'location_region' => $response->json('region'),
                    'location_country' => $response->json('country'),
                    'location_country_code' => $response->json('country_code'),
                    'location_latitude' => $response->json('latitude'),
                    'location_longitude' => $response->json('longitude'),
                    'location_timezone' => $response->json('timezone.id'),
                    'location_resolved_at' => now(),
                ];
            } catch (Throwable $exception) {
                report($exception);
                return null;
            }
        });
    }
}
