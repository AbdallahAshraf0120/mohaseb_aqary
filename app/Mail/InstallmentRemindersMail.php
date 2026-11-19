<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstallmentRemindersMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array<string, mixed>>  $overdue
     * @param  list<array<string, mixed>>  $upcoming
     * @param  list<array<string, mixed>>  $cheques
     */
    public function __construct(
        public readonly Project $project,
        public readonly string $reportDate,
        public readonly array $overdue,
        public readonly array $upcoming,
        public readonly array $cheques,
        public readonly int $daysBefore,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'تذكير الأقساط — '.$this->project->name.' — '.$this->reportDate);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.installment-reminders');
    }
}
