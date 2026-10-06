@extends('layouts.admin')

@section('title', 'Admin — Settings')

@php
    // Saved value → what the form shows, honouring old input after a validation error.
    $value = fn (string $key, mixed $default = null): mixed => old($key, $values[$key] ?? $default);
@endphp

@section('content')
    <main class="admin-main">
        <x-flash />

        <div class="admin-head">
            <div class="admin-head__title">
                <div><h1>Settings</h1></div>
                <span>Who gets notified, how email is sent, and the details shown to students</span>
            </div>
        </div>

        <form method="post" action="{{ route('admin.settings.update') }}" class="settings">
            @csrf
            @method('PUT')

            <section class="panel form-panel">
                <div class="panel__title"><h2>Notifications</h2><span>Where new leads and payment receipts are announced. Separate several addresses with commas.</span></div>
                <div class="form-grid">
                    <label class="field">Lead recipient email(s)
                        <input type="text" name="notifications[lead_recipients]" value="{{ $value('notifications.lead_recipients') }}" placeholder="you@example.com" class="input">
                        <span class="field__hint">Also receives contact form messages.</span>
                    </label>
                    <label class="field">Order recipient email(s)
                        <input type="text" name="notifications[order_recipients]" value="{{ $value('notifications.order_recipients') }}" placeholder="you@example.com" class="input">
                    </label>
                </div>
            </section>

            <section class="panel form-panel">
                <div class="panel__title">
                    <h2>Outgoing email</h2>
                    <span>
                        @if ($usingEnvMail)
                            No SMTP server saved — the site is using the server default (“{{ $envMailer }}”). With “log”, emails are written to the log file instead of being delivered.
                        @else
                            Emails are sent through the SMTP server below.
                        @endif
                    </span>
                </div>
                <div class="form-grid">
                    <label class="field">From name
                        <input type="text" name="mail[from_name]" value="{{ $value('mail.from_name') }}" placeholder="{{ config('site.name') }}" class="input">
                    </label>
                    <label class="field">From address
                        <input type="email" name="mail[from_address]" value="{{ $value('mail.from_address') }}" placeholder="hello@yourdomain.com" class="input">
                        <span class="field__hint">Must be the address the SMTP account is allowed to send as (for Gmail: that same Gmail address).</span>
                    </label>
                    <label class="field">SMTP host
                        <input type="text" name="mail[host]" value="{{ $value('mail.host') }}" placeholder="smtp.gmail.com" class="input">
                    </label>
                    <label class="field">Port
                        <input type="number" name="mail[port]" value="{{ $value('mail.port') }}" placeholder="587" class="input">
                    </label>
                    <label class="field">Encryption
                        <select name="mail[encryption]" class="input">
                            <option value="">Automatic</option>
                            <option value="tls" @selected($value('mail.encryption') === 'tls')>TLS / STARTTLS (port 587)</option>
                            <option value="ssl" @selected($value('mail.encryption') === 'ssl')>SSL (port 465)</option>
                        </select>
                    </label>
                    <label class="field">Username
                        <input type="text" name="mail[username]" value="{{ $value('mail.username') }}" class="input" autocomplete="off">
                    </label>
                    <label class="field field--full">Password
                        <input type="password" name="mail[password]" value="" placeholder="{{ $hasMailPassword ? 'Saved — leave blank to keep it' : 'SMTP password or Gmail App Password' }}" class="input" autocomplete="new-password">
                        <span class="field__hint">Stored encrypted and never shown again. Leave blank to keep the saved password.</span>
                    </label>
                </div>
            </section>

            <section class="panel form-panel">
                <div class="panel__title"><h2>Payment</h2><span>The bank account students see at checkout</span></div>
                <div class="form-grid">
                    <label class="field">Bank name
                        <input type="text" name="payment[bank_name]" value="{{ $value('payment.bank_name') }}" class="input">
                    </label>
                    <label class="field">Account title
                        <input type="text" name="payment[account_title]" value="{{ $value('payment.account_title') }}" class="input">
                    </label>
                    <label class="field">Account number
                        <input type="text" name="payment[account_number]" value="{{ $value('payment.account_number') }}" class="input">
                    </label>
                    <label class="field">IBAN
                        <input type="text" name="payment[iban]" value="{{ $value('payment.iban') }}" class="input">
                    </label>
                    <label class="field field--full">Payment instructions (optional)
                        <textarea name="payment[instructions]" class="input input--short">{{ $value('payment.instructions') }}</textarea>
                    </label>
                </div>
            </section>

            <section class="panel form-panel">
                <div class="panel__title"><h2>Contact</h2><span>Shown in the footer, on the contact page and in emails</span></div>
                <div class="form-grid">
                    <label class="field">WhatsApp number
                        <input type="text" name="contact[whatsapp]" value="{{ $value('contact.whatsapp') }}" placeholder="+92 300 1234567" class="input">
                    </label>
                    <label class="field">Public email
                        <input type="email" name="contact[email]" value="{{ $value('contact.email') }}" placeholder="hello@yourdomain.com" class="input">
                    </label>
                </div>
            </section>

            <section class="panel form-panel">
                <div class="panel__title"><h2>Content</h2><span>YouTube or Vimeo links</span></div>
                <div class="form-grid">
                    <label class="field">Intro video URL (home page)
                        <input type="url" name="content[intro_video_url]" value="{{ $value('content.intro_video_url') }}" placeholder="https://www.youtube.com/watch?v=…" class="input">
                    </label>
                    <label class="field">Lead video URL (used in the “Lead video” email)
                        <input type="url" name="content[lead_video_url]" value="{{ $value('content.lead_video_url') }}" placeholder="https://…" class="input">
                    </label>
                </div>
            </section>

            <section class="panel form-panel">
                <div class="panel__title"><h2>Affiliates</h2><span>Defaults for new affiliates and how long a referral click counts</span></div>
                <div class="form-grid">
                    <label class="field">Default commission rate (%)
                        <input type="number" name="affiliates[default_commission_rate]" value="{{ $value('affiliates.default_commission_rate', config('site.settings.affiliates.default_commission_rate')) }}" min="0" max="100" step="0.01" class="input" required>
                    </label>
                    <label class="field">Attribution window (days)
                        <input type="number" name="affiliates[attribution_days]" value="{{ $value('affiliates.attribution_days', config('site.settings.affiliates.attribution_days')) }}" min="1" max="365" step="1" class="input" required>
                    </label>
                </div>
            </section>

            <div class="form-panel__actions">
                <button type="submit" class="tool-btn tool-btn--dark">Save settings</button>
            </div>
        </form>

        <form method="post" action="{{ route('admin.settings.test-email') }}" class="panel form-panel">
            @csrf
            <div class="panel__title"><h2>Send test email</h2><span>Uses the saved settings, so save first. Sent immediately; any SMTP error is shown here.</span></div>
            <div class="inline-form">
                <input type="email" name="test_recipient" value="{{ old('test_recipient', auth()->user()->email) }}" class="input" aria-label="Send test email to" required>
                <button type="submit" class="tool-btn">Send test email</button>
            </div>
        </form>
    </main>
@endsection
