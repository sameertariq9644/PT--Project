<?php

namespace App\Actions;

use App\Models\Affiliate;
use App\Models\AffiliatePayout;
use App\Support\AffiliateStats;

class MarkPayoutPaid
{
    public function __construct(private AffiliateStats $stats) {}

    /**
     * Record that the affiliate's commission for a month (YYYY-MM) has been
     * paid. Marking the same month twice keeps the first payout.
     */
    public function handle(Affiliate $affiliate, string $period, ?string $note = null): AffiliatePayout
    {
        return $affiliate->payouts()->firstOrCreate(
            ['period' => $period],
            [
                'amount' => $this->stats->commissionForMonth($affiliate, $period),
                'paid_at' => now(),
                'note' => $note,
            ],
        );
    }
}
