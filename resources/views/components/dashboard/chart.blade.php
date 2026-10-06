@props(['metrics'])

<div class="panel">
    <div class="panel__head">
        <div class="panel__title">
            <h2 data-el="chart-title"></h2>
            <span data-el="chart-sub"></span>
        </div>
        <div role="group" aria-label="Chart metric" class="segmented segmented--metric">
            @foreach ($metrics as $id => $label)
                <button type="button" data-metric="{{ $id }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
    </div>
    <div class="chart" data-el="chart">
        <div class="chart__ticks" data-el="ticks"></div>
        <div class="chart__body">
            <div class="chart__plot">
                <div class="chart__grid chart__grid--top"></div>
                <div class="chart__grid chart__grid--mid"></div>
                <div class="chart__grid chart__grid--base"></div>
                <div class="chart__bars" data-el="bars"></div>
            </div>
            <div class="chart__labels" data-el="labels"></div>
        </div>
    </div>
</div>
