<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Timestamps are stored in UTC; anything a person reads, and every "which day
 * was this" decision, uses the site's timezone (Asia/Karachi).
 */
class SiteTime
{
    public static function zone(): string
    {
        return config('site.timezone', 'Asia/Karachi');
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(static::zone());
    }

    public static function today(): CarbonImmutable
    {
        return static::now()->startOfDay();
    }

    public static function local(DateTimeInterface $moment): CarbonImmutable
    {
        return CarbonImmutable::instance($moment)->setTimezone(static::zone());
    }

    /**
     * The site-local calendar day (Y-m-d) a moment falls on.
     */
    public static function date(DateTimeInterface $moment): string
    {
        return static::local($moment)->toDateString();
    }

    public static function format(?DateTimeInterface $moment, string $format = 'M j'): string
    {
        return $moment ? static::local($moment)->format($format) : '-';
    }
}
