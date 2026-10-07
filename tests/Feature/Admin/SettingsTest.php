<?php

namespace Tests\Feature\Admin;

use App\Mail\TestMail;
use App\Models\Setting;
use App\Support\MailConfigurator;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    /**
     * @param  array<string, array<string, mixed>>  $overrides
     * @return array<string, array<string, mixed>>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'notifications' => ['lead_recipients' => 'owner@example.com', 'order_recipients' => 'owner@example.com'],
            'mail' => ['from_name' => 'Dr. Khan', 'from_address' => 'hello@dpt.example', 'host' => 'smtp.dpt.example', 'port' => '587', 'encryption' => 'tls', 'username' => 'hello@dpt.example', 'password' => 'app-password-123'],
            'payment' => ['bank_name' => 'Meezan Bank', 'account_title' => 'DPT to USA', 'account_number' => '0123456789', 'iban' => 'PK00MEZN0000000123456789', 'instructions' => 'Use your order number as the reference.'],
            'contact' => ['whatsapp' => '+92 300 1112223', 'email' => 'hello@dpt.example'],
            'content' => ['intro_video_url' => 'https://youtu.be/intro12345', 'lead_video_url' => 'https://youtu.be/detailed12'],
            'affiliates' => ['default_commission_rate' => '25', 'attribution_days' => '45'],
        ], $overrides);
    }

    public function test_saving_settings_persists_them_and_refreshes_the_cache(): void
    {
        $this->assertSame(30, Settings::get('affiliates.attribution_days'));

        $this->put('/admin/settings', $this->payload())->assertRedirect(route('admin.settings'))->assertSessionHasNoErrors();

        $this->assertSame('Meezan Bank', Settings::get('payment.bank_name'));
        $this->assertSame('45', Settings::get('affiliates.attribution_days'));

        $this->get('/contact')->assertSee('+92 300 1112223');
    }

    public function test_the_smtp_password_is_encrypted_at_rest_and_never_rendered(): void
    {
        $this->put('/admin/settings', $this->payload());

        $stored = Setting::query()->findOrFail('mail.password')->value;

        $this->assertNotSame('app-password-123', $stored);
        $this->assertSame('app-password-123', Settings::secret('mail.password'));

        $this->get('/admin/settings')->assertOk()->assertDontSee('app-password-123')->assertDontSee($stored);
    }

    public function test_a_blank_password_keeps_the_saved_one(): void
    {
        $this->put('/admin/settings', $this->payload());
        $this->put('/admin/settings', $this->payload(['mail' => ['password' => '', 'host' => 'smtp.other.example']]));

        $this->assertSame('smtp.other.example', Settings::get('mail.host'));
        $this->assertSame('app-password-123', Settings::secret('mail.password'));
    }

    public function test_saved_smtp_settings_override_the_mail_configuration(): void
    {
        $this->put('/admin/settings', $this->payload());

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.dpt.example', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('app-password-123', config('mail.mailers.smtp.password'));
        $this->assertSame('hello@dpt.example', config('mail.from.address'));
        $this->assertSame('Dr. Khan', config('mail.from.name'));
    }

    public function test_without_smtp_settings_the_env_mail_configuration_is_kept(): void
    {
        $default = config('mail.default');

        MailConfigurator::apply();

        $this->assertSame($default, config('mail.default'));
    }

    public function test_invalid_settings_are_rejected(): void
    {
        $this->put('/admin/settings', $this->payload([
            'notifications' => ['lead_recipients' => 'owner@example.com, not-an-email'],
            'content' => ['intro_video_url' => 'javascript:alert(1)'],
            'affiliates' => ['default_commission_rate' => '140'],
        ]))->assertSessionHasErrors(['notifications.lead_recipients', 'content.intro_video_url', 'affiliates.default_commission_rate']);

        $this->assertDatabaseCount('settings', 0);
    }

    public function test_send_test_email_sends_immediately(): void
    {
        Mail::fake();

        $this->post('/admin/settings/test-email', ['test_recipient' => 'owner@example.com'])->assertSessionHasNoErrors();

        Mail::assertSent(TestMail::class, fn (TestMail $mail): bool => $mail->hasTo('owner@example.com'));
    }

    public function test_send_test_email_reports_the_real_smtp_error(): void
    {
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('Connection could not be established with host "smtp.dpt.example:587"'));

        $this->post('/admin/settings/test-email', ['test_recipient' => 'owner@example.com'])
            ->assertSessionHasErrors(['test_recipient' => 'The test email could not be sent: Connection could not be established with host "smtp.dpt.example:587"']);
    }
}
