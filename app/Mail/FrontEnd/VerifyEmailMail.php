<?php

namespace App\Mail\FrontEnd;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $verificationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('frontend.mail.verify-subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.frontend.verify-email');
    }
}
