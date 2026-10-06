@extends('layouts.site')

@section('title', 'Services — DPT to USA')

@php
    $paySteps = [
        ['title' => 'Choose a plan & enter your details', 'text' => 'We’ll use them to set up your account and sessions.'],
        ['title' => 'Transfer the amount', 'text' => 'Send the plan price in US dollars by bank transfer to the account shown.'],
        ['title' => 'Upload your receipt', 'text' => 'We verify within [24 hours] and send your onboarding details.'],
    ];
@endphp

@section('content')
    <section>
        <div class="wrap page-hero">
            <span class="eyebrow">Services</span>
            <h1 class="page-hero__title">Choose the support that fits <span class="hl">where you are.</span></h1>
            <p class="page-hero__lede">Every plan is one-to-one with Dr. [Name]. Prices are in US dollars. Pay by bank transfer — no card needed.</p>
        </div>
    </section>

    <section>
        <div class="wrap grid-3 plans">
            @foreach ($plans as $plan)
                <div @class(['plan', 'plan--featured' => $plan->is_featured])>
                    @if ($plan->is_featured)
                        <span class="badge-top">MOST CHOSEN</span>
                    @endif
                    <span class="plan__kicker">{{ $plan['kicker'] }}</span>
                    <h3>{{ $plan['name'] }}</h3>
                    <p class="plan__text">{{ $plan['description'] }}</p>
                    <div class="plan__price"><span class="plan__amount">{{ $plan->formattedPrice() }}</span><span class="plan__term">one-time</span></div>
                    <a href="{{ route('checkout.show', $plan->slug) }}" @class(['btn', $plan->is_featured ? 'btn--accent' : 'btn--dark'])>Choose this plan</a>
                    <div class="plan__rule"></div>
                    <div class="plan__features">
                        @foreach ($plan['features'] as $feature)
                            <div class="plan__feature"><x-icon name="check" size="20" stroke="2.2" /><span>{{ $feature }}</span></div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="band--sand">
        <div class="wrap stack-section compare">
            <div class="section-head">
                <span class="eyebrow">Compare</span>
                <h2 class="h2 h2--sm">What’s included in each plan.</h2>
            </div>
            <div class="compare__table">
                <div class="compare__row compare__row--head">
                    <span></span>
                    <span class="compare__cell">Roadmap</span>
                    <span class="compare__cell compare__cell--featured">Mentorship</span>
                    <span class="compare__cell">Complete</span>
                </div>
                @foreach ($comparison as $label => $cells)
                    <div class="compare__row">
                        <span>{{ $label }}</span>
                        @foreach ($cells as $cell)
                            @if ($cell === true)
                                <span class="compare__cell--check"><x-icon name="check" size="20" stroke="2.2" /></span>
                            @elseif ($cell === false)
                                <span class="compare__cell compare__cell--none">—</span>
                            @else
                                <span class="compare__cell">{{ $cell }}</span>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
            <span class="compare__note">*Fair-use policy applies. [Edit to match final offer.]</span>
        </div>
    </section>

    <section>
        <div class="wrap pay__inner">
            <div class="section-head">
                <span class="eyebrow">Paying is simple</span>
                <h2 class="h2 h2--sm">Bank transfer in three steps.</h2>
                <p class="pay__lede">No cards or online gateways. All prices are in US dollars, and you’ll see our account details at checkout.</p>
            </div>
            <div class="pay__steps">
                @foreach ($paySteps as $step)
                    <div class="pay-step">
                        <span class="pay-step__num">{{ $loop->iteration }}</span>
                        <div class="pay-step__text">
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
