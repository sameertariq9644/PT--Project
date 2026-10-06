@extends('layouts.site')

@section('title', 'About — Dr. [Name]')

@php
    $timeline = [
        ['year' => '[Year]', 'title' => 'Graduated with a DPT', 'text' => '[University], Pakistan. [A line about deciding to pursue the US.]'],
        ['year' => '[Year]', 'title' => 'Credential evaluation', 'text' => '[What the evaluation process was like and what he learnt.]'],
        ['year' => '[Year]', 'title' => 'Passed the NPTE', 'text' => '[How he prepared, and what made the difference.]'],
        ['year' => '[Year]', 'title' => 'Licensed & first US job', 'text' => '[State, setting, and the move itself.]'],
        ['year' => 'Today', 'title' => 'Practising & mentoring', 'text' => '[Current role — and guiding Pakistani graduates through the same path.]'],
    ];
    $promises = [
        ['icon' => 'shield', 'title' => 'Honesty over hype', 'text' => 'If the US isn’t right for you yet, I’ll tell you — before you pay for anything.'],
        ['icon' => 'user', 'title' => 'Built around you', 'text' => 'No copy-paste plans. Your degree, your scores and your budget shape your roadmap.'],
        ['icon' => 'route', 'title' => 'Been there myself', 'text' => 'Advice from someone who actually walked the path — not from a forum or an agent.'],
    ];
    $credentials = [
        'Degree' => 'DPT, [University]',
        'US licence' => '[State(s)]',
        'Experience' => '[X] years in the US',
        'Certifications' => '[e.g. OCS, Cert. DN]',
    ];
@endphp

@section('content')
    <section class="about-hero">
        <div class="wrap about-hero__inner">
            <div class="about-hero__copy">
                <span class="eyebrow">About Dr. [Name]</span>
                <h1 class="about-hero__title">A Pakistani DPT who made it — and <span class="hl">came back</span> to guide you.</h1>
                <p class="about-hero__lede">[One-paragraph intro: who he is, where he practises today, and why he started helping graduates from home.]</p>
                <div class="about-hero__tags">
                    @foreach (['PT, DPT', 'Licensed in [State]', '[Specialty]', 'Speaks English & Urdu'] as $tag)
                        <span class="tag">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
            <div class="about-hero__photo">
                <x-icon name="user" size="56" stroke="1.4" />
                <span>[Portrait photo]</span>
            </div>
        </div>
    </section>

    <section class="band--dark">
        <div class="quote__inner">
            <x-icon name="quote" size="44" class="quote__mark" />
            <p class="quote__text">[A personal line from the doctor — e.g. what he wishes someone had told him when he graduated.]</p>
            <span class="quote__by">— Dr. [Full Name], PT, DPT</span>
        </div>
    </section>

    <section>
        <div class="wrap journey__inner">
            <div class="section-head">
                <span class="eyebrow">My journey</span>
                <h2 class="h2">The same road you’re about to walk.</h2>
                <p class="journey__lede">Every step below is one I went through myself — which is why I can tell you what really happens at each one.</p>
            </div>
            <div class="timeline">
                @foreach ($timeline as $event)
                    <div class="timeline__item">
                        <div class="timeline__dot"></div>
                        <span class="timeline__year">{{ $event['year'] }}</span>
                        <div class="timeline__body">
                            <h3>{{ $event['title'] }}</h3>
                            <p>{{ $event['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="band--sand">
        <div class="wrap stack-section promises">
            <div class="section-head section-head--narrow">
                <span class="eyebrow">How I work</span>
                <h2 class="h2">Three promises I make every student.</h2>
            </div>
            <div class="grid-3">
                @foreach ($promises as $promise)
                    <div class="promise">
                        <div class="icon-tile"><x-icon :name="$promise['icon']" /></div>
                        <h3>{{ $promise['title'] }}</h3>
                        <p>{{ $promise['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section>
        <div class="wrap stack-section credentials">
            <div class="section-head">
                <span class="eyebrow">Credentials</span>
                <h2 class="h2">Qualifications at a glance.</h2>
            </div>
            <div class="credentials__grid">
                @foreach ($credentials as $label => $value)
                    <div class="credential">
                        <span class="credential__label">{{ $label }}</span>
                        <span class="credential__value">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
