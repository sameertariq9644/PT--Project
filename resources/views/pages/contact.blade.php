@extends('layouts.site')

@section('title', 'Contact | DPT to USA')

@php
    $channels = [
        ['icon' => 'chat', 'title' => 'WhatsApp', 'text' => $whatsapp, 'action' => 'Start a chat', 'url' => $whatsappUrl],
        ['icon' => 'mail', 'title' => 'Email', 'text' => $email, 'action' => 'Send an email', 'url' => $emailUrl],
        ['icon' => 'video', 'title' => 'Follow along', 'text' => 'Free tips on YouTube & Instagram', 'action' => config('site.social_handle'), 'url' => null],
    ];
@endphp

@section('content')
    <section>
        <div class="wrap page-hero">
            <span class="eyebrow">Contact</span>
            <h1 class="page-hero__title">Let’s talk about your next step.</h1>
            <p class="page-hero__lede">Questions about the process or a plan? Reach out, you’ll hear back within one working day.</p>
        </div>
    </section>

    <section>
        <div class="wrap grid-3 contact-cards">
            @foreach ($channels as $channel)
                <div class="contact-card">
                    <div class="icon-tile"><x-icon :name="$channel['icon']" /></div>
                    <h3>{{ $channel['title'] }}</h3>
                    <p>{{ $channel['text'] }}</p>
                    <a href="{{ $channel['url'] ?? '#message' }}" class="contact-card__link">{{ $channel['action'] }}<x-icon name="arrow-right" size="18" stroke="2.2" /></a>
                </div>
            @endforeach
        </div>
    </section>

    <section>
        <div class="wrap contact-main">
            {{-- Posts with fetch() to a stateless endpoint (resources/js/forms.js): no @csrf, no old(). --}}
            <div id="message" class="contact-form">
                <form class="form-grid" method="post" action="{{ route('contact.store') }}" data-ajax-form novalidate>
                    <h2>Send a message</h2>
                    <label class="field" data-field="name">Full name<input type="text" name="name" placeholder="Your name" class="input" required></label>
                    <label class="field" data-field="email">Email<input type="email" name="email" placeholder="you&#64;email.com" class="input" required></label>
                    <label class="field" data-field="whatsapp">WhatsApp (optional)<input type="tel" name="whatsapp" placeholder="+92 3XX XXXXXXX" class="input"></label>
                    <label class="field" data-field="topic">Topic
                        <select name="topic" class="input">
                            @foreach ($topics as $topic)
                                <option>{{ $topic }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="field field--full" data-field="message">Message<textarea name="message" placeholder="How can we help?" class="input" required></textarea></label>
                    <div class="hp" aria-hidden="true">
                        <label>Website<input type="text" name="{{ config('site.spam.honeypot') }}" tabindex="-1" autocomplete="off"></label>
                    </div>
                    <p class="form-alert field--full" data-form-alert hidden></p>
                    <div class="contact-form__actions">
                        <button type="submit" class="btn btn--row btn--dark">Send message<x-icon name="arrow-right" size="18" stroke="2.2" /></button>
                    </div>
                    <noscript>
                        <p class="form-alert field--full">This form needs JavaScript. You can also reach us on WhatsApp ({{ $whatsapp }}) or by email ({{ $email }}).</p>
                    </noscript>
                </form>
                <div class="form-success" data-form-success hidden>
                    <div class="icon-tile"><x-icon name="check" stroke="2.2" /></div>
                    <h3>Message sent.</h3>
                    <p>Thank you for reaching out, you’ll hear back from us within one working day.</p>
                </div>
            </div>
            <div class="contact-side">
                <div class="contact-promo">
                    <span class="eyebrow eyebrow--mint">Wondering if you qualify?</span>
                    <h3>Use the eligibility check instead.</h3>
                    <p>It’s faster, and you’ll get a detailed video walkthrough by email.</p>
                    <a href="{{ route('home') }}#eligibility" class="btn btn--accent">Check eligibility</a>
                </div>
                <div class="hours">
                    <h3>Response hours</h3>
                    <div class="hours__row"><span>Mon – Fri</span><span>[10am – 8pm PKT]</span></div>
                    <div class="hours__row"><span>Saturday</span><span>[12pm – 5pm PKT]</span></div>
                    <div class="hours__row"><span>Sunday</span><span>Closed</span></div>
                </div>
            </div>
        </div>
    </section>
@endsection
