<?php

namespace Tests\Feature\Admin;

use App\Models\Lead;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QueryCountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guards the admin lists against N+1 queries: the number of queries must
     * not grow with the number of rows on the page.
     */
    #[DataProvider('lists')]
    public function test_admin_lists_run_the_same_number_of_queries_however_many_rows_they_show(string $uri): void
    {
        $affiliate = $this->affiliate();
        $this->actingAs($this->admin());

        $seed = function (int $count) use ($affiliate): void {
            Lead::factory()->count($count)->create();
            Order::factory()->pending()->referredBy($affiliate)->count($count)->create();
            $this->affiliate();
        };

        $seed(1);
        $few = $this->countQueries($uri);

        $seed(15);
        $many = $this->countQueries($uri);

        $this->assertSame($few, $many);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function lists(): array
    {
        return [
            'leads' => ['/admin/leads'],
            'orders' => ['/admin/orders'],
            'affiliates' => ['/admin/affiliates'],
            'affiliate stats' => ['/admin/affiliates/stats'],
        ];
    }

    private function countQueries(string $uri): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get($uri)->assertOk();

        DB::disableQueryLog();

        return count(DB::getQueryLog());
    }
}
