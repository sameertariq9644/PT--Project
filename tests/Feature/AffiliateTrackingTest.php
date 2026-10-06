<?php

namespace Tests\Feature;

use App\Jobs\RecordAffiliateVisitJob;
use App\Models\AffiliateDailyStat;
use App\Models\AffiliateVisit;
use App\Support\SiteTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AffiliateTrackingTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/140.0 Safari/537.36'];

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function visit(array $overrides = []): TestResponse
    {
        return $this->postJson('/t/visit', ['ref' => 'SANA20', 'vid' => (string) Str::uuid(), 'campaign' => null, 'path' => '/', ...$overrides], self::BROWSER);
    }

    public function test_a_visit_only_queues_a_job_on_the_tracking_queue(): void
    {
        Queue::fake();
        config(['site.queues.tracking' => 'tracking']);

        $visitor = (string) Str::uuid();

        $response = $this->visit(['vid' => $visitor, 'campaign' => 'npte-reel'])->assertNoContent();

        $this->assertSame([], $response->headers->getCookies());

        Queue::assertPushedOn('tracking', RecordAffiliateVisitJob::class, function (RecordAffiliateVisitJob $job) use ($visitor): bool {
            return $job->code === 'SANA20' && $job->visitorId === $visitor && $job->campaign === 'npte-reel';
        });
    }

    public function test_clicks_always_count_but_a_visitor_is_unique_once_per_day(): void
    {
        $affiliate = $this->affiliate(['code' => 'SANA20']);
        $visitor = (string) Str::uuid();

        $this->visit(['vid' => $visitor]);
        $this->visit(['vid' => $visitor, 'ref' => 'sana20']);
        $this->visit();

        $stat = AffiliateDailyStat::query()->sole();

        $this->assertSame($affiliate->id, $stat->affiliate_id);
        $this->assertSame(SiteTime::today()->toDateString(), substr((string) $stat->date, 0, 10));
        $this->assertSame(3, $stat->clicks);
        $this->assertSame(2, $stat->unique_visitors);

        $this->travel(1)->days();
        $this->visit(['vid' => $visitor]);

        $nextDay = AffiliateDailyStat::query()->latest('date')->first();

        $this->assertSame(1, $nextDay->clicks);
        $this->assertSame(1, $nextDay->unique_visitors);
    }

    public function test_clicks_are_bucketed_per_campaign(): void
    {
        $this->affiliate(['code' => 'SANA20']);
        $visitor = (string) Str::uuid();

        $this->visit(['vid' => $visitor, 'campaign' => 'NPTE Reel']);
        $this->visit(['vid' => $visitor]);

        $this->assertEqualsCanonicalizing(['npte-reel', ''], AffiliateDailyStat::query()->pluck('campaign')->all());
        $this->assertSame(1, (int) AffiliateDailyStat::query()->sum('unique_visitors'));
    }

    public function test_unknown_and_paused_codes_record_nothing(): void
    {
        $this->affiliate(['code' => 'PAUSED', 'is_active' => false]);

        $this->visit(['ref' => 'PAUSED'])->assertNoContent();
        $this->visit(['ref' => 'NOBODY'])->assertNoContent();

        $this->assertDatabaseCount('affiliate_daily_stats', 0);
        $this->assertDatabaseCount('affiliate_visits', 0);
    }

    public function test_bots_are_ignored(): void
    {
        $this->affiliate(['code' => 'SANA20']);

        $this->postJson('/t/visit', ['ref' => 'SANA20', 'vid' => (string) Str::uuid()], ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)'])
            ->assertNoContent();

        $this->assertDatabaseCount('affiliate_daily_stats', 0);
    }

    public function test_malformed_payloads_are_rejected(): void
    {
        $this->visit(['vid' => 'not-a-uuid', 'ref' => 'DROP TABLE'])->assertJsonValidationErrors(['vid', 'ref']);
    }

    public function test_old_visit_rows_are_pruned_but_aggregates_are_kept(): void
    {
        $affiliate = $this->affiliate(['code' => 'SANA20']);

        $this->visit();
        $this->travel(46)->days();
        $this->visit();

        $this->artisan('model:prune', ['--model' => [AffiliateVisit::class]])->assertSuccessful();

        $this->assertDatabaseCount('affiliate_visits', 1);
        $this->assertSame(2, $affiliate->dailyStats()->count());
    }
}
