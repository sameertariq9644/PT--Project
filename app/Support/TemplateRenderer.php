<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Renders an admin-written email template. Placeholders are replaced by plain
 * string substitution and the body is Markdown with raw HTML stripped —
 * admin text is never compiled as Blade, so it cannot execute code.
 */
class TemplateRenderer
{
    /**
     * The placeholders an admin can use, with a description for the editor.
     *
     * @var array<string, string>
     */
    public const PLACEHOLDERS = [
        'name' => 'Recipient’s name',
        'email' => 'Recipient’s email',
        'video_url' => 'Detailed video link (Settings)',
        'whatsapp' => 'Our WhatsApp number (Settings)',
        'plan' => 'Plan name',
        'amount' => 'Order amount',
        'order_number' => 'Order number',
        'site_name' => 'Site name',
    ];

    /**
     * Placeholders that hold links or addresses; these are inserted without
     * Markdown escaping so they still work inside [text](url) and autolinks.
     *
     * @var list<string>
     */
    protected const LINK_PLACEHOLDERS = ['email', 'video_url'];

    /**
     * @param  array<string, scalar|null>  $variables
     * @return array{subject: string, html: string, text: string}
     */
    public function render(string $subject, string $body, array $variables): array
    {
        $plain = array_map(fn ($value): string => (string) $value, $variables);

        $safe = [];

        foreach ($plain as $key => $value) {
            $safe[$key] = in_array($key, self::LINK_PLACEHOLDERS, true)
                ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                : $this->escapeMarkdown($value);
        }

        return [
            'subject' => trim(preg_replace('/\s+/', ' ', $this->substitute($subject, $plain))),
            'html' => Str::markdown($this->substitute($body, $safe), [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
            'text' => $this->substitute($body, $plain),
        ];
    }

    /**
     * The values available to every template, for the given lead and/or order.
     *
     * @return array<string, string>
     */
    public static function variables(?Lead $lead = null, ?Order $order = null): array
    {
        return [
            'name' => $order?->name ?? $lead?->name ?? '',
            'email' => $order?->email ?? $lead?->email ?? '',
            'video_url' => (string) Settings::get('content.lead_video_url', ''),
            'whatsapp' => Site::whatsapp(),
            'plan' => $order?->plan_name ?? '',
            'amount' => $order ? $order->formattedAmount() : '',
            'order_number' => $order?->number ?? '',
            'site_name' => config('site.name'),
        ];
    }

    /**
     * Example values for the template editor's live preview.
     *
     * @return array<string, string>
     */
    public static function sampleVariables(): array
    {
        return [
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'video_url' => (string) Settings::get('content.lead_video_url', 'https://example.com/video'),
            'whatsapp' => Site::whatsapp(),
            'plan' => 'Pathway Mentorship',
            'amount' => Money::usd(30000),
            'order_number' => 'DPT-1042',
            'site_name' => config('site.name'),
        ];
    }

    /**
     * Replace {{placeholder}} tokens. Anything else in double braces, such as
     * `{{ 7*7 }}`, is not a placeholder and is left as literal text.
     *
     * @param  array<string, string>  $values
     */
    protected function substitute(string $text, array $values): string
    {
        return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', function (array $match) use ($values): string {
            return array_key_exists($match[1], self::PLACEHOLDERS) ? ($values[$match[1]] ?? '') : $match[0];
        }, $text);
    }

    /**
     * Stop a visitor-supplied value (e.g. a name) from being read as Markdown
     * or HTML: every ASCII punctuation character is backslash-escaped, so the
     * renderer prints it as literal, HTML-escaped text — no formatting, no
     * tags and no auto-linked URLs.
     */
    protected function escapeMarkdown(string $value): string
    {
        return preg_replace('/([!-\/:-@\[-`{-~])/', '\\\\$1', $value);
    }
}
