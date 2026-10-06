@props(['question', 'answer', 'category' => null, 'open' => false])

<div class="faq-item" data-faq-item @if ($category) data-cat="{{ $category }}" @endif>
    <button type="button" class="faq-item__q" aria-expanded="{{ $open ? 'true' : 'false' }}">
        <span class="faq-item__q-text">{{ $question }}</span>
        <span class="faq-item__icon">
            <x-icon name="minus" size="16" stroke="2.4" class="faq-item__minus" />
            <x-icon name="plus" size="16" stroke="2.4" class="faq-item__plus" />
        </span>
    </button>
    <p class="faq-item__a">{{ $answer }}</p>
</div>
