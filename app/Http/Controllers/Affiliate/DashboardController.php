<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatsRequest;
use App\Support\AffiliateStats;
use App\Support\Site;
use App\Support\SiteTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The affiliate's own portal. Every query is scoped through the logged-in
 * user's affiliate — an affiliate id is never accepted from the request.
 */
class DashboardController extends Controller
{
    public function __construct(private AffiliateStats $stats) {}

    public function index(Request $request): View
    {
        $affiliate = $request->user()->affiliate;

        return view('affiliate.portal', [
            'user' => $request->user(),
            'affiliate' => $affiliate,
            'pages' => [
                '/' => 'Homepage',
                '/eligibility' => 'Eligibility check',
                '/services' => 'Services',
                '/pathway' => 'The Pathway',
            ],
            'earnings' => $this->stats->earnings($affiliate),
            'referrals' => $this->stats->referrals($affiliate),
            'topLinks' => $this->stats->topLinks($affiliate),
            'attributionDays' => Site::attributionDays(),
            'today' => SiteTime::today(),
            'lastMonth' => SiteTime::today()->subMonthNoOverflow()->format('Y-m'),
        ]);
    }

    public function stats(StatsRequest $request): JsonResponse
    {
        [$from, $to] = $this->stats->resolveRange($request->range(), $request->validated('from'), $request->validated('to'));

        return response()->json($this->stats->summary($request->user()->affiliate, $from, $to));
    }
}
