@extends('layouts.portal')

@section('title', 'Affiliate portal')

@php
    $rate = rtrim(rtrim(number_format($affiliate->commission_rate, 2), '0'), '.');
    $firstName = str($user->name)->before(' ');
@endphp

@section('content')
    <section>
        <div class="wrap portal" data-dashboard="portal" data-stats-url="{{ route('affiliate.stats') }}">
            <x-flash />

            <div class="portal__intro">
                <div class="portal__title">
                    <h1>Welcome back, {{ $firstName }}.</h1>
                </div>
                <p>You earn <strong>{{ $rate }}%</strong> on every confirmed sale that comes through your links.</p>
            </div>

            <div class="portal__top">
                <div class="link-card" data-link-builder data-base-url="{{ url('/') }}" data-code="{{ $affiliate->code }}">
                    <div class="link-card__head">
                        <h2>Your affiliate link</h2>
                        <span>Share it anywhere. Visits are credited to you for {{ $attributionDays }} days.</span>
                    </div>
                    <div class="link-builder">
                        <span class="link-builder__label">Create a custom link</span>
                        <div class="link-builder__fields">
                            <label>Page to link to
                                <select data-el="page">
                                    @foreach ($pages as $path => $name)
                                        <option value="{{ $path }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Campaign name (optional)
                                <input type="text" data-el="campaign" placeholder="e.g. npte-reel">
                            </label>
                        </div>
                    </div>
                    <div class="link-output">
                        <div class="link-output__url" data-el="link">{{ url('/') }}/?ref={{ $affiliate->code }}</div>
                        <button type="button" class="link-output__copy" data-el="copy">
                            <span class="when-idle"><x-icon name="copy" size="18" />Copy link</span>
                            <span class="when-copied"><x-icon name="check" size="18" stroke="2.6" />Copied</span>
                        </button>
                    </div>
                </div>

                <div class="earnings">
                    <h2>Earnings</h2>
                    <div class="earnings__grid">
                        <div class="earning earning--pending"><span class="earning__label">PENDING</span><span class="earning__value">{{ \App\Support\Money::usd($earnings['pending']) }}</span><span>{{ $earnings['pending_count'] }} {{ Str::plural('sale', $earnings['pending_count']) }} being verified</span></div>
                        <div class="earning earning--approved"><span class="earning__label">APPROVED</span><span class="earning__value">{{ \App\Support\Money::usd($earnings['approved']) }}</span><span>Not paid out yet</span></div>
                        <div class="earning earning--paid"><span class="earning__label">PAID OUT</span><span class="earning__value">{{ \App\Support\Money::usd($earnings['paid']) }}</span><span>Lifetime</span></div>
                    </div>
                    <div class="earnings__meta">
                        <div><span>How you get paid</span><span>Send us your monthly invoice</span></div>
                        <div><span>Paid to</span><span>{{ $affiliate->payout_details ? Str::limit(Str::before($affiliate->payout_details, "\n"), 32) : 'Not set yet' }}</span></div>
                    </div>
                    <form method="get" action="{{ route('affiliate.invoices') }}" class="invoice-picker">
                        <label>Monthly invoice
                            <input type="month" name="month" value="{{ $lastMonth }}" max="{{ $lastMonth }}" required>
                        </label>
                        <button type="submit">View invoice</button>
                    </form>
                    <a href="{{ route('affiliate.profile') }}" class="earnings__link">Update payout details</a>
                </div>
            </div>

            <x-dashboard.range-bar :today="$today" />

            <div class="kpis" data-el="kpis"></div>

            <x-dashboard.chart :metrics="['earn' => 'Earnings', 'clicks' => 'Clicks', 'sales' => 'Sales']" />

            <div class="portal__bottom">
                <div class="table table--tight table--referrals">
                    <div class="table__title"><h2>Recent referrals</h2></div>
                    <div class="table__head"><span>Date</span><span>Student</span><span>Plan</span><span>Status</span><span class="right">You earn</span></div>
                    @forelse ($referrals as $referral)
                        <div class="table__row">
                            <span class="muted">{{ $referral['date'] }}</span>
                            <span class="strong">{{ $referral['student'] }}</span>
                            <span>{{ $referral['plan'] }}</span>
                            <span class="status status--sm status--{{ $referral['tone'] }}">{{ $referral['status'] }}</span>
                            <span class="right strong">{{ $referral['earn'] }}</span>
                        </div>
                    @empty
                        <div class="table__empty">No referrals yet. Share your link to get started.</div>
                    @endforelse
                </div>
                <div class="top-links">
                    <h2>Your top links</h2>
                    <div class="top-links__head"><span>Link</span><span class="right">Clicks</span><span class="right">Sales</span></div>
                    @forelse ($topLinks as $link)
                        <div class="top-links__row">
                            <div class="top-links__name"><span>{{ $link['name'] }}</span><span>{{ $link['tag'] }}</span></div>
                            <span class="right">{{ number_format($link['clicks']) }}</span>
                            <span class="right strong">{{ $link['sales'] }}</span>
                        </div>
                    @empty
                        <div class="top-links__row"><div class="top-links__name"><span>No clicks yet</span><span>ref={{ $affiliate->code }}</span></div><span class="right">0</span><span class="right strong">0</span></div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
