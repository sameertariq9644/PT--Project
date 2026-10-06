<?php

namespace Database\Factories;

use App\Models\Affiliate;
use App\Models\AffiliateDailyStat;
use App\Support\SiteTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliateDailyStat>
 */
class AffiliateDailyStatFactory extends Factory
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
            'date' => SiteTime::today()->toDateString(),
            'campaign' => '',
            'clicks' => 10,
            'unique_visitors' => 7,
        ];
    }
}
