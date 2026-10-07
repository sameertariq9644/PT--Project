@extends('layouts.site')

@section('title', 'FAQ | DPT to USA')

@section('content')
    <section>
        <div class="wrap page-hero">
            <span class="eyebrow">FAQ</span>
            <h1 class="page-hero__title">Answers to what students ask most.</h1>
            <p class="page-hero__lede">Can’t find your question? Message us on WhatsApp, we reply within a day.</p>
        </div>
    </section>

    <section>
        <div class="wrap faq-page" data-faq>
            <div class="faq-cats">
                @foreach ($categories as $category)
                    <button type="button" class="faq-cat" data-faq-cat="{{ $category }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $category }}</button>
                @endforeach
                <div class="faq-help">
                    <h3>Still have a question?</h3>
                    <p>We’re happy to help before you commit to anything.</p>
                    <a href="{{ route('contact') }}" class="btn btn--accent">Contact us</a>
                </div>
            </div>
            <div class="faq-list">
                @foreach ($faqs as $faq)
                    <x-faq-item :question="$faq['question']" :answer="$faq['answer']" :category="$faq['category']" :open="$loop->first" />
                @endforeach
            </div>
        </div>
    </section>
@endsection
