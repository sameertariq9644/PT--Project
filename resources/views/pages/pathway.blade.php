@extends('layouts.site')

@section('title', 'The Pathway — DPT to USA')

@section('content')
    <section>
        <div class="wrap page-hero">
            <span class="eyebrow">The pathway</span>
            <h1 class="page-hero__title">Six steps from Pakistan to a <span class="hl">US licence.</span></h1>
            <p class="page-hero__lede">Here is the whole route in plain language — what each step is, what you’ll need, and where graduates most often lose time.</p>
            <div class="step-rail">
                @foreach ($steps as $step)
                    <div class="step-rail__item">
                        <div class="step-rail__bar"></div>
                        <span>{{ sprintf('%02d', $loop->iteration) }} {{ $step['short'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="band--sand">
        <div class="wrap pathway-steps">
            @foreach ($steps as $step)
                <div class="pathway-step">
                    <div class="pathway-step__index">
                        <span class="pathway-step__num">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <span class="pill">{{ $step['tag'] }}</span>
                    </div>
                    <div class="pathway-step__main">
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['body'] }}</p>
                        <div class="pathway-step__time">
                            <x-icon name="clock" size="18" />Typical time: <strong>{{ $step['time'] }}</strong>
                        </div>
                    </div>
                    <div class="pathway-step__side">
                        <div class="needs">
                            <span class="needs__label">What you’ll need</span>
                            @foreach ($step['needs'] as $need)
                                <div class="needs__item"><x-icon name="check" size="20" stroke="2.2" class="icon-teal" /><span>{{ $need }}</span></div>
                            @endforeach
                        </div>
                        <div class="stuck">
                            <x-icon name="warning" size="20" />
                            <span><strong>Where people get stuck:</strong> {{ $step['stuck'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="notice">
                <x-icon name="info" size="22" class="icon-teal" />
                <span>Requirements change and differ by state. This page is a general overview; always confirm with FSBPT, FCCPT and your state board. We are not immigration lawyers — for visa filings, you’ll work with a licensed attorney.</span>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap pathway-cta__inner">
            <div class="pathway-cta__copy">
                <span class="eyebrow">Not sure where you stand?</span>
                <h2 class="h2">Find out which step you’re on — free.</h2>
                <p>Answer a few questions and get a personal video walkthrough in your inbox.</p>
                <a href="{{ route('home') }}#eligibility" class="btn btn--row btn--accent">Check my eligibility<x-icon name="arrow-right" size="18" stroke="2.2" /></a>
            </div>
            <div class="resource-card">
                <span class="resource-card__label">Free resource</span>
                <h3>The Pathway Checklist (PDF)</h3>
                <p>Every document and deadline on one printable page.</p>
                <a href="{{ route('home') }}#eligibility" class="btn btn--outline-light">Get it with your eligibility check</a>
            </div>
        </div>
    </section>
@endsection
