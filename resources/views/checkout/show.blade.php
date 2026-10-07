@extends('layouts.site')

@section('title', 'Checkout | '.$plan->name)

{{--
    Step 1 of checkout. Like the other public pages this is cached at the edge
    and identical for every visitor: no @csrf, no old(), no session.
--}}
@section('content')
    <section>
        <div class="wrap page-hero page-hero--tight">
            <span class="eyebrow">Checkout · Step 1 of 2</span>
            <h1 class="page-hero__title">Your details.</h1>
            <p class="page-hero__lede">Tell us who you are. On the next step you’ll see our bank account, transfer the amount and upload your receipt, no card needed.</p>
        </div>
    </section>

    <section>
        <div class="wrap contact-main">
            <div class="contact-form">
                @if ($plan->price === null)
                    <div class="form-success">
                        <div class="icon-tile"><x-icon name="info" /></div>
                        <h3>This plan can’t be bought online yet.</h3>
                        <p>The price is still being finalised. Message us on WhatsApp ({{ $whatsapp }}) or use the <a href="{{ route('contact') }}">contact page</a> and we’ll set you up directly.</p>
                    </div>
                @else
                    <form class="form-grid" method="post" action="{{ route('checkout.store', $plan->slug) }}" data-ajax-form novalidate>
                        <h2>Who is this plan for?</h2>
                        <label class="field field--full" data-field="name">Full name<input type="text" name="name" placeholder="e.g. Ayesha Khan" class="input" autocomplete="name" required></label>
                        <label class="field" data-field="email">Email<input type="email" name="email" placeholder="you&#64;email.com" class="input" autocomplete="email" required></label>
                        <label class="field" data-field="whatsapp">WhatsApp number<input type="tel" name="whatsapp" placeholder="+92 3XX XXXXXXX" class="input" autocomplete="tel" required></label>
                        <div class="hp" aria-hidden="true">
                            <label>Website<input type="text" name="{{ config('site.spam.honeypot') }}" tabindex="-1" autocomplete="off"></label>
                        </div>
                        <p class="form-alert field--full" data-form-alert hidden></p>
                        <div class="contact-form__actions">
                            <button type="submit" class="btn btn--row btn--dark">Continue to payment<x-icon name="arrow-right" size="18" stroke="2.2" /></button>
                        </div>
                        <noscript>
                            <p class="form-alert field--full">Checkout needs JavaScript. You can also order on WhatsApp: {{ $whatsapp }}.</p>
                        </noscript>
                    </form>
                @endif
            </div>

            <div class="contact-side">
                <div class="contact-promo">
                    <span class="eyebrow eyebrow--mint">Order summary</span>
                    <h3>{{ $plan->name }}</h3>
                    <p>{{ $plan->description }}</p>
                    <div class="summary-price"><span>{{ $plan->formattedPrice() }}</span><span>one-time</span></div>
                </div>
                <div class="hours">
                    <h3>What’s included</h3>
                    @foreach ($plan->features as $feature)
                        <div class="plan__feature"><x-icon name="check" size="20" stroke="2.2" /><span>{{ $feature }}</span></div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
