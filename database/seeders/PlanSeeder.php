<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Support\SiteContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    /**
     * Create the three plans from the design copy. Prices start empty
     * ("$[-]") and are set under Admin → Services.
     */
    public function run(): void
    {
        foreach (SiteContent::plans() as $position => $plan) {
            Plan::query()->firstOrCreate(['slug' => Str::slug($plan['name'])], [
                'name' => $plan['name'],
                'kicker' => $plan['kicker'],
                'teaser' => $plan['teaser'],
                'description' => $plan['description'],
                'price' => null,
                'is_featured' => $plan['featured'],
                'highlights' => $plan['highlights'],
                'features' => $plan['features'],
                'sort_order' => $position + 1,
                'is_active' => true,
            ]);
        }
    }
}
