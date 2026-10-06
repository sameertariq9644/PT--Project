<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Base for the internal "something happened" emails sent to the admin. Each
 * is a heading, a list of label/value rows and an optional admin link, with
 * Reply-To set to the visitor so the admin can answer straight from Gmail.
 * The visitor's address is never used as the From address.
 */
abstract class AdminNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public bool $deleteWhenMissingModels = true;

    public function __construct()
    {
        $this->onQueue(config('site.queues.mail'));
        $this->afterCommit();
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    abstract protected function subjectLine(): string;

    abstract protected function heading(): string;

    /**
     * @return array<string, string|null>
     */
    abstract protected function rows(): array;

    abstract protected function replyToAddress(): Address;

    protected function intro(): ?string
    {
        return null;
    }

    /**
     * @return array{url: string, label: string}|null
     */
    protected function action(): ?array
    {
        return null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine(), replyTo: [$this->replyToAddress()]);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            text: 'emails.notification-text',
            with: [
                'heading' => $this->heading(),
                'intro' => $this->intro(),
                'rows' => array_filter($this->rows(), fn (?string $value): bool => filled($value)),
                'action' => $this->action(),
            ],
        );
    }
}
