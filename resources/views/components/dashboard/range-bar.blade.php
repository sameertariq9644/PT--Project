@props(['today'])

@php
    $ranges = ['7d' => 'Last 7 days', '30d' => 'Last 30 days', 'tm' => 'This month', 'lm' => 'Last month', 'yr' => 'This year', 'custom' => 'Custom'];
@endphp

<div {{ $attributes->class('range-bar') }}>
    <div role="group" aria-label="Date range" class="segmented">
        @foreach ($ranges as $id => $label)
            <button type="button" data-range="{{ $id }}" aria-pressed="{{ $id === '30d' ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
    </div>
    <div class="range-bar__text">
        <x-icon name="calendar" size="18" />
        <span data-el="range-text"></span>
    </div>
    <div class="range-custom" data-el="custom-range" hidden>
        <label>From<input type="date" data-el="from" value="{{ $today->subDays(19)->toDateString() }}" max="{{ $today->toDateString() }}"></label>
        <label>To<input type="date" data-el="to" value="{{ $today->toDateString() }}" max="{{ $today->toDateString() }}"></label>
        <button type="button" data-el="apply">Apply</button>
    </div>
</div>
