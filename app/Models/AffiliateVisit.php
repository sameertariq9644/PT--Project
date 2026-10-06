<?php

namespace App\Models;

use App\Support\SiteTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * One row per affiliate, visitor and day. Only used to decide whether a click
 * counts as a new unique visitor; dashboards read AffiliateDailyStat instead.
 */
class AffiliateVisit extends Model
{
    use Prunable;

    /**
     * @return Builder<AffiliateVisit>
     */
    public function prunable(): Builder
    {
        return static::query()->where('date', '<', SiteTime::today()->subDays(45)->toDateString());
    }
}
