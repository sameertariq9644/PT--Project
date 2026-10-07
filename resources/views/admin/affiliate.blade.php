@extends('layouts.admin')

@section('title', 'Admin | '.$affiliate->user->name)

@section('content')
    <main class="admin-main">
        <x-flash />

        <div class="admin-head">
            <div class="admin-head__title">
                <div>
                    <h1>{{ $affiliate->user->name }}</h1>
                    <span class="status status--{{ $affiliate->is_active ? 'green' : 'grey' }}">{{ $affiliate->is_active ? 'Active' : 'Paused' }}</span>
                </div>
                <span>ref={{ $affiliate->code }}{{ $affiliate->channel ? ' · '.$affiliate->channel : '' }} · {{ rtrim(rtrim(number_format($affiliate->commission_rate, 2), '0'), '.') }}% commission</span>
            </div>
            <div class="admin-head__tools">
                <a href="{{ route('admin.affiliates') }}" class="tool-btn tool-btn--link">Back to affiliates</a>
                <a href="{{ route('admin.affiliates.edit', $affiliate) }}" class="tool-btn tool-btn--dark tool-btn--link">Edit</a>
            </div>
        </div>

        <div class="stat-cards">
            <div class="stat-card"><span class="stat-card__label">Pending</span><span class="stat-card__value">{{ \App\Support\Money::usd($earnings['pending']) }}</span><span class="stat-card__note">{{ $earnings['pending_count'] }} {{ Str::plural('sale', $earnings['pending_count']) }} being verified</span></div>
            <div class="stat-card"><span class="stat-card__label">Approved, not paid</span><span class="stat-card__value">{{ \App\Support\Money::usd($earnings['approved']) }}</span><span class="stat-card__note">Owed to the affiliate</span></div>
            <div class="stat-card"><span class="stat-card__label">Paid out</span><span class="stat-card__value">{{ \App\Support\Money::usd($earnings['paid']) }}</span><span class="stat-card__note">Lifetime</span></div>
            <div class="stat-card"><span class="stat-card__label">Login</span><span class="stat-card__value stat-card__value--sm">{{ $affiliate->user->username }}</span><span class="stat-card__note">{{ $affiliate->user->email }}</span></div>
        </div>

        <div class="panel">
            <div class="panel__title"><h2>Referral link</h2><span>Any page works, add <code>?ref={{ $affiliate->code }}</code> to its address.</span></div>
            <div class="link-output"><div class="link-output__url link-output__url--bordered">{{ $link }}</div></div>
            @if ($affiliate->payout_details)
                <div class="panel__title"><h2>Payout details</h2></div>
                <p class="pre-line">{{ $affiliate->payout_details }}</p>
            @endif
        </div>

        <div class="table table--tight" style="--cols: 1.2fr 0.6fr 1fr 1fr 1.2fr 1.6fr">
            <div class="table__bar"><h2>Monthly commission</h2></div>
            <div class="table__head">
                <span>Month</span><span class="right">Sales</span><span class="right">Revenue</span><span class="right">Commission</span><span>Status</span><span></span>
            </div>
            @forelse ($months as $month)
                <div class="table__row">
                    <span class="strong">{{ $month['label'] }}</span>
                    <span class="right">{{ $month['sales'] }}</span>
                    <span class="right">{{ \App\Support\Money::usd($month['revenue']) }}</span>
                    <span class="aff-commission">{{ \App\Support\Money::usd($month['commission']) }}</span>
                    @if ($month['payout'])
                        <span class="status status--sm status--dark">Paid {{ \App\Support\SiteTime::format($month['payout']->paid_at, 'M j, Y') }}</span>
                    @elseif ($month['complete'])
                        <span class="status status--sm status--amber">Unpaid</span>
                    @else
                        <span class="status status--sm status--grey">Month in progress</span>
                    @endif
                    <div class="row-actions">
                        @if ($month['complete'])
                            <a href="{{ route('admin.affiliates.invoice', [$affiliate, $month['period']]) }}" class="aff-view">Invoice</a>
                            @unless ($month['payout'])
                                <form method="post" action="{{ route('admin.affiliates.payouts', $affiliate) }}">
                                    @csrf
                                    <input type="hidden" name="period" value="{{ $month['period'] }}">
                                    <button type="submit" class="send-btn" data-confirm="Mark {{ $month['label'] }} ({{ \App\Support\Money::usd($month['commission']) }}) as paid?">Mark as paid</button>
                                </form>
                            @endunless
                        @endif
                    </div>
                </div>
            @empty
                <div class="table__empty">No confirmed sales yet.</div>
            @endforelse
        </div>
    </main>
@endsection
