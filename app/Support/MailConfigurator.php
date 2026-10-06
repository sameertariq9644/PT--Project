<?php

namespace App\Support;

use Illuminate\Support\Facades\Mail;

/**
 * Applies the "Outgoing email" settings on top of the .env mail config.
 * Runs at boot and before every queued job, so long-running workers pick up
 * changes made in Admin → Settings. With no SMTP host saved, .env is used.
 */
class MailConfigurator
{
    public static function apply(): void
    {
        $changed = false;

        $fromAddress = Settings::get('mail.from_address');
        $fromName = Settings::get('mail.from_name');

        if (filled($fromAddress) && config('mail.from.address') !== $fromAddress) {
            config(['mail.from.address' => $fromAddress]);
            $changed = true;
        }

        if (filled($fromName) && config('mail.from.name') !== $fromName) {
            config(['mail.from.name' => $fromName]);
            $changed = true;
        }

        $host = Settings::get('mail.host');

        if (filled($host)) {
            $port = (int) (Settings::get('mail.port') ?: 587);
            $encryption = Settings::get('mail.encryption') ?: ($port === 465 ? 'ssl' : 'tls');

            $smtp = [
                'transport' => 'smtp',
                'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
                'host' => $host,
                'port' => $port,
                'username' => Settings::get('mail.username'),
                'password' => Settings::secret('mail.password'),
                'timeout' => 15,
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            ];

            if (config('mail.mailers.smtp') != $smtp || config('mail.default') !== 'smtp') {
                config(['mail.default' => 'smtp', 'mail.mailers.smtp' => $smtp]);
                $changed = true;
            }
        }

        if ($changed && app()->resolved('mail.manager')) {
            Mail::forgetMailers();
        }
    }
}
