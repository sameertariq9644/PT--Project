<?php

namespace Database\Seeders;

use App\Support\DefaultEmailTemplates;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Create the system email templates that are not there yet.
     */
    public function run(): void
    {
        foreach (array_keys(DefaultEmailTemplates::all()) as $key) {
            DefaultEmailTemplates::resolve($key);
        }
    }
}
