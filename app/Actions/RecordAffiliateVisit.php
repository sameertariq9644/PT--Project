<?php

namespace App\Actions;

use App\Models\Affiliate;
use App\Models\AffiliateDailyStat;
use App\Models\AffiliateVisit;
use App\Support\Attribution;
use App\Support\SiteTime;
use Illuminate\Support\Facades\DB;

class RecordAffiliateVisit
{
    /**
     * Fold one affiliate link click into the daily aggregate. A visitor is
     * counted as unique once per affiliate per day.
     */
    public function handle(string $code, string $visitorId, ?string $campaign = null): void
    {
        $affiliate = Affiliate::findActiveByCode($code);

        if ($affiliate === null) {
            return;
        }

        $date = SiteTime::today()->toDateString();
        $now = now();

        DB::transaction(function () use ($affiliate, $visitorId, $campaign, $date, $now): void {
            $isNewVisitor = AffiliateVisit::query()->insertOrIgnore([
                'affiliate_id' => $affiliate->id,
                'visitor_id' => $visitorId,
                'date' => $date,
                'created_at' => $now,
                'updated_at' => $now,
            ]) > 0;

            AffiliateDailyStat::query()->upsert(
                [[
                    'affiliate_id' => $affiliate->id,
                    'date' => $date,
                    'campaign' => Attribution::normalizeCampaign($campaign) ?? '',
                    'clicks' => 1,
                    'unique_visitors' => $isNewVisitor ? 1 : 0,
                ]],
                ['affiliate_id', 'date', 'campaign'],
                [
                    'clicks' => DB::raw('affiliate_daily_stats.clicks + 1'),
                    'unique_visitors' => DB::raw('affiliate_daily_stats.unique_visitors + '.($isNewVisitor ? 1 : 0)),
                    'updated_at' => $now,
                ],
            );
        });
    }
}
