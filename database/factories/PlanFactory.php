<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'kicker' => 'Starting out',
            'teaser' => fake()->sentence(),
            'description' => fake()->sentence(),
            'price' => 30000,
            'is_featured' => false,
            'highlights' => ['60-minute video call'],
            'features' => ['60-minute video call', 'Personal roadmap'],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    /**
     * A plan whose price the owner has not set yet.
     */
    public function unpriced(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
