<?php

namespace Database\Factories;

use App\Models\Affiliate;
use App\Models\AffiliatePayout;
use App\Support\SiteTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliatePayout>
 */
class AffiliatePayoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'affiliate_id' => Affiliate::factory(),
            'period' => SiteTime::today()->subMonthNoOverflow()->format('Y-m'),
            'amount' => 6000,
            'paid_at' => now(),
            'note' => null,
        ];
    }
}
