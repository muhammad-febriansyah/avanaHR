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

// Remind HR of expiring documents + ending fixed-term contracts.
Schedule::command('reminders:scan')->dailyAt('02:00');

// Deliver pending email notifications via SMTP.
Schedule::command('notifications:dispatch')->everyMinute();
