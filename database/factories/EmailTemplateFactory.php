<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => null,
            'name' => fake()->unique()->words(3, true),
            'subject' => 'A note for {{name}}',
            'body' => "Hi {{name}},\n\nThank you for your interest.",
            'is_system' => false,
        ];
    }

    /**
     * A built-in template the site depends on, e.g. "lead_video".
     */
    public function system(string $key): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
            'is_system' => true,
        ]);
    }
}
