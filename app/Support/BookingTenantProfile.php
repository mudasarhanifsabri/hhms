<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingTenantProfile
{
    public static function missing(User $user): array
    {
        return array_keys(array_filter([
            'name' => blank($user->name), 'phone' => blank($user->phone),
            'eid_passport_no' => blank($user->eid_passport_no), 'nationality' => blank($user->nationality),
            'dob' => blank($user->dob), 'address' => blank($user->address),
        ]));
    }

    public static function sync(Booking $booking): ?User
    {
        return DB::transaction(function () use ($booking) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $linkedTenant = (bool) $booking->tenant_id;
            if ($booking->tenant_id) {
                $tenant = User::withTrashed()->find($booking->tenant_id);
                if (! $tenant || $tenant->trashed() || $tenant->role !== 'tenant') {
                    $booking->histories()->firstOrCreate(['title' => 'Tenant Link Needs Review'], ['description' => 'The linked tenant account is missing, removed or no longer has the tenant role.']);

                    return null;
                }
            }
            $email = strtolower(trim($booking->guest_email ?? ''));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($booking->guest_passport_id_no ?? '') > 50) {
                $booking->histories()->firstOrCreate(['title' => 'Tenant Link Needs Review'], ['description' => 'Guest email or passport/ID requires correction before creating a tenant login.']);

                return null;
            }
            if (! $linkedTenant) {
                $tenant = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();
                if (! $tenant) {
                    $tenant = User::firstOrCreate(['email' => $email], ['name' => $booking->guest_name, 'phone' => $booking->guest_phone,
                        'eid_passport_no' => $booking->guest_passport_id_no, 'password' => Str::random(64), 'role' => 'tenant']);
                }
            }
            $passport = strtoupper(trim($booking->guest_passport_id_no ?? ''));
            if ($tenant->trashed() || $tenant->role !== 'tenant' || (! $linkedTenant && filled($tenant->eid_passport_no) && strtoupper(trim($tenant->eid_passport_no)) !== $passport)) {
                $booking->histories()->firstOrCreate(['title' => 'Tenant Link Needs Review'], ['description' => 'Guest email matches a different role, removed account or conflicting passport/ID. No tenant access was granted.']);

                return null;
            }

            $emailOwner = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->whereKeyNot($tenant->id)->first();
            if (! $emailOwner) {
                $tenant->email = $email;
            } elseif ($linkedTenant && strtolower((string) $tenant->email) !== $email) {
                $booking->histories()->firstOrCreate(['title' => 'Tenant Email Needs Review'], ['description' => 'The updated guest email belongs to another account, so the linked tenant email was not changed.']);
            }

            foreach (['name' => 'guest_name', 'phone' => 'guest_phone', 'eid_passport_no' => 'guest_passport_id_no', 'id_document' => 'guest_document'] as $field => $source) {
                if (filled($booking->{$source})) {
                    $tenant->{$field} = $booking->{$source};
                }
            }
            $tenant->forceFill(['tenant_profile_required' => count(self::missing($tenant)) > 0])->save();
            $booking->forceFill(['tenant_id' => $tenant->id])->save();

            return $tenant;
        });
    }
}
