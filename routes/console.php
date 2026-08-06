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

// Bersihkan data dan file sampah (Soft Delete) yang lebih dari 30 hari secara otomatis setiap hari jam 00:00
Schedule::command('prune:soft-deleted --days=30')->daily();

// Kompresi riwayat percakapan bot WhatsApp yang panjang (>50 pesan) setiap minggu
Schedule::command('whatsapp:compact-bot-conversations')->weekly();
