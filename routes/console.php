<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('dtcm:send-expiry-reminders')->dailyAt('08:00')->withoutOverlapping();
Schedule::command('bookings:send-checkout-reminders')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('expenses:generate-recurring')->dailyAt('00:10')->withoutOverlapping();
