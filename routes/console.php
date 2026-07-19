<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Generate reminders everyday at 08:00
Schedule::command('whatsapp:reminders')->dailyAt('08:00');

// Process and send notifications every minute
Schedule::command('whatsapp:process')->everyMinute();
