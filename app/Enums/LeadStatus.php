<?php

namespace App\Enums;

enum LeadStatus: string
{
    case NeedsReview = 'needs_review';
    case Eligible = 'eligible';
    case NotEligible = 'not_eligible';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::NeedsReview => 'Needs review',
            self::Eligible => 'Eligible',
            self::NotEligible => 'Not eligible',
            self::Customer => 'Customer',
        };
    }

    /**
     * The `.status--*` modifier used to render this status.
     */
    public function tone(): string
    {
        return match ($this) {
            self::NeedsReview => 'amber',
            self::Eligible => 'green',
            self::NotEligible => 'grey',
            self::Customer => 'dark',
        };
    }
}
