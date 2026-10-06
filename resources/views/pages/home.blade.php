@extends('layouts.site')

@section('title', 'Home — DPT to USA Consultation')

@php
    $howSteps = [
        ['icon' => 'form', 'title' => 'Check eligibility', 'text' => 'Answer a few questions about your degree, graduation year and exam status. Takes 3 minutes.'],
        ['icon' => 'mail', 'title' => 'Get the full video', 'text' => 'If you qualify, we email you a detailed recorded walkthrough of the process and the services.'],
        ['icon' => 'list', 'title' => 'Choose a service', 'text' => 'Take your time. When you’re convinced, pick the plan that matches where you are.'],
        ['icon' => 'bank', 'title' => 'Pay by bank transfer', 'text' => 'Transfer to our local account and upload your receipt at checkout. No card needed.'],
        ['icon' => 'check', 'title' => 'We confirm & begin', 'text' => 'We verify your payment within [24 hours] and schedule your first session.'],
    ];
    $stories = ['Passed NPTE', 'FCCPT approved', 'Now practising in [State]'];
@endphp

@section('content')
    {{-- Hero --}}
    <section id="top" class="hero">
        <div class="wrap hero__inner">
            <div class="hero__copy">
                <div class="hero__badge">
                    <span class="hero__badge-tag">For DPT graduates in Pakistan</span>
                    <span>Guided by a US-licensed physical therapist</span>
                </div>
                <h1 class="hero__title">From a DPT in Pakistan to a <span class="hl">licensed PT</span> in the USA.</h1>
                <p class="hero__lede">I made this move myself. Now I help Pakistani DPT graduates understand every step — credentialing, the NPTE, licensure and visas — with an honest plan built around your profile.</p>
                <div class="hero__cta">
                    <a href="#eligibility" class="btn btn--row btn--accent">Check my eligibility
                        <x-icon name="arrow-right" size="18" stroke="2.2" />
                    </a>
                    <a href="#watch" class="play-link">
                        <span class="play-link__icon"><x-icon name="play" size="16" /></span>Watch the 2-minute intro</a>
                </div>
                <div class="stats">
                    <div class="stat"><span class="stat__value">[120+]</span><span class="stat__label">students guided</span></div>
                    <div class="stats__rule"></div>
                    <div class="stat"><span class="stat__value">[8 yrs]</span><span class="stat__label">practising in the US</span></div>
                    <div class="stats__rule"></div>
                    <div class="stat"><span class="stat__value">1:1</span><span class="stat__label">personal guidance</span></div>
                </div>
            </div>

            <div class="hero__visual">
                <div class="hero__portrait">
                    <svg class="hero__rings" width="100%" height="100%" viewBox="0 0 480 580" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                        <circle cx="380" cy="120" r="170" fill="none" stroke="#9FD3C7" stroke-width="1"></circle>
                        <circle cx="380" cy="120" r="230" fill="none" stroke="#9FD3C7" stroke-width="1"></circle>
                        <circle cx="380" cy="120" r="290" fill="none" stroke="#9FD3C7" stroke-width="1"></circle>
                    </svg>
                    <div class="hero__portrait-label">
                        <x-icon name="user" size="56" stroke="1.4" />
                        <span>[Professional portrait — clinic setting]</span>
                    </div>
                </div>
                <div class="journey-card">
                    <span class="journey-card__label">Your journey</span>
                    <div class="journey-card__route">
                        <span>Pakistan</span>
                        <svg width="70" height="16" viewBox="0 0 70 16" fill="none" aria-hidden="true"><path d="M2 12 C 20 -2, 50 -2, 68 12" stroke="#B8532A" stroke-width="1.6" stroke-dasharray="3 4" fill="none"></path><circle cx="68" cy="12" r="3" fill="#B8532A"></circle></svg>
                        <span>USA</span>
                    </div>
                    <div class="progress"><div class="progress__bar" style="width: 58%"></div></div>
                    <div class="journey-card__meta"><span>Step 3 of 6 · NPTE prep</span><strong>On track</strong></div>
                </div>
                <div class="cred-chip">
                    <div class="cred-chip__icon"><x-icon name="shield" size="22" /></div>
                    <div class="cred-chip__text">
                        <span class="cred-chip__title">PT, DPT · Licensed in [State]</span>
                        <span class="cred-chip__sub">Trained in Pakistan, practising in the US</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Trust strip --}}
    <section class="trust">
        <div class="wrap trust__inner">
            <span class="trust__label">Guidance covers</span>
            @foreach (['FCCPT credentialing', 'TOEFL planning', 'NPTE strategy', 'State licensure', 'VisaScreen & visas'] as $topic)
                <span class="trust__item">{{ $topic }}</span>
                @unless ($loop->last)
                    <span class="trust__dot">•</span>
                @endunless
            @endforeach
        </div>
    </section>

    {{-- Intro video --}}
    <section id="watch" class="watch">
        <div class="wrap watch__inner">
            <div class="watch__copy">
                <span class="eyebrow">Start here</span>
                <h2 class="h2">Watch this before you apply.</h2>
                <p class="watch__lede">A short, honest introduction to what the path really looks like — and how I work with students.</p>
                <div class="watch__points">
                    @foreach (['Realistic timelines and total costs, start to finish', 'The mistakes that cost graduates months (or years)', 'Who this consultation is — and isn’t — right for'] as $point)
                        <div class="watch__point">
                            <x-icon name="check" size="22" stroke="2.2" class="icon-teal" />
                            <span>{{ $point }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="video">
                <span class="video__label">[Intro video thumbnail]</span>
                <button type="button" class="video__play" aria-label="Play intro video" data-video="{{ $introVideoUrl }}">
                    <x-icon name="play" size="30" />
                </button>
                <div class="video__meta">
                    <div class="video__caption">
                        <span class="video__title">“Is the US path right for you?”</span>
                        <span class="video__by">Dr. [Name] · 2 min</span>
                    </div>
                    <span class="video__lang">English / Urdu</span>
                </div>
            </div>
        </div>
    </section>

    {{-- The pathway --}}
    <section id="pathway" class="band--sand">
        <div class="wrap stack-section pathway-teaser">
            <div class="split-head">
                <div class="pathway-teaser__head">
                    <span class="eyebrow">The pathway</span>
                    <h2 class="h2">Six steps between you and a US licence — mapped clearly.</h2>
                </div>
                <p class="pathway-teaser__intro">Most graduates don’t fail for lack of talent. They get lost in the order, the paperwork and the deadlines. This is the route, in plain language.</p>
            </div>
            <div class="grid-3">
                @foreach ($steps as $step)
                    <div @class(['step-card', 'step-card--dark' => $loop->last])>
                        <div class="step-card__top">
                            <span class="step-card__num">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span class="pill">{{ $step['tag'] }}</span>
                        </div>
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['summary'] }}</p>
                    </div>
                @endforeach
            </div>
            <p class="fineprint">Requirements change and vary by state. All guidance is checked against current FSBPT, FCCPT and state board sources.</p>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how">
        <div class="wrap stack-section how">
            <div class="section-head section-head--center">
                <span class="eyebrow">How it works</span>
                <h2 class="h2 how__title">No pressure. Full transparency. You decide when you’re ready.</h2>
            </div>
            <div class="how__steps">
                <div class="how__line"></div>
                @foreach ($howSteps as $step)
                    <div class="how-step">
                        <div @class(['how-step__icon', 'how-step__icon--accent' => $loop->last])>
                            <x-icon :name="$step['icon']" size="28" :stroke="$loop->last ? 2 : 1.8" />
                        </div>
                        <span class="how-step__num">STEP {{ $loop->iteration }}</span>
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Who it's for --}}
    <section class="fit">
        <div class="wrap fit__inner">
            <div class="fit__grid">
                <div class="fit-card">
                    <h3>This is for you if…</h3>
                    <div class="fit-card__list">
                        @foreach (['You hold (or are finishing) a DPT from an HEC-recognised university', 'You’re serious about a US career and can commit 12–24 months', 'You want a clear plan instead of scattered advice from groups and forums'] as $item)
                            <div class="fit-card__item"><x-icon name="check" size="22" stroke="2.2" class="icon-teal" /><span>{{ $item }}</span></div>
                        @endforeach
                    </div>
                </div>
                <div class="fit-card fit-card--no">
                    <h3>It’s not for you if…</h3>
                    <div class="fit-card__list">
                        @foreach (['You’re looking for a shortcut or a guaranteed visa — no honest adviser can promise one', 'You want someone else to do the studying and exams for you', 'You aren’t yet sure the US is where you want to build your life'] as $item)
                            <div class="fit-card__item"><x-icon name="x" size="22" stroke="2.2" class="icon-rust" /><span>{{ $item }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Services --}}
    <section id="services" class="band--dark">
        <div class="wrap stack-section plans-teaser">
            <div class="split-head">
                <div class="plans-teaser__head">
                    <span class="eyebrow eyebrow--accent">Services</span>
                    <h2 class="h2">Support for exactly where you are.</h2>
                </div>
                <a href="{{ route('services') }}" class="text-link">Compare all services
                    <x-icon name="arrow-right" size="16" stroke="2.2" /></a>
            </div>
            <div class="grid-3 plans-teaser__grid">
                @foreach ($plans as $plan)
                    <div @class(['teaser-plan', 'teaser-plan--featured' => $plan->is_featured])>
                        @if ($plan->is_featured)
                            <span class="badge-top">MOST CHOSEN</span>
                        @endif
                        <span class="teaser-plan__kicker">{{ $plan['kicker'] }}</span>
                        <h3>{{ $plan['name'] }}</h3>
                        <p class="teaser-plan__text">{{ $plan['teaser'] }}</p>
                        <div class="teaser-plan__features">
                            @foreach ($plan['highlights'] as $highlight)
                                <span>✓ {{ $highlight }}</span>
                            @endforeach
                        </div>
                        <div class="teaser-plan__price"><span>{{ $plan->formattedPrice() }}</span></div>
                        <a href="{{ route('checkout.show', $plan->slug) }}" @class(['btn', $plan->is_featured ? 'btn--dark' : 'btn--outline-light'])>View details</a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- About --}}
    <section id="about" class="about-teaser">
        <div class="wrap about-teaser__inner">
            <div class="about-teaser__photo">
                <div class="about-teaser__photo-label">
                    <x-icon name="image" size="48" stroke="1.4" />
                    <span>[Candid photo — at work in the US]</span>
                </div>
                <div class="about-teaser__tag">
                    <span class="about-teaser__tag-title">[University], Pakistan</span>
                    <span class="about-teaser__tag-sub">DPT, class of [Year]</span>
                </div>
            </div>
            <div class="about-teaser__copy">
                <span class="eyebrow">About Dr. [Name]</span>
                <h2 class="h2">I stood exactly where you’re standing.</h2>
                <p>[Short personal story: graduating in Pakistan, the confusion of figuring out the US process alone, the setbacks, and passing the NPTE.]</p>
                <p>[Today: practising as a licensed physical therapist in [State], and why he started guiding Pakistani graduates.]</p>
                <div class="fact-row">
                    <div class="fact"><span class="fact__label">Degree</span><span class="fact__value">DPT, [University]</span></div>
                    <div class="fact"><span class="fact__label">Licensed in</span><span class="fact__value">[State(s)]</span></div>
                    <div class="fact"><span class="fact__label">Specialty</span><span class="fact__value">[e.g. Orthopaedics]</span></div>
                </div>
                <a href="{{ route('about') }}" class="text-link">Read my full story
                    <x-icon name="arrow-right" size="16" stroke="2.2" /></a>
            </div>
        </div>
    </section>

    {{-- Stories --}}
    <section class="band--sand">
        <div class="wrap stack-section stories">
            <div class="section-head section-head--narrow">
                <span class="eyebrow">Student stories</span>
                <h2 class="h2">From confused to cleared.</h2>
            </div>
            <div class="grid-3">
                @foreach ($stories as $outcome)
                    <div class="story">
                        <span class="pill">{{ $outcome }}</span>
                        <p class="story__quote">“[Real student quote about the experience — added once permission is given.]”</p>
                        <div class="story__by">
                            <div class="story__avatar"></div>
                            <div class="story__who">
                                <span class="story__name">[Student name]</span>
                                <span class="story__meta">DPT [University], [Year]</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Eligibility form --}}
    <section id="eligibility" class="eligibility">
        <div class="wrap eligibility__inner">
            <div class="eligibility__card">
                <div class="eligibility__aside">
                    <span class="eyebrow eyebrow--accent">Free eligibility check</span>
                    <h2>See if the US path is right for you.</h2>
                    <p>Tell us a little about yourself. If you’re a fit, you’ll receive a detailed video walkthrough in your inbox — free, with no obligation.</p>
                    <div class="eligibility__notes">
                        <div class="eligibility__note"><x-icon name="clock" size="18" class="icon-mint" />Takes about 3 minutes</div>
                        <div class="eligibility__note"><x-icon name="lock" size="18" class="icon-mint" />Your details stay private</div>
                        <div class="eligibility__note"><x-icon name="mail" size="18" class="icon-mint" />Video arrives by email</div>
                    </div>
                </div>
                <x-lead-form :fields="$leadFields" />
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="faq-teaser">
        <div class="wrap faq-teaser__inner">
            <div class="section-head">
                <span class="eyebrow">FAQ</span>
                <h2 class="h2">Questions students ask first.</h2>
                <p class="faq-teaser__lede">Still unsure? Message us on <a href="{{ route('contact') }}">WhatsApp</a> — we reply within a day.</p>
            </div>
            <div class="faq-list faq-list--home" data-faq>
                @foreach ($faqs as $faq)
                    <x-faq-item :question="$faq['question']" :answer="$faq['answer']" :open="$loop->first" />
                @endforeach
            </div>
        </div>
    </section>
@endsection
