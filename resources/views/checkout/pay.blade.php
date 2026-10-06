@extends('layouts.site')

@section('title', 'Payment — '.$order->number)

@php
    $bankRows = array_filter([
        'Bank' => $bank['bank_name'],
        'Account title' => $bank['account_title'],
        'Account number' => $bank['account_number'],
        'IBAN' => $bank['iban'],
    ]);
    $awaiting = $order->status === \App\Enums\OrderStatus::AwaitingPayment;
@endphp

{{-- Step 2 of checkout. Reached through a signed link and never cached. --}}
@section('content')
    <section>
        <div class="wrap page-hero page-hero--tight">
            <span class="eyebrow">Checkout · Step 2 of 2 · Order {{ $order->number }}</span>
            <h1 class="page-hero__title">Pay &amp; upload your receipt.</h1>
            <p class="page-hero__lede">Transfer the exact amount to the account below, then upload a screenshot or PDF of your receipt. Keep this page’s link — it is your order page.</p>
        </div>
    </section>

    <section>
        <div class="wrap contact-main">
            <div class="contact-form">
                @if ($awaiting)
                    <form class="form-grid" method="post" action="{{ $proofUrl }}" enctype="multipart/form-data" data-ajax-form novalidate>
                        <h2>Your transfer details</h2>
                        <label class="field" data-field="transaction_id">Transaction ID (optional)<input type="text" name="transaction_id" placeholder="e.g. FT2610039921" class="input"></label>
                        <label class="field" data-field="transferred_on">Transfer date<input type="date" name="transferred_on" value="{{ $today }}" max="{{ $today }}" class="input" required></label>
                        <label class="field field--full" data-field="receipt">Receipt (JPG, PNG, WebP or PDF, up to 5 MB)
                            <input type="file" name="receipt" accept="image/jpeg,image/png,image/webp,application/pdf" class="input input--file" required>
                        </label>
                        <p class="form-alert field--full" data-form-alert hidden></p>
                        <div class="contact-form__actions">
                            <button type="submit" class="btn btn--row btn--dark">Submit receipt<x-icon name="arrow-right" size="18" stroke="2.2" /></button>
                        </div>
                        <noscript>
                            <p class="form-alert field--full">Uploading needs JavaScript. You can also send your receipt on WhatsApp: {{ $whatsapp }} (quote order {{ $order->number }}).</p>
                        </noscript>
                    </form>
                @endif

                <div class="form-success" data-form-success @if ($awaiting) hidden @endif>
                    <div class="icon-tile"><x-icon name="check" stroke="2.2" /></div>
                    @if ($order->status === \App\Enums\OrderStatus::Confirmed)
                        <h3>Payment confirmed — welcome aboard.</h3>
                        <p>Your order {{ $order->number }} is confirmed. Message us on WhatsApp to schedule your first session.</p>
                    @elseif ($order->status === \App\Enums\OrderStatus::Rejected)
                        <h3>We couldn’t verify this payment.</h3>
                        <p>Please contact us about order {{ $order->number }} and we’ll sort it out with you.</p>
                    @else
                        <h3>Thank you — receipt received.</h3>
                        <p>We verify bank transfers manually, usually within one working day. You’ll get an email as soon as order {{ $order->number }} is confirmed.</p>
                    @endif
                    <p>WhatsApp:
                        @if ($whatsappUrl)
                            <a href="{{ $whatsappUrl }}">{{ $whatsapp }}</a>
                        @else
                            <strong>{{ $whatsapp }}</strong>
                        @endif
                    </p>
                </div>
            </div>

            <div class="contact-side">
                <div class="contact-promo">
                    <span class="eyebrow eyebrow--mint">Amount to transfer</span>
                    <h3>{{ $order->plan_name }}</h3>
                    <div class="summary-price"><span>{{ $order->formattedAmount() }}</span><span>exact amount</span></div>
                </div>
                <div class="hours">
                    <h3>Our bank account</h3>
                    @forelse ($bankRows as $label => $value)
                        <div class="hours__row"><span>{{ $label }}</span><span class="strong">{{ $value }}</span></div>
                    @empty
                        <p class="muted">Our bank details will be sent to you on WhatsApp ({{ $whatsapp }}). Please message us with your order number.</p>
                    @endforelse
                    @if ($bank['instructions'])
                        <p class="muted">{{ $bank['instructions'] }}</p>
                    @endif
                    <div class="hours__row"><span>Reference</span><span class="strong">{{ $order->number }}</span></div>
                </div>
            </div>
        </div>
    </section>
@endsection
