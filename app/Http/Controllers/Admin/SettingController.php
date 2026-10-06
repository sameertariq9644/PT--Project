<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendTestEmailRequest;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Mail\TestMail;
use App\Support\MailConfigurator;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    public function edit(Settings $settings): View
    {
        // The stored SMTP password is never sent back to the browser.
        $stored = $settings->all();

        return view('admin.settings', [
            'values' => [
                'notifications.lead_recipients' => Settings::get('notifications.lead_recipients'),
                'notifications.order_recipients' => Settings::get('notifications.order_recipients'),
                ...collect($stored)->except(Settings::ENCRYPTED)->filter(fn (mixed $value): bool => filled($value))->all(),
            ],
            'hasMailPassword' => filled($stored['mail.password'] ?? null),
            'usingEnvMail' => blank($stored['mail.host'] ?? null),
            'envMailer' => config('mail.default'),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        Settings::set($request->settings());

        MailConfigurator::apply();

        return redirect()->route('admin.settings')->with('status', 'Settings saved.');
    }

    /**
     * Send a test message right now (not queued) using the saved settings,
     * and show the real SMTP error if it fails.
     */
    public function sendTestEmail(SendTestEmailRequest $request): RedirectResponse
    {
        $recipient = $request->validated('test_recipient');

        MailConfigurator::apply();

        try {
            Mail::to($recipient)->send(new TestMail);
        } catch (Throwable $exception) {
            return back()->withErrors(['test_recipient' => 'The test email could not be sent: '.$exception->getMessage()]);
        }

        return back()->with('status', 'Test email sent to '.$recipient.'. Check the inbox (and the spam folder).');
    }
}
