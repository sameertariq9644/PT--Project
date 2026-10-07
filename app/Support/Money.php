<?php

namespace App\Support;

class Money
{
    /**
     * Format a whole number of US dollars, e.g. "$30,000". A missing amount
     * renders as the "$[-]" placeholder used in the designs.
     */
    public static function usd(?int $amount): string
    {
        return $amount === null ? '$[-]' : '$'.number_format($amount);
    }
}
