<?php

namespace App\Support;

use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Models\Lead;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;

/**
 * The badge numbers in the admin sidebar: leads waiting for review and
 * orders waiting for payment verification.
 */
class AdminNavCounts
{
    public const CACHE_KEY = 'admin.nav_counts';

    /**
     * @return array{leads: int, orders: int}
     */
    public static function get(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinute(), fn (): array => [
            'leads' => Lead::query()->where('status', LeadStatus::NeedsReview)->count(),
            'orders' => Order::query()->where('status', OrderStatus::Pending)->count(),
        ]);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
