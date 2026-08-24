<?php

namespace App\Console\Commands;

use App\Models\UnitDocument;
use App\Models\User;
use App\Notifications\DtcmPermitExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDtcmExpiryReminders extends Command
{
    protected $signature = 'dtcm:send-expiry-reminders';
    protected $description = 'Notify administrators about DTCM permits expiring within seven days';

    public function handle(): int
    {
        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        if ($admins->isEmpty()) {
            $this->components->warn('No active administrators found.');
            return self::SUCCESS;
        }

        $sent = 0;
        UnitDocument::with('property.building')
            ->where('type', 'dtcm_permit')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [today(), today()->addDays(7)])
            ->where(fn ($query) => $query->whereNull('expiry_reminder_sent_for')
                ->orWhereColumn('expiry_reminder_sent_for', '!=', 'expires_at'))
            ->each(function (UnitDocument $permit) use ($admins, &$sent) {
                Notification::send($admins, new DtcmPermitExpiring($permit));
                $permit->forceFill(['expiry_reminder_sent_for' => $permit->expires_at])->save();
                $sent++;
            });

        $this->components->info("Sent {$sent} DTCM expiry reminder(s).");
        return self::SUCCESS;
    }
}
