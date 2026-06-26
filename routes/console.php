<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Apply scheduled employee movements whose effective date has arrived.
Schedule::command('movements:apply-due')->dailyAt('01:00');

// Remind + escalate approval steps that have breached their SLA.
Schedule::command('approvals:check-sla')->hourly();

// Deliver pending email notifications via SMTP.
Schedule::command('notifications:dispatch')->everyMinute();
