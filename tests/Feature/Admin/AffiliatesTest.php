<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Affiliate;
use App\Models\AffiliateDailyStat;
use App\Models\AffiliatePayout;
use App\Models\Order;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-15 07:00:00');
        $this->actingAs($this->admin());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Sana Ahmed',
            'email' => 'sana@example.com',
            'username' => 'sana',
            'password' => 'hand-over-123',
            'code' => 'sana20',
            'channel' => 'Instagram',
            'commission_rate' => '25',
            'is_active' => '1',
            ...$overrides,
        ];
    }

    public function test_the_page_renders_without_sample_data(): void
    {
        $affiliate = $this->affiliate();

        $this->get('/admin/affiliates')
            ->assertOk()
            ->assertSee('Performance by affiliate')
            ->assertSee($affiliate->user->name)
            ->assertDontSee('SAMPLE DATA')
            ->assertDontSee('Conversion funnel')
            ->assertDontSee('PAYOUTS');
    }

    public function test_the_add_form_defaults_to_the_commission_rate_from_settings(): void
    {
        Settings::set(['affiliates.default_commission_rate' => '35']);

        $this->get('/admin/affiliates/create')->assertOk()->assertSee('name="commission_rate" value="35"', false);
    }

    public function test_creating_an_affiliate_creates_their_login_too(): void
    {
        $this->post('/admin/affiliates', $this->payload())->assertSessionHasNoErrors();

        $affiliate = Affiliate::query()->sole();
        $user = $affiliate->user;

        $this->assertSame('SANA20', $affiliate->code);
        $this->assertSame(25.0, $affiliate->commission_rate);
        $this->assertSame(UserRole::Affiliate, $user->role);
        $this->assertSame('sana', $user->username);

        $this->post('/logout');
        $this->post('/login', ['login' => 'sana', 'password' => 'hand-over-123'])->assertRedirect(route('affiliate.portal'));
    }

    public function test_codes_usernames_and_emails_must_be_unique(): void
    {
        $existing = $this->affiliate(['code' => 'SANA20']);
        $existing->user->update(['username' => 'sana', 'email' => 'sana@example.com']);

        $this->post('/admin/affiliates', $this->payload())->assertSessionHasErrors(['code', 'username', 'email']);

        $this->assertSame(2, User::query()->count());
    }

    public function test_pausing_an_affiliate_stops_login_and_attribution(): void
    {
        $affiliate = $this->affiliate(['code' => 'SANA20']);
        $affiliate->user->update(['username' => 'sana']);

        // Warm the cached code lookup, then pause.
        $this->assertNotNull(Affiliate::findActiveByCode('SANA20'));

        $this->put("/admin/affiliates/{$affiliate->id}", $this->payload(['email' => $affiliate->user->email, 'password' => '', 'is_active' => '0']))
            ->assertSessionHasNoErrors();

        $this->assertFalse($affiliate->refresh()->is_active);
        $this->assertNull(Affiliate::findActiveByCode('SANA20'));

        $this->post('/logout');
        $this->post('/login', ['login' => 'sana', 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_editing_without_a_password_keeps_the_current_one(): void
    {
        $affiliate = $this->affiliate();
        $hash = $affiliate->user->password;

        $this->put("/admin/affiliates/{$affiliate->id}", $this->payload(['password' => '', 'commission_rate' => '30']));

        $this->assertSame($hash, $affiliate->user->refresh()->password);
        $this->assertSame(30.0, $affiliate->refresh()->commission_rate);
    }

    public function test_changing_the_rate_does_not_change_commission_on_existing_orders(): void
    {
        $affiliate = $this->affiliate(['commission_rate' => 20]);
        $order = Order::factory()->referredBy($affiliate)->pending()->create();

        $this->put("/admin/affiliates/{$affiliate->id}", $this->payload(['email' => $affiliate->user->email, 'password' => '', 'commission_rate' => '50']));
        $this->post("/admin/orders/{$order->number}/confirm");

        $this->assertSame(6000, $order->refresh()->commission_amount);
    }

    public function test_stats_cover_all_affiliates_or_just_the_selected_one(): void
    {
        $sana = $this->affiliate(['code' => 'SANA20', 'commission_rate' => 20]);
        $ali = $this->affiliate(['code' => 'ALI', 'commission_rate' => 10]);

        AffiliateDailyStat::factory()->for($sana)->create(['date' => '2026-10-14', 'clicks' => 40, 'unique_visitors' => 30]);
        AffiliateDailyStat::factory()->for($ali)->create(['date' => '2026-10-14', 'clicks' => 5, 'unique_visitors' => 4]);

        Order::factory()->referredBy($sana)->confirmed()->create(['created_at' => '2026-10-14 09:00:00', 'confirmed_at' => '2026-10-14 09:00:00']);
        Order::factory()->referredBy($ali)->confirmed()->create(['created_at' => '2026-10-14 09:00:00', 'confirmed_at' => '2026-10-14 09:00:00']);
        // An order nobody referred is not affiliate traffic.
        Order::factory()->confirmed()->create(['created_at' => '2026-10-14 09:00:00', 'confirmed_at' => '2026-10-14 09:00:00']);

        $this->getJson('/admin/affiliates/stats?range=30d')
            ->assertOk()
            ->assertJsonPath('cur', ['clicks' => 45, 'visitors' => 34, 'checkouts' => 2, 'sales' => 2, 'revenue' => 60000, 'commission' => 9000])
            ->assertJsonCount(2, 'rows')
            ->assertJsonPath('rows.0.code', 'SANA20')
            ->assertJsonPath('rows.0.commission', 6000)
            ->assertJsonPath('rows.1.commission', 3000);

        $this->getJson('/admin/affiliates/stats?range=30d&affiliate='.$ali->id)
            ->assertJsonPath('cur', ['clicks' => 5, 'visitors' => 4, 'checkouts' => 1, 'sales' => 1, 'revenue' => 30000, 'commission' => 3000])
            ->assertJsonCount(1, 'rows')
            ->assertJsonPath('rows.0.code', 'ALI');
    }

    public function test_the_export_downloads_the_per_affiliate_numbers(): void
    {
        $sana = $this->affiliate(['code' => 'SANA20']);
        $sana->user->update(['name' => 'Sana Ahmed']);
        AffiliateDailyStat::factory()->for($sana)->create(['date' => '2026-10-14', 'clicks' => 40, 'unique_visitors' => 30]);

        $csv = $this->get('/admin/affiliates/export?range=7d')->assertDownload('affiliates-2026-10-09-to-2026-10-15.csv')->streamedContent();

        $this->assertStringContainsString('"Sana Ahmed",SANA20,', $csv);
        $this->assertStringContainsString(',40,30,0,0,0,0,Active', $csv);
    }

    public function test_a_completed_month_can_be_marked_as_paid_once(): void
    {
        $affiliate = $this->affiliate(['commission_rate' => 20]);
        Order::factory()->referredBy($affiliate)->confirmed()->count(2)->create(['created_at' => '2026-09-10 08:00:00', 'confirmed_at' => '2026-09-10 08:00:00']);

        $this->get("/admin/affiliates/{$affiliate->id}")->assertOk()->assertSee('September 2026')->assertSee('$12,000')->assertSee('Mark as paid');

        $this->post("/admin/affiliates/{$affiliate->id}/payouts", ['period' => '2026-09'])->assertSessionHasNoErrors();

        // A sale confirmed late must not silently change what was already paid.
        Order::factory()->referredBy($affiliate)->confirmed()->create(['created_at' => '2026-09-20 08:00:00', 'confirmed_at' => '2026-09-20 08:00:00']);
        $this->post("/admin/affiliates/{$affiliate->id}/payouts", ['period' => '2026-09']);

        $payout = AffiliatePayout::query()->sole();

        $this->assertSame(12000, $payout->amount);
        $this->assertSame('2026-09', $payout->period);

        $this->get("/admin/affiliates/{$affiliate->id}/invoices/2026-09")->assertOk()->assertSee('Paid Oct 15, 2026');
    }

    public function test_the_current_month_cannot_be_paid_out(): void
    {
        $affiliate = $this->affiliate();

        $this->post("/admin/affiliates/{$affiliate->id}/payouts", ['period' => '2026-10'])->assertSessionHasErrors('period');
        $this->post("/admin/affiliates/{$affiliate->id}/payouts", ['period' => 'October'])->assertSessionHasErrors('period');

        $this->assertDatabaseCount('affiliate_payouts', 0);
    }
}
