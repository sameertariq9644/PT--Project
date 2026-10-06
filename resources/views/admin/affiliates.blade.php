@extends('layouts.admin')

@section('title', 'Admin — Affiliates')

@section('content')
    <main class="admin-main admin-main--compact" data-dashboard="admin" data-stats-url="{{ route('admin.affiliates.stats') }}" data-export-url="{{ route('admin.affiliates.export') }}">
        <x-flash />

        <div class="admin-head">
            <div class="admin-head__title">
                <div><h1>Affiliates</h1></div>
                <span>Traffic and sales from affiliate links · {{ rtrim(rtrim(number_format($defaultRate, 2), '0'), '.') }}% default commission on confirmed sales</span>
            </div>
            <div class="admin-head__tools">
                <label class="tool-select">Affiliate
                    <select data-el="affiliate">
                        <option value="all">All affiliates</option>
                        @foreach ($affiliates as $affiliate)
                            <option value="{{ $affiliate->id }}">{{ $affiliate->user->name }}</option>
                        @endforeach
                    </select>
                </label>
                <a href="{{ route('admin.affiliates.create') }}" class="tool-btn tool-btn--dark tool-btn--link">+ Add affiliate</a>
            </div>
        </div>

        <div class="affiliates-top">
            <x-dashboard.range-bar :today="$today" />
            <a href="{{ route('admin.affiliates.export') }}" class="tool-btn tool-btn--sm tool-btn--link" data-el="export">Export CSV</a>
        </div>

        <div class="kpis kpis--6" data-el="kpis"></div>

        <x-dashboard.chart :metrics="['sales' => 'Sales', 'revenue' => 'Revenue', 'clicks' => 'Clicks']" />

        <div class="table table--tight table--affiliates">
            <div class="table__bar">
                <h2>Performance by affiliate</h2>
                <label class="search search--sm">
                    <x-icon name="search" size="20" />
                    <input type="search" placeholder="Search affiliates" aria-label="Search affiliates" data-el="search">
                </label>
            </div>
            <div class="table__head">
                <span>Affiliate</span><span class="right">Clicks</span><span class="right">Visitors</span><span class="right">Checkouts</span><span class="right">Sales</span><span class="right">Conv.</span><span class="right">Revenue</span><span class="right">Commission</span><span>Status</span><span></span>
            </div>
            <div data-el="rows"></div>
        </div>
    </main>
@endsection
