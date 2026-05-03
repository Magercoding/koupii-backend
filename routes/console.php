<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send deadline reminders every day at midnight (or hourly if preferred)
Schedule::command('app:send-deadline-reminders')->dailyAt('07:00');
