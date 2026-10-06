<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_logs_in_with_their_email_and_lands_on_leads(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'owner@example.com']);

        $this->post('/login', ['login' => 'Owner@Example.com', 'password' => 'password'])
            ->assertRedirect(route('admin.leads'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_an_affiliate_logs_in_with_their_username_and_lands_on_the_portal(): void
    {
        $affiliate = $this->affiliate();
        $affiliate->user->update(['username' => 'sana']);

        $this->post('/login', ['login' => 'sana', 'password' => 'password'])
            ->assertRedirect(route('affiliate.portal'));

        $this->assertAuthenticatedAs($affiliate->user);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        User::factory()->admin()->create(['email' => 'owner@example.com']);

        $this->from('/login')
            ->post('/login', ['login' => 'owner@example.com', 'password' => 'not-the-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_an_inactive_user_cannot_log_in(): void
    {
        User::factory()->admin()->inactive()->create(['email' => 'owner@example.com']);

        $this->post('/login', ['login' => 'owner@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        User::factory()->admin()->create(['email' => 'owner@example.com']);

        $this->freezeSecond();

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['login' => 'owner@example.com', 'password' => 'wrong']);
        }

        $this->post('/login', ['login' => 'owner@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['login' => 'Too many login attempts. Please try again in 60 seconds.']);

        $this->assertGuest();
    }

    public function test_logging_out_ends_the_session(): void
    {
        $this->actingAs($this->admin())->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_logged_in_user_is_sent_from_the_login_page_to_their_own_area(): void
    {
        $this->actingAs($this->affiliate()->user)->get('/login')->assertRedirect(route('affiliate.portal'));
    }

    public function test_a_user_deactivated_while_logged_in_is_signed_out(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $admin->forceFill(['is_active' => false])->save();

        $this->get('/admin/leads')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    #[DataProvider('adminRoutes')]
    public function test_admin_routes_reject_guests_and_affiliates(string $method, string $uri): void
    {
        $uri = $this->resolveUri($uri);

        $this->call($method, $uri)->assertRedirect(route('login'));

        $this->actingAs($this->affiliate()->user)->call($method, $uri)->assertForbidden();
    }

    #[DataProvider('affiliateRoutes')]
    public function test_affiliate_routes_reject_guests_and_admins(string $method, string $uri): void
    {
        $this->call($method, $uri)->assertRedirect(route('login'));

        $this->actingAs($this->admin())->call($method, $uri)->assertForbidden();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function adminRoutes(): array
    {
        return [
            'leads' => ['GET', '/admin/leads'],
            'leads export' => ['GET', '/admin/leads/export'],
            'lead detail' => ['GET', '/admin/leads/{lead}'],
            'lead status' => ['PATCH', '/admin/leads/{lead}/status'],
            'lead video' => ['POST', '/admin/leads/{lead}/video'],
            'lead email' => ['POST', '/admin/leads/{lead}/email'],
            'orders' => ['GET', '/admin/orders'],
            'order receipt' => ['GET', '/admin/orders/{order}/receipt'],
            'order confirm' => ['POST', '/admin/orders/{order}/confirm'],
            'order reject' => ['POST', '/admin/orders/{order}/reject'],
            'affiliates' => ['GET', '/admin/affiliates'],
            'affiliate stats' => ['GET', '/admin/affiliates/stats'],
            'affiliate export' => ['GET', '/admin/affiliates/export'],
            'affiliate create' => ['GET', '/admin/affiliates/create'],
            'affiliate store' => ['POST', '/admin/affiliates'],
            'affiliate detail' => ['GET', '/admin/affiliates/{affiliate}'],
            'affiliate edit' => ['GET', '/admin/affiliates/{affiliate}/edit'],
            'affiliate update' => ['PUT', '/admin/affiliates/{affiliate}'],
            'affiliate payout' => ['POST', '/admin/affiliates/{affiliate}/payouts'],
            'affiliate invoice' => ['GET', '/admin/affiliates/{affiliate}/invoices/2026-01'],
            'services' => ['GET', '/admin/services'],
            'service edit' => ['GET', '/admin/services/{plan}/edit'],
            'service update' => ['PUT', '/admin/services/{plan}'],
            'email templates' => ['GET', '/admin/email-templates'],
            'email template preview' => ['POST', '/admin/email-templates/preview'],
            'email template create' => ['GET', '/admin/email-templates/create'],
            'email template store' => ['POST', '/admin/email-templates'],
            'email template edit' => ['GET', '/admin/email-templates/{template}/edit'],
            'email template update' => ['PUT', '/admin/email-templates/{template}'],
            'email template delete' => ['DELETE', '/admin/email-templates/{template}'],
            'settings' => ['GET', '/admin/settings'],
            'settings update' => ['PUT', '/admin/settings'],
            'settings test email' => ['POST', '/admin/settings/test-email'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function affiliateRoutes(): array
    {
        return [
            'portal' => ['GET', '/affiliate'],
            'stats' => ['GET', '/affiliate/stats'],
            'invoice picker' => ['GET', '/affiliate/invoices'],
            'invoice' => ['GET', '/affiliate/invoices/2026-01'],
            'profile' => ['GET', '/affiliate/profile'],
            'profile update' => ['PUT', '/affiliate/profile'],
        ];
    }

    /**
     * Swap the {placeholders} in a data-provider URI for real records.
     */
    private function resolveUri(string $uri): string
    {
        return strtr($uri, array_filter([
            '{lead}' => str_contains($uri, '{lead}') ? Lead::factory()->create()->id : null,
            '{order}' => str_contains($uri, '{order}') ? Order::factory()->pending()->create()->number : null,
            '{affiliate}' => str_contains($uri, '{affiliate}') ? Affiliate::factory()->create()->id : null,
            '{plan}' => str_contains($uri, '{plan}') ? Plan::factory()->create()->id : null,
            '{template}' => str_contains($uri, '{template}') ? EmailTemplate::factory()->create()->id : null,
        ]));
    }
}
