<?php

namespace Tests;

use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * An active affiliate; log in as `$affiliate->user`.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function affiliate(array $attributes = []): Affiliate
    {
        return Affiliate::factory()->create($attributes);
    }

    /**
     * A valid eligibility form submission, as resources/js/forms.js sends it.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function leadPayload(array $overrides = []): array
    {
        return [
            'name' => 'Ayesha Khan',
            'email' => 'ayesha@example.com',
            'whatsapp' => '+92 300 1234567',
            'graduation_year' => '2025',
            'university' => 'Riphah International University',
            'clinical_experience' => 'Less than 1 year',
            'toefl' => 'Preparing',
            'fccpt' => 'Not yet',
            '_elapsed' => 8000,
            ...$overrides,
        ];
    }
}
