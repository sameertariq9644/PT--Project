<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'whatsapp' => '+92 300 '.fake()->numerify('#######'),
            'answers' => [
                'graduation_year' => '2025',
                'university' => 'Riphah International University',
                'clinical_experience' => 'Less than 1 year',
                'fccpt' => 'Not yet',
            ],
            'status' => LeadStatus::NeedsReview,
        ];
    }

    public function status(LeadStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
