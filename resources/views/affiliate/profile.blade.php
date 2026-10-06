@extends('layouts.portal')

@section('title', 'Your details — Affiliate portal')

@section('content')
    <section>
        <div class="wrap portal portal--narrow">
            <x-flash />

            <div class="portal__intro">
                <div class="portal__title"><h1>Your details</h1></div>
                <p>Tell us where to send your commission, and keep your password up to date.</p>
            </div>

            <form method="post" action="{{ route('affiliate.profile.update') }}" class="panel form-panel">
                @csrf
                @method('PUT')

                <div class="panel__title"><h2>Payout details</h2><span>Bank name, account title and account number or IBAN. This appears on your invoices.</span></div>
                <label class="field">Where should we pay you?
                    <textarea name="payout_details" class="input input--short" placeholder="e.g. Meezan Bank&#10;Account title: Sana Ahmed&#10;IBAN: PK00 MEZN 0000 0000 0000 0000">{{ old('payout_details', $affiliate->payout_details) }}</textarea>
                </label>

                <div class="panel__title"><h2>Change password</h2><span>Leave these empty to keep your current password.</span></div>
                <div class="form-grid">
                    <label class="field field--full">Current password
                        <input type="password" name="current_password" class="input" autocomplete="current-password">
                    </label>
                    <label class="field">New password
                        <input type="password" name="password" class="input" autocomplete="new-password">
                    </label>
                    <label class="field">Repeat new password
                        <input type="password" name="password_confirmation" class="input" autocomplete="new-password">
                    </label>
                </div>

                <div class="form-panel__actions">
                    <a href="{{ route('affiliate.portal') }}" class="tool-btn tool-btn--link">Back to portal</a>
                    <button type="submit" class="tool-btn tool-btn--dark">Save</button>
                </div>
            </form>
        </div>
    </section>
@endsection
