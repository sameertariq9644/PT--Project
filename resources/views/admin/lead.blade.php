@extends('layouts.admin')

@section('title', 'Admin — '.$lead->name)

@section('content')
    <main class="admin-main">
        <x-flash />

        <div class="admin-head">
            <div class="admin-head__title">
                <div><h1>{{ $lead->name }}</h1><span class="status status--{{ $lead->status->tone() }}">{{ $lead->status->label() }}</span></div>
                <span>Submitted {{ \App\Support\SiteTime::format($lead->created_at, 'M j, Y · g:i A') }}</span>
            </div>
            <div class="admin-head__tools">
                <a href="{{ route('admin.leads') }}" class="tool-btn tool-btn--link">Back to leads</a>
            </div>
        </div>

        <div class="orders-layout">
            <div class="panel">
                <div class="panel__title"><h2>Form answers</h2><span>Everything this student told us</span></div>
                <div class="order-detail__rows">
                    <div class="order-detail__row"><span>Email</span><span><a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></span></div>
                    <div class="order-detail__row"><span>WhatsApp</span><span>{{ $lead->whatsapp }}</span></div>
                    @foreach ($fields as $field)
                        <div class="order-detail__row"><span>{{ $field['label'] }}</span><span>{{ $lead->answer($field['key']) ?? '—' }}</span></div>
                    @endforeach
                    <div class="order-detail__row"><span>Referred by</span><span>{{ $lead->affiliate ? $lead->affiliate->user->name.' ('.$lead->affiliate->code.')' : '—' }}</span></div>
                    <div class="order-detail__row"><span>Campaign</span><span>{{ $lead->utm_campaign ?? '—' }}</span></div>
                    <div class="order-detail__row"><span>Video email</span><span>{{ $lead->video_sent_at ? 'Sent '.\App\Support\SiteTime::format($lead->video_sent_at, 'M j, Y · g:i A') : 'Not sent' }}</span></div>
                </div>

                @if ($lead->orders->isNotEmpty())
                    <div class="panel__title"><h2>Orders</h2></div>
                    <div class="order-detail__rows">
                        @foreach ($lead->orders as $order)
                            <div class="order-detail__row">
                                <span><a href="{{ route('admin.orders', ['status' => 'all', 'order' => $order->number]) }}">{{ $order->number }}</a> · {{ $order->plan_name }}</span>
                                <span><span class="status status--sm status--{{ $order->status->tone() }}">{{ $order->status->label() }}</span></span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="order-detail">
                <div class="order-detail__head"><h2>Actions</h2></div>

                <form method="post" action="{{ route('admin.leads.status', $lead) }}" class="stack-form">
                    @csrf
                    @method('PATCH')
                    <label class="note-field">Status
                        <select name="status" class="input input--sm">
                            @foreach ($statuses as $option)
                                <option value="{{ $option->value }}" @selected($lead->status === $option)>{{ $option->label() }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="tool-btn">Update status</button>
                </form>

                <form method="post" action="{{ route('admin.leads.video', $lead) }}" class="stack-form">
                    @csrf
                    <button type="submit" class="tool-btn tool-btn--dark">{{ $lead->video_sent_at ? 'Resend video email' : 'Send video email' }}</button>
                    <span class="order-detail__hint">Sends the “Lead video” template and marks the lead as eligible.</span>
                </form>

                <form method="post" action="{{ route('admin.leads.email', $lead) }}" class="stack-form">
                    @csrf
                    <label class="note-field">Send another email
                        <select name="template_id" class="input input--sm" required>
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="tool-btn">Send email</button>
                </form>
            </div>
        </div>
    </main>
@endsection
