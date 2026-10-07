@extends('layouts.admin')

@section('title', 'Admin | Orders')

@php
    $tabs = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'rejected' => 'Rejected', 'awaiting_payment' => 'Awaiting payment'];
    $query = array_filter(['status' => $tab, 'q' => $search, 'page' => $orders->currentPage() > 1 ? $orders->currentPage() : null]);
@endphp

@section('content')
    <main class="admin-main">
        <x-flash />

        <div class="admin-head">
            <div class="admin-head__title">
                <div><h1>Orders</h1></div>
                <span>Verify bank transfers and confirm enrolments</span>
            </div>
            <div class="admin-head__tools">
                <form method="get" action="{{ route('admin.orders') }}" class="search" role="search">
                    <x-icon name="search" size="20" />
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search by name, email or order" aria-label="Search">
                    <input type="hidden" name="status" value="{{ $tab }}">
                </form>
            </div>
        </div>

        <div class="stat-cards">
            <div class="stat-card"><span class="stat-card__label">Pending verification</span><span class="stat-card__value">{{ (int) ($counts['pending'] ?? 0) }}</span><span class="stat-card__note">{{ $oldestPending ? 'Oldest: '.\Illuminate\Support\Carbon::parse($oldestPending)->diffForHumans() : 'Nothing waiting' }}</span></div>
            <div class="stat-card"><span class="stat-card__label">Confirmed (this month)</span><span class="stat-card__value">{{ (int) $month->confirmed }}</span><span class="stat-card__note">+{{ (int) $month->this_week }} this week</span></div>
            <div class="stat-card"><span class="stat-card__label">Rejected</span><span class="stat-card__value">{{ (int) ($counts['rejected'] ?? 0) }}</span><span class="stat-card__note">All time</span></div>
            <div class="stat-card"><span class="stat-card__label">Revenue (this month)</span><span class="stat-card__value">{{ \App\Support\Money::usd((int) $month->revenue) }}</span><span class="stat-card__note">From confirmed orders</span></div>
        </div>

        <div class="orders-layout">
            <div>
                <div class="table table--orders">
                    <div class="tabs">
                        @foreach ($tabs as $value => $label)
                            <a href="{{ route('admin.orders', array_filter(['status' => $value, 'q' => $search])) }}" @class(['tab', 'is-active' => $tab === $value])>{{ $label }} · {{ (int) ($counts[$value] ?? 0) }}</a>
                        @endforeach
                        <a href="{{ route('admin.orders', array_filter(['status' => 'all', 'q' => $search])) }}" @class(['tab', 'is-active' => $tab === 'all'])>All</a>
                    </div>
                    <div class="table__head">
                        <span>Order</span><span>Student</span><span>Plan</span><span>Amount</span><span>Status</span><span>Date</span>
                    </div>
                    @forelse ($orders as $order)
                        <a href="{{ route('admin.orders', [...$query, 'order' => $order->number]) }}" @class(['table__row', 'is-selected' => $selected?->is($order)])>
                            <span class="strong">#{{ $order->number }}</span>
                            <span class="cell-name">{{ $order->name }}</span>
                            <span>{{ $order->plan_name }}</span>
                            <span>{{ $order->formattedAmount() }}</span>
                            <span class="status status--{{ $order->status->tone() }}">{{ $order->status->label() }}</span>
                            <span class="muted">{{ \App\Support\SiteTime::format($order->created_at) }}</span>
                        </a>
                    @empty
                        <div class="table__empty">No orders in this view.</div>
                    @endforelse
                </div>

                {{ $orders->links('partials.pagination') }}
            </div>

            @if ($selected)
                <div class="order-detail">
                    <div class="order-detail__head">
                        <h2>#{{ $selected->number }}</h2>
                        <span class="status status--{{ $selected->status->tone() }}">{{ $selected->status->label() }}</span>
                    </div>

                    @if ($selected->receipt_path)
                        <a href="{{ route('admin.orders.receipt', $selected) }}" target="_blank" rel="noopener" class="receipt @if ($receiptIsImage) receipt--image @endif">
                            @if ($receiptIsImage)
                                <img src="{{ route('admin.orders.receipt', $selected) }}" alt="Payment receipt for order {{ $selected->number }}">
                            @else
                                <x-icon name="receipt" size="30" stroke="1.6" />
                            @endif
                            <span>{{ $receiptIsImage ? 'Click to enlarge' : 'PDF receipt · click to open' }}</span>
                        </a>
                    @else
                        <div class="receipt">
                            <x-icon name="receipt" size="30" stroke="1.6" />
                            <span>No receipt uploaded yet</span>
                        </div>
                    @endif

                    <div class="order-detail__rows">
                        <div class="order-detail__row"><span>Student</span><span>{{ $selected->name }}</span></div>
                        <div class="order-detail__row"><span>Email</span><span><a href="mailto:{{ $selected->email }}">{{ $selected->email }}</a></span></div>
                        <div class="order-detail__row"><span>WhatsApp</span><span>{{ $selected->whatsapp }}</span></div>
                        <div class="order-detail__row"><span>Plan</span><span>{{ $selected->plan_name }}</span></div>
                        <div class="order-detail__row"><span>Amount due</span><span>{{ $selected->formattedAmount() }}</span></div>
                        <div class="order-detail__row"><span>Transaction ID</span><span>{{ $selected->transaction_id ?? '-' }}</span></div>
                        <div class="order-detail__row"><span>Transfer date</span><span>{{ $selected->transferred_on?->format('M j, Y') ?? '-' }}</span></div>
                        @if ($selected->affiliate)
                            <div class="order-detail__row"><span>Affiliate</span><span>{{ $selected->affiliate->user->name }} · {{ rtrim(rtrim(number_format($selected->commission_rate, 2), '0'), '.') }}%</span></div>
                            <div class="order-detail__row"><span>Commission</span><span>{{ \App\Support\Money::usd($selected->status === \App\Enums\OrderStatus::Confirmed ? $selected->commission_amount : $selected->expectedCommission()) }}</span></div>
                        @endif
                        @if ($selected->lead_id)
                            <div class="order-detail__row"><span>Lead</span><span><a href="{{ route('admin.leads.show', $selected->lead_id) }}">View form answers</a></span></div>
                        @endif
                    </div>

                    @if ($selected->status->isDecided())
                        @if ($selected->admin_note)
                            <div class="note-field">Internal note<p class="note-field__text">{{ $selected->admin_note }}</p></div>
                        @endif
                        <span class="order-detail__hint">
                            {{ $selected->status->label() }} {{ \App\Support\SiteTime::format($selected->confirmed_at ?? $selected->rejected_at, 'M j, Y · g:i A') }}. The student has been emailed.
                        </span>
                    @else
                        <form method="post" class="order-detail__form">
                            @csrf
                            <label class="note-field">Internal note<textarea name="admin_note" placeholder="e.g. amount matches statement">{{ old('admin_note') }}</textarea></label>
                            <div class="order-detail__actions">
                                <button type="submit" class="btn-reject" formaction="{{ route('admin.orders.reject', $selected) }}" data-confirm="Reject this payment and email the student? A note is required.">Reject</button>
                                <button type="submit" class="btn-confirm" formaction="{{ route('admin.orders.confirm', $selected) }}" data-confirm="Confirm this payment and email the student?">Confirm payment</button>
                            </div>
                        </form>
                        <span class="order-detail__hint">Confirming emails the student and moves them to onboarding.</span>
                    @endif
                </div>
            @endif
        </div>
    </main>
@endsection
