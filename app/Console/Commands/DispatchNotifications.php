<?php

namespace App\Console\Commands;

use App\Mail\ApprovalNotificationMail;
use App\Models\Notification;
use App\Models\Scopes\TenantScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Delivers pending email notifications via SMTP. Each row carries its own
 * tenant + recipient, so processing them cross-tenant never leaks data.
 */
class DispatchNotifications extends Command
{
    protected $signature = 'notifications:dispatch';

    protected $description = 'Send pending email notifications via SMTP.';

    public function handle(): int
    {
        $pending = Notification::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where('channel', 'email')
            ->where('status', 'pending')
            ->with('user:id,email,name')
            ->limit(500)
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($pending as $notification) {
            $email = $notification->user?->email;

            if (empty($email)) {
                $notification->update(['status' => 'skipped']);
                $skipped++;

                continue;
            }

            Mail::to($email)->send(new ApprovalNotificationMail($notification));
            $notification->update(['status' => 'sent', 'sent_at' => now()]);
            $sent++;
        }

        $this->info("Notifikasi email terkirim: {$sent}, dilewati: {$skipped}.");

        return self::SUCCESS;
    }
}
