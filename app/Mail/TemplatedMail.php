<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Support\DefaultEmailTemplates;
use App\Support\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * An email written by the admin under Admin → Email templates. The template
 * is rendered when the job runs, so edits apply to mail still in the queue.
 */
class TemplatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public bool $deleteWhenMissingModels = true;

    /**
     * @var array{subject: string, html: string, text: string}|null
     */
    protected ?array $rendered = null;

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function __construct(public EmailTemplate $template, public array $variables)
    {
        $this->onQueue(config('site.queues.mail'));
        $this->afterCommit();
    }

    /**
     * Build the mail for one of the system templates (lead_video, order_confirmed, …).
     *
     * @param  array<string, scalar|null>  $variables
     */
    public static function forKey(string $key, array $variables): self
    {
        return new self(DefaultEmailTemplates::resolve($key), $variables);
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->rendered()['subject']);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.templated',
            text: 'emails.text',
            with: [
                'html' => $this->rendered()['html'],
                'text' => $this->rendered()['text'],
            ],
        );
    }

    /**
     * @return array{subject: string, html: string, text: string}
     */
    protected function rendered(): array
    {
        return $this->rendered ??= app(TemplateRenderer::class)->render(
            $this->template->subject,
            $this->template->body,
            $this->variables,
        );
    }
}
