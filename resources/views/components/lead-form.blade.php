@props(['fields'])

{{--
    The eligibility form, generated from config/lead_form.php. It posts with
    fetch() to a stateless endpoint (see resources/js/forms.js), so there is
    no @csrf and no old() here: this markup is identical for every visitor.
--}}
<div class="eligibility__form">
    <form class="form-grid" method="post" action="{{ route('leads.store') }}" data-ajax-form novalidate>
        @foreach ($fields as $field)
            @php
                $required = in_array('required', $field['rules'], true);
            @endphp

            @if ($field['type'] === 'radio')
                <fieldset class="choice-group" data-field="{{ $field['key'] }}">
                    <legend>{{ $field['label'] }}</legend>
                    <div class="choices">
                        @foreach ($field['options'] as $value => $label)
                            <label class="choice"><input type="radio" name="{{ $field['key'] }}" value="{{ $value }}" @checked($field['default'] === $value)>{{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
            @elseif ($field['type'] === 'select')
                <label @class(['field', 'field--full' => $field['width'] === 'full']) data-field="{{ $field['key'] }}">{{ $field['label'] }}
                    <select name="{{ $field['key'] }}" class="input input--tight" @required($required)>
                        <option value="">{{ $field['placeholder'] ?? 'Select' }}</option>
                        @foreach ($field['options'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @else
                <label @class(['field', 'field--full' => $field['width'] === 'full']) data-field="{{ $field['key'] }}">{{ $field['label'] }}
                    <input type="{{ $field['type'] }}" name="{{ $field['key'] }}" placeholder="{{ $field['placeholder'] }}" class="input" @required($required)>
                </label>
            @endif
        @endforeach

        <div class="hp" aria-hidden="true">
            <label>Website<input type="text" name="{{ config('site.spam.honeypot') }}" tabindex="-1" autocomplete="off"></label>
        </div>

        <p class="form-alert field--full" data-form-alert hidden></p>

        <div class="eligibility__submit">
            <span class="eligibility__consent">By submitting, you agree to receive the walkthrough video and follow-up emails.</span>
            <button type="submit" class="btn btn--row btn--accent">Check my eligibility
                <x-icon name="arrow-right" size="18" stroke="2.2" /></button>
        </div>

        <noscript>
            <p class="form-alert field--full">This form needs JavaScript. You can also reach us by email or WhatsApp, see the <a href="{{ route('contact') }}">contact page</a>.</p>
        </noscript>
    </form>

    <div class="form-success" data-form-success hidden>
        <div class="icon-tile"><x-icon name="check" stroke="2.2" /></div>
        <h3>Thank you, we’ve got your details.</h3>
        <p>We review every submission personally. If you’re a fit, the detailed video walkthrough will arrive in your inbox soon. Check your spam folder too, just in case.</p>
    </div>
</div>
