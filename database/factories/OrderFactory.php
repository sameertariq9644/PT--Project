<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Affiliate;
use App\Models\Order;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'DPT-'.fake()->unique()->numberBetween(100000, 999999),
            'plan_id' => Plan::factory(),
            'plan_name' => 'Pathway Mentorship',
            'amount' => 30000,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'whatsapp' => '+92 300 '.fake()->numerify('#######'),
            'status' => OrderStatus::AwaitingPayment,
        ];
    }

    /**
     * The customer has uploaded a receipt; waiting for the admin.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Pending,
            'transaction_id' => 'FT'.fake()->numerify('##########'),
            'transferred_on' => now()->toDateString(),
            'receipt_path' => 'receipts/'.fake()->uuid().'.jpg',
        ]);
    }

    public function confirmed(): static
    {
        return $this->pending()->state(fn (array $attributes) => [
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
            'commission_amount' => isset($attributes['commission_rate'])
                ? (int) round($attributes['amount'] * $attributes['commission_rate'] / 100)
                : 0,
        ]);
    }

    public function rejected(): static
    {
        return $this->pending()->state(fn (array $attributes) => [
            'status' => OrderStatus::Rejected,
            'rejected_at' => now(),
            'admin_note' => 'Receipt unclear',
        ]);
    }

    /**
     * Attribute the order to an affiliate, snapshotting their commission rate.
     */
    public function referredBy(Affiliate $affiliate): static
    {
        return $this->state(fn (array $attributes) => [
            'affiliate_id' => $affiliate->id,
            'commission_rate' => $affiliate->commission_rate,
        ]);
    }
}
