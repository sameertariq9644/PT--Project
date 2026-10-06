<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent synchronously from Admin → Settings to prove the SMTP settings work.
 */
class TestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Test email from '.config('site.name'));
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            text: 'emails.notification-text',
            with: [
                'heading' => 'Your email settings work',
                'intro' => 'This is a test message sent from Admin → Settings. If you are reading it, outgoing email is configured correctly.',
                'rows' => [],
                'action' => null,
            ],
        );
    }
}
