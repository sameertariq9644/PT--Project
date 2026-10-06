<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Every seeder is safe to run again:
     * it only adds what is missing and never overwrites an admin's edits.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            EmailTemplateSeeder::class,
            SettingSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
