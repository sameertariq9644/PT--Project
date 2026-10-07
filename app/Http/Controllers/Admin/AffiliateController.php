<?php

namespace App\Http\Controllers\Admin;

use App\Actions\MarkPayoutPaid;
use App\Actions\SaveAffiliate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkPayoutPaidRequest;
use App\Http\Requests\Admin\SaveAffiliateRequest;
use App\Http\Requests\StatsRequest;
use App\Models\Affiliate;
use App\Support\AffiliateStats;
use App\Support\Csv;
use App\Support\Site;
use App\Support\SiteTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AffiliateController extends Controller
{
    public function __construct(private AffiliateStats $stats) {}

    public function index(): View
    {
        return view('admin.affiliates', [
            'affiliates' => Affiliate::query()->with('user:id,name')->get()->sortBy('user.name')->values(),
            'defaultRate' => Site::defaultCommissionRate(),
            'today' => SiteTime::today(),
        ]);
    }

    /**
     * The numbers behind the KPI cards, the chart and the per-affiliate table.
     */
    public function stats(StatsRequest $request): JsonResponse
    {
        [$from, $to] = $this->stats->resolveRange($request->range(), $request->validated('from'), $request->validated('to'));

        $affiliate = $this->selectedAffiliate($request);
        $rows = $this->stats->perAffiliate($from, $to);

        return response()->json([
            ...$this->stats->summary($affiliate, $from, $to),
            'rows' => $affiliate ? array_values(array_filter($rows, fn (array $row): bool => $row['id'] === $affiliate->id)) : $rows,
        ]);
    }

    public function export(StatsRequest $request): StreamedResponse
    {
        [$from, $to] = $this->stats->resolveRange($request->range(), $request->validated('from'), $request->validated('to'));

        $rows = array_map(fn (array $row): array => [
            $row['name'], $row['code'], $row['channel'], $row['clicks'], $row['visitors'], $row['checkouts'],
            $row['sales'], $row['revenue'], $row['commission'], $row['active'] ? 'Active' : 'Paused',
        ], $this->stats->perAffiliate($from, $to));

        return Csv::download(
            'affiliates-'.$from->toDateString().'-to-'.$to->toDateString().'.csv',
            ['Affiliate', 'Code', 'Channel', 'Clicks', 'Unique visitors', 'Checkouts', 'Sales', 'Revenue (USD)', 'Commission (USD)', 'Status'],
            $rows,
        );
    }

    public function create(): View
    {
        return view('admin.affiliate-form', [
            'affiliate' => null,
            'defaultRate' => Site::defaultCommissionRate(),
            'suggestedCode' => $this->suggestCode(),
        ]);
    }

    public function store(SaveAffiliateRequest $request, SaveAffiliate $saveAffiliate): RedirectResponse
    {
        $affiliate = $saveAffiliate->handle($request->validated());

        return redirect()
            ->route('admin.affiliates.show', $affiliate)
            ->with('status', 'Affiliate created. Share the username and password with them.');
    }

    public function show(Affiliate $affiliate): View
    {
        return view('admin.affiliate', [
            'affiliate' => $affiliate->load('user'),
            'months' => $this->stats->monthly($affiliate),
            'earnings' => $this->stats->earnings($affiliate),
            'link' => route('home', ['ref' => $affiliate->code]),
        ]);
    }

    public function edit(Affiliate $affiliate): View
    {
        return view('admin.affiliate-form', [
            'affiliate' => $affiliate->load('user'),
            'defaultRate' => Site::defaultCommissionRate(),
            'suggestedCode' => $affiliate->code,
        ]);
    }

    public function update(SaveAffiliateRequest $request, Affiliate $affiliate, SaveAffiliate $saveAffiliate): RedirectResponse
    {
        $saveAffiliate->handle($request->validated(), $affiliate);

        return redirect()->route('admin.affiliates.show', $affiliate)->with('status', 'Affiliate updated.');
    }

    public function markPaid(MarkPayoutPaidRequest $request, Affiliate $affiliate, MarkPayoutPaid $markPayoutPaid): RedirectResponse
    {
        $period = $request->validated('period');

        if (! $this->stats->isCompletedMonth($period)) {
            return back()->withErrors(['period' => 'Only completed months can be paid out.']);
        }

        $payout = $markPayoutPaid->handle($affiliate, $period, $request->validated('note'));

        return back()->with('status', $payout->wasRecentlyCreated
            ? $period.' marked as paid.'
            : $period.' was already marked as paid, nothing changed.');
    }

    /**
     * The same printable invoice the affiliate sees in their portal.
     */
    public function invoice(Affiliate $affiliate, string $month): View
    {
        abort_unless($this->stats->isCompletedMonth($month), 404);

        return view('affiliate.invoice', [
            'affiliate' => $affiliate->load('user'),
            'invoice' => $this->stats->invoice($affiliate, $month),
            'backUrl' => route('admin.affiliates.show', $affiliate),
        ]);
    }

    protected function selectedAffiliate(StatsRequest $request): ?Affiliate
    {
        $id = $request->validated('affiliate');

        return is_numeric($id) ? Affiliate::query()->with('user:id,name')->find((int) $id) : null;
    }

    protected function suggestCode(): string
    {
        do {
            $code = 'REF'.Str::upper(Str::random(5));
        } while (Affiliate::query()->where('code', $code)->exists());

        return $code;
    }
}
