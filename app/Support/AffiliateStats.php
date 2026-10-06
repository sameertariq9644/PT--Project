<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Affiliate;
use App\Models\AffiliateDailyStat;
use App\Models\AffiliatePayout;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Numbers for the admin affiliates page and the affiliate portal.
 *
 * Clicks and unique visitors come from the daily aggregate table. Checkouts,
 * sales, revenue and commission are always computed from `orders`, which is
 * low volume and always correct. Everything is grouped in SQL; nothing here
 * loops over raw click events.
 */
class AffiliateStats
{
    public const RANGES = ['7d', '30d', 'tm', 'lm', 'yr', 'custom'];

    protected const METRICS = ['clicks', 'visitors', 'checkouts', 'sales', 'revenue', 'commission'];

    /**
     * Turn a range id from the range bar into site-local start and end days.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function resolveRange(string $range, ?string $from = null, ?string $to = null): array
    {
        $today = SiteTime::today();

        return match ($range) {
            '7d' => [$today->subDays(6), $today],
            'tm' => [$today->startOfMonth(), $today],
            'lm' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            'yr' => [$today->startOfYear(), $today],
            'custom' => $this->customRange($from, $to, $today),
            default => [$today->subDays(29), $today],
        };
    }

    /**
     * Totals, previous-period totals and chart buckets for a date range,
     * for one affiliate or (when null) all of them.
     *
     * @return array{text: string, monthly: bool, cur: array<string, int>, pv: array<string, int>, buckets: list<array<string, int|string>>}
     */
    public function summary(?Affiliate $affiliate, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = (int) $from->diffInDays($to) + 1;
        $previousFrom = $from->subDays($days);
        $window = [$previousFrom->toDateString(), $to->toDateString()];

        $byDay = [];

        $stats = AffiliateDailyStat::query()
            ->when($affiliate, fn ($query) => $query->where('affiliate_id', $affiliate->id))
            ->whereBetween('date', $window)
            ->groupBy('date')
            ->selectRaw('date as day, sum(clicks) as clicks, sum(unique_visitors) as visitors')
            ->toBase()
            ->get();

        $placed = Order::query()
            ->when($affiliate, fn ($query) => $query->where('affiliate_id', $affiliate->id), fn ($query) => $query->whereNotNull('affiliate_id'))
            ->whereBetween('placed_on', $window)
            ->groupBy('placed_on')
            ->selectRaw('placed_on as day, count(*) as checkouts')
            ->toBase()
            ->get();

        $confirmed = Order::query()
            ->when($affiliate, fn ($query) => $query->where('affiliate_id', $affiliate->id), fn ($query) => $query->whereNotNull('affiliate_id'))
            ->where('status', OrderStatus::Confirmed->value)
            ->whereBetween('confirmed_on', $window)
            ->groupBy('confirmed_on')
            ->selectRaw('confirmed_on as day, count(*) as sales, sum(amount) as revenue, sum(commission_amount) as commission')
            ->toBase()
            ->get();

        foreach ([$stats, $placed, $confirmed] as $rows) {
            foreach ($rows as $row) {
                $day = substr((string) $row->day, 0, 10);

                foreach (self::METRICS as $metric) {
                    if (isset($row->{$metric})) {
                        $byDay[$day][$metric] = (int) $row->{$metric};
                    }
                }
            }
        }

        $monthly = $days > 92;
        $buckets = [];
        $current = $this->emptyTotals();
        $previous = $this->emptyTotals();

        for ($day = $previousFrom; $day->lte($to); $day = $day->addDay()) {
            $values = $byDay[$day->toDateString()] ?? [];
            $isCurrent = $day->gte($from);

            if ($isCurrent) {
                $key = $monthly ? $day->format('Y-m') : $day->toDateString();
                $buckets[$key] ??= ['lab' => $monthly ? $day->format('M Y') : $day->format('M j'), ...$this->emptyTotals()];
            }

            foreach (self::METRICS as $metric) {
                $value = $values[$metric] ?? 0;

                if ($isCurrent) {
                    $current[$metric] += $value;
                    $buckets[$key][$metric] += $value;
                } else {
                    $previous[$metric] += $value;
                }
            }
        }

        return [
            'text' => $this->rangeText($from, $to),
            'monthly' => $monthly,
            'cur' => $current,
            'pv' => $previous,
            'buckets' => array_values($buckets),
        ];
    }

    /**
     * One row per affiliate for the admin table, for a date range.
     *
     * @return list<array<string, mixed>>
     */
    public function perAffiliate(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $window = [$from->toDateString(), $to->toDateString()];

        $stats = AffiliateDailyStat::query()
            ->whereBetween('date', $window)
            ->groupBy('affiliate_id')
            ->selectRaw('affiliate_id, sum(clicks) as clicks, sum(unique_visitors) as visitors')
            ->toBase()->get()->keyBy('affiliate_id');

        $placed = Order::query()
            ->whereNotNull('affiliate_id')
            ->whereBetween('placed_on', $window)
            ->groupBy('affiliate_id')
            ->selectRaw('affiliate_id, count(*) as checkouts')
            ->toBase()->get()->keyBy('affiliate_id');

        $confirmed = Order::query()
            ->whereNotNull('affiliate_id')
            ->where('status', OrderStatus::Confirmed->value)
            ->whereBetween('confirmed_on', $window)
            ->groupBy('affiliate_id')
            ->selectRaw('affiliate_id, count(*) as sales, sum(amount) as revenue, sum(commission_amount) as commission')
            ->toBase()->get()->keyBy('affiliate_id');

        return Affiliate::query()->with('user:id,name')->get()
            ->map(fn (Affiliate $affiliate): array => [
                'id' => $affiliate->id,
                'name' => $affiliate->user->name,
                'initials' => $affiliate->user->initials(),
                'code' => $affiliate->code,
                'channel' => $affiliate->channel,
                'active' => $affiliate->is_active,
                'rate' => $affiliate->commission_rate,
                'clicks' => (int) ($stats[$affiliate->id]->clicks ?? 0),
                'visitors' => (int) ($stats[$affiliate->id]->visitors ?? 0),
                'checkouts' => (int) ($placed[$affiliate->id]->checkouts ?? 0),
                'sales' => (int) ($confirmed[$affiliate->id]->sales ?? 0),
                'revenue' => (int) ($confirmed[$affiliate->id]->revenue ?? 0),
                'commission' => (int) ($confirmed[$affiliate->id]->commission ?? 0),
                'url' => route('admin.affiliates.show', $affiliate),
            ])
            ->sortByDesc('revenue')
            ->values()
            ->all();
    }

    /**
     * The three earnings cards in the portal.
     *
     * @return array{pending: int, pending_count: int, approved: int, paid: int}
     */
    public function earnings(Affiliate $affiliate): array
    {
        $pending = $affiliate->orders()->where('status', OrderStatus::Pending->value)->get(['id', 'affiliate_id', 'amount', 'commission_rate']);
        $months = $this->monthly($affiliate);

        return [
            'pending' => $pending->sum(fn (Order $order): int => $order->expectedCommission()),
            'pending_count' => $pending->count(),
            'approved' => (int) $months->whereNull('payout')->sum('commission'),
            'paid' => (int) $affiliate->payouts()->sum('amount'),
        ];
    }

    /**
     * Confirmed sales grouped by the month they were confirmed in, newest first.
     *
     * @return Collection<int, array{period: string, label: string, sales: int, revenue: int, commission: int, complete: bool, payout: ?AffiliatePayout}>
     */
    public function monthly(Affiliate $affiliate): Collection
    {
        $payouts = $affiliate->payouts()->get()->keyBy('period');
        $currentPeriod = SiteTime::today()->format('Y-m');

        return $affiliate->orders()
            ->where('status', OrderStatus::Confirmed->value)
            ->toBase()
            ->get(['confirmed_on', 'amount', 'commission_amount'])
            ->groupBy(fn (object $order): string => substr((string) $order->confirmed_on, 0, 7))
            ->map(fn (Collection $orders, string $period): array => [
                'period' => $period,
                'label' => CarbonImmutable::createFromFormat('!Y-m', $period)->format('F Y'),
                'sales' => $orders->count(),
                'revenue' => (int) $orders->sum('amount'),
                'commission' => (int) $orders->sum('commission_amount'),
                'complete' => $period < $currentPeriod,
                'payout' => $payouts->get($period),
            ])
            ->sortByDesc('period')
            ->values();
    }

    public function commissionForMonth(Affiliate $affiliate, string $period): int
    {
        return (int) $this->confirmedInMonth($affiliate, $period)->sum('commission_amount');
    }

    /**
     * Whether a YYYY-MM period is a real month that has already ended.
     */
    public function isCompletedMonth(string $period): bool
    {
        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) === 1 && $period < SiteTime::today()->format('Y-m');
    }

    /**
     * Everything the printable invoice for one month needs.
     *
     * @return array{number: string, period: string, label: string, issued: CarbonImmutable, lines: Collection<int, Order>, total: int, sales_total: int, payout: ?AffiliatePayout}
     */
    public function invoice(Affiliate $affiliate, string $period): array
    {
        $lines = $this->confirmedInMonth($affiliate, $period)->orderBy('confirmed_on')->orderBy('id')->get();
        $month = CarbonImmutable::createFromFormat('!Y-m', $period);

        return [
            'number' => 'INV-'.$affiliate->code.'-'.$month->format('Ym'),
            'period' => $period,
            'label' => $month->format('F Y'),
            'issued' => $month->endOfMonth(),
            'lines' => $lines,
            'total' => (int) $lines->sum('commission_amount'),
            'sales_total' => (int) $lines->sum('amount'),
            'payout' => $affiliate->payouts()->where('period', $period)->first(),
        ];
    }

    /**
     * The affiliate's most recent referred orders, with the student masked.
     *
     * @return list<array{date: string, student: string, plan: string, status: string, tone: string, earn: string}>
     */
    public function referrals(Affiliate $affiliate, int $limit = 8): array
    {
        $paidPeriods = $affiliate->payouts()->pluck('period')->all();

        return $affiliate->orders()->latest('id')->limit($limit)->get()
            ->map(function (Order $order) use ($paidPeriods): array {
                $paidOut = $order->status === OrderStatus::Confirmed
                    && in_array(substr((string) $order->confirmed_on, 0, 7), $paidPeriods, true);

                [$status, $tone] = match (true) {
                    $paidOut => ['Paid out', 'dark'],
                    $order->status === OrderStatus::Confirmed => ['Confirmed', 'green'],
                    $order->status === OrderStatus::Pending => ['Pending verification', 'amber'],
                    $order->status === OrderStatus::Rejected => ['Not verified', 'red'],
                    default => ['Checkout started', 'grey'],
                };

                $earns = match ($order->status) {
                    OrderStatus::Confirmed => $order->commission_amount,
                    OrderStatus::Pending => $order->expectedCommission(),
                    default => null,
                };

                return [
                    'date' => SiteTime::format($order->created_at),
                    'student' => $this->maskName($order->name),
                    'plan' => $order->plan_name,
                    'status' => $status,
                    'tone' => $tone,
                    'earn' => $earns === null ? '—' : Money::usd($earns),
                ];
            })
            ->all();
    }

    /**
     * The affiliate's best links, grouped by campaign tag.
     *
     * @return list<array{name: string, tag: string, clicks: int, sales: int}>
     */
    public function topLinks(Affiliate $affiliate, int $limit = 5): array
    {
        $sales = $affiliate->orders()
            ->where('status', OrderStatus::Confirmed->value)
            ->groupBy('utm_campaign')
            ->selectRaw('utm_campaign as campaign, count(*) as sales')
            ->toBase()->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->campaign => (int) $row->sales]);

        return $affiliate->dailyStats()
            ->groupBy('campaign')
            ->selectRaw('campaign, sum(clicks) as clicks')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->toBase()->get()
            ->map(fn (object $row): array => [
                'name' => $row->campaign === '' ? 'Main link' : str($row->campaign)->replace(['-', '_'], ' ')->ucfirst()->value(),
                'tag' => $row->campaign === '' ? 'ref='.$affiliate->code : 'utm_campaign='.$row->campaign,
                'clicks' => (int) $row->clicks,
                'sales' => $sales[(string) $row->campaign] ?? 0,
            ])
            ->all();
    }

    /**
     * "Ayesha Khan" becomes "A***a K." so affiliates never see who bought.
     */
    public function maskName(string $name): string
    {
        $words = array_values(array_filter(explode(' ', trim($name))));

        if ($words === []) {
            return '—';
        }

        $first = $words[0];
        $masked = mb_substr($first, 0, 1).'***'.(mb_strlen($first) > 1 ? mb_substr($first, -1) : '');

        return count($words) > 1 ? $masked.' '.mb_strtoupper(mb_substr(end($words), 0, 1)).'.' : $masked;
    }

    /**
     * @return HasMany<Order, Affiliate>
     */
    protected function confirmedInMonth(Affiliate $affiliate, string $period)
    {
        $month = CarbonImmutable::createFromFormat('!Y-m', $period);

        return $affiliate->orders()
            ->where('status', OrderStatus::Confirmed->value)
            ->whereBetween('confirmed_on', [$month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString()]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function customRange(?string $from, ?string $to, CarbonImmutable $today): array
    {
        $parse = function (?string $value) use ($today): ?CarbonImmutable {
            if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
                return null;
            }

            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, SiteTime::zone());

            return $date && $date->toDateString() === $value ? $date->min($today) : null;
        };

        $end = $parse($to) ?? $today;
        $start = $parse($from) ?? $end->subDays(19);

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        // Keep the window bounded so one request can never scan years of rows.
        return [$start->max($end->subYears(2)), $end];
    }

    protected function rangeText(CarbonImmutable $from, CarbonImmutable $to): string
    {
        return $from->year === $to->year
            ? $from->format('M j').' – '.$to->format('M j, Y')
            : $from->format('M j, Y').' – '.$to->format('M j, Y');
    }

    /**
     * @return array<string, int>
     */
    protected function emptyTotals(): array
    {
        return array_fill_keys(self::METRICS, 0);
    }
}
