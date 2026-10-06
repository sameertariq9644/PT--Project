<?php

namespace Database\Factories;

use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Affiliate>
 */
class AffiliateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => Str::upper(fake()->unique()->bothify('????##')),
            'channel' => fake()->randomElement(['Instagram', 'YouTube', 'Facebook', 'WhatsApp groups']),
            'commission_rate' => 20,
            'payout_details' => null,
            'is_active' => true,
        ];
    }

    /**
     * A paused affiliate: no attribution and no login.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'user_id' => User::factory()->inactive(),
        ]);
    }
}
