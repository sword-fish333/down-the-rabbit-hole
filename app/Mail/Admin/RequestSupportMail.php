<?php

namespace App\Mail\Admin;

use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestSupportMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{title: string, topic: string, message: string}  $support
     */
    public function __construct(
        public Admin $admin,
        public array $support,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [$this->admin->email],
            subject: __('admin/backend.mail.support-subject', ['title' => $this->support['title']]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.support-request',
        );
    }
}
