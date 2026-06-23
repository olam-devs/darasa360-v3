<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class ReportCardMail extends Mailable
{
    use Queueable, SerializesModels;

    public $studentName;
    public $className;
    public $term;
    public $academicYear;
    public $schoolName;
    public $pdfPath;
    public $averageMarks;
    public $position;
    public $totalStudents;
    public $grade;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->studentName = $data['studentName'];
        $this->className = $data['className'];
        $this->term = $data['term'];
        $this->academicYear = $data['academicYear'];
        $this->schoolName = $data['schoolName'];
        $this->pdfPath = $data['pdfPath'];
        $this->averageMarks = $data['averageMarks'] ?? 0;
        $this->position = $data['position'] ?? 0;
        $this->totalStudents = $data['totalStudents'] ?? 0;
        $this->grade = $data['grade'] ?? 'N/A';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->schoolName} - Report Card for {$this->studentName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.report_card',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as("ReportCard-{$this->studentName}-{$this->term}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
