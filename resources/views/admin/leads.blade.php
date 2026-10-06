@extends('layouts.admin')

@section('title', 'Admin — Leads')

@php
    // Student, the configurable answer columns, then Status, Video email and Date.
    $cols = implode(' ', ['1.7fr', ...array_column($columns, 'table_width'), '1.1fr', '1.1fr', '0.7fr']);
    $percent = fn (int $part, int $whole): string => $whole > 0 ? round($part / $whole * 100).'%' : '—';
    $eligible = (int) ($counts['eligible'] ?? 0);
    $customers = (int) ($counts['customer'] ?? 0);
@endphp

@section('content')
    <main class="admin-main">
        <x-flash />

        <div class="admin-head">
            <div class="admin-head__title">
                <div><h1>Leads</h1></div>
                <span>Everyone who completed the eligibility form</span>
            </div>
            <div class="admin-head__tools">
                <form method="get" action="{{ route('admin.leads') }}" class="search" role="search">
                    <x-icon name="search" size="20" />
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search by name or email" aria-label="Search">
                    @if ($status)
                        <input type="hidden" name="status" value="{{ $status->value }}">
                    @endif
                </form>
                <a href="{{ route('admin.leads.export', request()->only(['status', 'q'])) }}" class="tool-btn">Export CSV</a>
            </div>
        </div>

        <div class="stat-cards">
            <div class="stat-card"><span class="stat-card__label">New leads (7 days)</span><span class="stat-card__value">{{ (int) $totals->recent }}</span><span class="stat-card__note">+{{ (int) $totals->today }} today</span></div>
            <div class="stat-card"><span class="stat-card__label">Eligible</span><span class="stat-card__value">{{ $eligible }}</span><span class="stat-card__note">{{ $percent($eligible, $total) }} of all leads</span></div>
            <div class="stat-card"><span class="stat-card__label">Video email sent</span><span class="stat-card__value">{{ (int) $totals->video_sent }}</span><span class="stat-card__note">{{ (int) $totals->video_waiting }} waiting to send</span></div>
            <div class="stat-card"><span class="stat-card__label">Converted to customers</span><span class="stat-card__value">{{ $customers }}</span><span class="stat-card__note">{{ $percent($customers, $total) }} of all leads</span></div>
        </div>

        <div class="table table--leads" style="--cols: {{ $cols }}">
            <div class="tabs">
                <a href="{{ route('admin.leads', array_filter(['q' => $search])) }}" @class(['tab', 'is-active' => $status === null])>All · {{ $total }}</a>
                @foreach ([\App\Enums\LeadStatus::Eligible, \App\Enums\LeadStatus::NeedsReview, \App\Enums\LeadStatus::NotEligible, \App\Enums\LeadStatus::Customer] as $tab)
                    <a href="{{ route('admin.leads', array_filter(['status' => $tab->value, 'q' => $search])) }}" @class(['tab', 'is-active' => $status === $tab])>{{ $tab === \App\Enums\LeadStatus::Customer ? 'Customers' : $tab->label() }} · {{ (int) ($counts[$tab->value] ?? 0) }}</a>
                @endforeach
            </div>
            <div class="table__head">
                <span>Student</span>
                @foreach ($columns as $column)
                    <span>{{ $column['table_label'] }}</span>
                @endforeach
                <span>Status</span><span>Video email</span><span>Date</span>
            </div>
            @forelse ($leads as $lead)
                <div class="table__row">
                    <a href="{{ route('admin.leads.show', $lead) }}" class="person">
                        <span class="person__name">{{ $lead->name }}</span>
                        <span class="person__sub">{{ $lead->email }}</span>
                    </a>
                    @foreach ($columns as $column)
                        <span>{{ $lead->answer($column['key']) ?? '—' }}</span>
                    @endforeach
                    <form method="post" action="{{ route('admin.leads.status', $lead) }}">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="status status--{{ $lead->status->tone() }} status-select" aria-label="Status of {{ $lead->name }}" data-autosubmit>
                            @foreach ($statuses as $option)
                                <option value="{{ $option->value }}" @selected($lead->status === $option)>{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if ($lead->video_sent_at)
                        <span class="sent"><x-icon name="check" size="16" stroke="2.6" />Sent</span>
                    @elseif ($lead->status === \App\Enums\LeadStatus::NotEligible)
                        <span class="dash">—</span>
                    @else
                        <form method="post" action="{{ route('admin.leads.video', $lead) }}">
                            @csrf
                            <button type="submit" class="send-btn">Send video</button>
                        </form>
                    @endif
                    <span class="muted">{{ \App\Support\SiteTime::format($lead->created_at) }}</span>
                </div>
            @empty
                <div class="table__empty">No leads here yet. They appear as soon as someone submits the eligibility form.</div>
            @endforelse
        </div>

        {{ $leads->links('partials.pagination') }}
    </main>
@endsection
