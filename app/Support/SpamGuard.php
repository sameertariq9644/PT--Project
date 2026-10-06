<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Cheap bot filtering for the session-less public forms: a honeypot field
 * that people never see, and a minimum time between page load and submit
 * (reported by resources/js/forms.js as `_elapsed` milliseconds).
 *
 * This is the single seam for form abuse protection — verify a Cloudflare
 * Turnstile token here when one is added.
 */
class SpamGuard
{
    public function isSpam(Request $request): bool
    {
        if (filled($request->input(config('site.spam.honeypot')))) {
            return true;
        }

        $elapsed = $request->input('_elapsed');

        return ! is_numeric($elapsed) || (int) $elapsed < config('site.spam.min_fill_seconds') * 1000;
    }
}
