@extends('layouts.base')

@php
    $isHome = request()->routeIs('home');
    $eligibilityUrl = route('home').'#eligibility';
    $nav = [
        'pathway' => 'The Pathway',
        'how' => 'How It Works',
        'services' => 'Services',
        'about' => 'About',
        'faq' => 'FAQ',
        'contact' => 'Contact',
    ];
@endphp

@section('body')
    <div class="page" data-track data-attribution-days="{{ $attributionDays }}" data-track-url="{{ route('tracking.visit') }}">
        <header class="site-header">
            <div class="wrap site-header__inner">
                <a href="{{ $isHome ? '#top' : route('home') }}" class="brand">
                    <div class="brand__mark">{{ config('site.initials') }}</div>
                    <div class="brand__text">
                        <span class="brand__name">{{ config('site.name') }}</span>
                        <span class="brand__tag">{{ config('site.tagline') }}</span>
                    </div>
                </a>
                <nav class="site-nav">
                    @foreach ($nav as $name => $label)
                        @if ($name === 'how')
                            <a href="{{ $isHome ? '#how' : route('home').'#how' }}" class="site-nav__link">{{ $label }}</a>
                        @else
                            <a href="{{ route($name) }}" @class(['site-nav__link', 'is-active' => request()->routeIs($name)])>{{ $label }}</a>
                        @endif
                    @endforeach
                </nav>
                <div class="site-header__actions">
                    <a href="{{ $isHome ? '#watch' : route('home').'#watch' }}" class="btn btn--outline">Watch intro</a>
                    <a href="{{ $eligibilityUrl }}" class="btn btn--dark">Check eligibility</a>
                </div>
            </div>
        </header>

        @yield('content')

        <footer id="contact" class="site-footer">
            <div class="wrap site-footer__inner">
                <div class="footer-cta">
                    <h2 @class(['footer-cta__title', 'footer-cta__title--home' => $isHome])>Your US career starts with <span>one honest conversation.</span></h2>
                    <a href="{{ $eligibilityUrl }}" class="btn btn--row btn--accent">Check my eligibility
                        <x-icon name="arrow-right" size="18" stroke="2.2" />
                    </a>
                </div>
                <div class="footer-grid">
                    <div class="footer-col footer-col--brand">
                        <span class="footer-brand">{{ config('site.name') }}</span>
                        <p class="footer-about">{{ config('site.description') }}</p>
                    </div>
                    <div class="footer-col">
                        <span class="footer-col__title">Explore</span>
                        <a href="{{ route('pathway') }}" class="footer-col__link">The Pathway</a>
                        @if ($isHome)
                            <a href="#how" class="footer-col__link">How It Works</a>
                        @endif
                        <a href="{{ route('services') }}" class="footer-col__link">Services</a>
                        <a href="{{ route('about') }}" class="footer-col__link">About</a>
                        @unless ($isHome)
                            <a href="{{ $eligibilityUrl }}" class="footer-col__link">Eligibility check</a>
                        @endunless
                    </div>
                    <div class="footer-col">
                        <span class="footer-col__title">Help</span>
                        <a href="{{ route('faq') }}" class="footer-col__link">FAQ</a>
                        <a href="{{ route('faq') }}" class="footer-col__link">Payment &amp; refunds</a>
                        <a href="{{ route('faq') }}" class="footer-col__link">Privacy policy</a>
                        <a href="{{ route('faq') }}" class="footer-col__link">Terms</a>
                        <a href="{{ route('login') }}" class="footer-col__link">Affiliate login</a>
                    </div>
                    <div class="footer-col">
                        <span class="footer-col__title">Contact</span>
                        <a href="{{ route('contact') }}" class="footer-col__link">{{ $contactEmail }}</a>
                        <span>{{ config('site.socials') }}</span>
                    </div>
                </div>
                <div class="footer-legal">
                    <span>© {{ date('Y') }} Dr. [Full Name]. All rights reserved.</span>
                    <span>Educational guidance only, not legal or immigration advice.</span>
                </div>
            </div>
        </footer>
    </div>
@endsection
