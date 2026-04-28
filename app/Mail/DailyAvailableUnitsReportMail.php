<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyAvailableUnitsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{property_name:string, total_units:int, sold_units:int, available_units:int}>  $rows
     */
    public function __construct(
        public readonly Project $project,
        public readonly string $reportDate,
        public readonly array $rows,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تقرير يومي — الوحدات المتاحة — '.$this->project->name.' — '.$this->reportDate,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-available-units-report',
            with: [
                'project' => $this->project,
                'reportDate' => $this->reportDate,
                'rows' => $this->rows,
            ],
        );
    }
}

