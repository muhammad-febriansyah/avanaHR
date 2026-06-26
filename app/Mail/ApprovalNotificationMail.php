<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Notification $notification) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectFor($this->notification->type));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.approval-notification',
            with: [
                'heading' => $this->subjectFor($this->notification->type),
                'title' => $this->notification->payload['title'] ?? null,
            ],
        );
    }

    private function subjectFor(string $type): string
    {
        return match ($type) {
            'approval.assigned' => 'Pengajuan menunggu persetujuan Anda',
            'approval.approved' => 'Pengajuan Anda disetujui',
            'approval.rejected' => 'Pengajuan Anda ditolak',
            'approval.revision' => 'Pengajuan Anda perlu revisi',
            'approval.sla_reminder' => 'Pengingat: persetujuan melewati SLA',
            'approval.escalated' => 'Persetujuan dieskalasi ke Anda',
            default => 'Notifikasi persetujuan',
        };
    }
}
