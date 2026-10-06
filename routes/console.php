<?php

use App\Models\AffiliateVisit;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Raw affiliate visit rows are only needed to decide uniqueness; drop them after 45 days.
Schedule::command('model:prune', ['--model' => [AffiliateVisit::class]])->daily();

Schedule::command('queue:prune-failed', ['--hours' => 24 * 30])->weekly();
