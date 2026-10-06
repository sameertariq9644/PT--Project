@extends('layouts.base')

@section('title', $invoice['number'])

{{-- A print-optimised page: "Print / Save as PDF" uses the browser, no PDF library. --}}
@section('body')
    <div class="page invoice-page">
        <div class="invoice-toolbar">
            <a href="{{ $backUrl }}" class="tool-btn tool-btn--link">Back</a>
            <button type="button" class="tool-btn tool-btn--dark" data-print>Print / Save as PDF</button>
        </div>

        <article class="invoice">
            <header class="invoice__head">
                <div>
                    <span class="eyebrow">Commission invoice</span>
                    <h1>{{ $invoice['number'] }}</h1>
                    <p>{{ $invoice['label'] }} · issued {{ $invoice['issued']->format('M j, Y') }}</p>
                </div>
                <span class="status status--{{ $invoice['payout'] ? 'green' : 'amber' }}">
                    {{ $invoice['payout'] ? 'Paid '.\App\Support\SiteTime::format($invoice['payout']->paid_at, 'M j, Y') : 'Unpaid' }}
                </span>
            </header>

            <div class="invoice__parties">
                <div>
                    <span class="needs__label">From</span>
                    <strong>{{ $affiliate->user->name }}</strong>
                    <span>{{ $affiliate->user->email }}</span>
                    <span>Affiliate code: {{ $affiliate->code }}</span>
                    <p class="pre-line">{{ $affiliate->payout_details ?: 'Payout details not provided yet.' }}</p>
                </div>
                <div>
                    <span class="needs__label">Bill to</span>
                    <strong>{{ config('site.name') }}</strong>
                    <span>{{ \App\Support\Site::email() }}</span>
                    <span>WhatsApp: {{ \App\Support\Site::whatsapp() }}</span>
                </div>
            </div>

            <table class="invoice__table">
                <thead>
                    <tr><th>Confirmed</th><th>Order</th><th>Plan</th><th class="right">Sale amount</th><th class="right">Rate</th><th class="right">Commission</th></tr>
                </thead>
                <tbody>
                    @forelse ($invoice['lines'] as $line)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($line->confirmed_on)->format('M j, Y') }}</td>
                            <td>{{ $line->number }}</td>
                            <td>{{ $line->plan_name }}</td>
                            <td class="right">{{ $line->formattedAmount() }}</td>
                            <td class="right">{{ rtrim(rtrim(number_format((float) $line->commission_rate, 2), '0'), '.') }}%</td>
                            <td class="right">{{ \App\Support\Money::usd($line->commission_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">No confirmed sales in {{ $invoice['label'] }}.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr><td colspan="3">Total</td><td class="right">{{ \App\Support\Money::usd($invoice['sales_total']) }}</td><td></td><td class="right">{{ \App\Support\Money::usd($invoice['total']) }}</td></tr>
                </tfoot>
            </table>

            <p class="fineprint">Commission is earned on sales confirmed during {{ $invoice['label'] }}. Send this invoice to {{ \App\Support\Site::email() }} to request payment.</p>
        </article>
    </div>
@endsection
