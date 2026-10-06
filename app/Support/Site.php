<?php

namespace App\Support;

/**
 * Contact and content details shown on the public site, read from Settings
 * with the placeholders in config/site.php as the fallback.
 */
class Site
{
    public static function email(): string
    {
        return (string) Settings::get('contact.email');
    }

    public static function whatsapp(): string
    {
        return (string) Settings::get('contact.whatsapp');
    }

    /**
     * A wa.me chat link, or null while the number is still a placeholder.
     */
    public static function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D/', '', static::whatsapp());

        return strlen($digits) >= 8 ? 'https://wa.me/'.$digits : null;
    }

    /**
     * A mailto link, or null while the address is still a placeholder.
     */
    public static function emailUrl(): ?string
    {
        $email = static::email();

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$email : null;
    }

    public static function introVideoEmbedUrl(): ?string
    {
        return VideoEmbed::url(Settings::get('content.intro_video_url'));
    }

    public static function attributionDays(): int
    {
        return max(1, (int) Settings::get('affiliates.attribution_days'));
    }

    public static function defaultCommissionRate(): float
    {
        return (float) Settings::get('affiliates.default_commission_rate');
    }

    /**
     * The bank account shown at checkout.
     *
     * @return array{bank_name: ?string, account_title: ?string, account_number: ?string, iban: ?string, instructions: ?string}
     */
    public static function bankDetails(): array
    {
        return [
            'bank_name' => Settings::get('payment.bank_name'),
            'account_title' => Settings::get('payment.account_title'),
            'account_number' => Settings::get('payment.account_number'),
            'iban' => Settings::get('payment.iban'),
            'instructions' => Settings::get('payment.instructions'),
        ];
    }

    /**
     * Split a comma-separated recipient setting into valid addresses.
     *
     * @return list<string>
     */
    public static function recipients(string $key): array
    {
        return collect(preg_split('/[\s,;]+/', (string) Settings::get($key)))
            ->filter(fn (string $address): bool => filter_var($address, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }
}
