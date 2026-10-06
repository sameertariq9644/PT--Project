<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateDailyStat;
use App\Models\AffiliatePayout;
use App\Models\Order;
use App\Support\SiteTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AffiliatePortalTest extends TestCase
{
    use RefreshDatabase;

    private Affiliate $affiliate;

    protected function setUp(): void
    {
        parent::setUp();

        // Mid-month, midday in Karachi, so "today", "this month" and "last month" are unambiguous.
        $this->travelTo('2026-10-15 07:00:00');

        $this->affiliate = $this->affiliate(['code' => 'SANA20', 'commission_rate' => 20]);
        $this->affiliate->user->update(['name' => 'Sana Ahmed']);
    }

    /**
     * A confirmed $30,000 sale for the affiliate, confirmed at the given moment.
     */
    private function confirmedSale(Affiliate $affiliate, string $confirmedAt, array $attributes = []): Order
    {
        return Order::factory()->referredBy($affiliate)->confirmed()->create([
            'created_at' => $confirmedAt,
            'confirmed_at' => $confirmedAt,
            ...$attributes,
        ]);
    }

    public function test_the_portal_shows_the_affiliates_own_link_rate_and_earnings(): void
    {
        $this->confirmedSale($this->affiliate, '2026-09-10 08:00:00');
        $this->confirmedSale($this->affiliate, '2026-08-05 08:00:00');
        AffiliatePayout::factory()->for($this->affiliate)->create(['period' => '2026-08', 'amount' => 6000]);
        Order::factory()->referredBy($this->affiliate)->pending()->create(['name' => 'Ayesha Khan', 'amount' => 50000]);

        $response = $this->actingAs($this->affiliate->user)->get('/affiliate')->assertOk();

        $response
            ->assertSee('Welcome back, Sana.')
            ->assertSee('ref=SANA20')
            ->assertSee('20%')
            ->assertSeeInOrder(['PENDING', '$10,000', '1 sale being verified'])
            ->assertSeeInOrder(['APPROVED', '$6,000'])
            ->assertSeeInOrder(['PAID OUT', '$6,000'])
            ->assertSee('A***a K.')
            ->assertSee('Pending verification')
            ->assertSee('Paid out')
            ->assertDontSee('Ayesha Khan')
            ->assertDontSee('SAMPLE DATA');
    }

    public function test_stats_report_clicks_from_aggregates_and_money_from_orders(): void
    {
        AffiliateDailyStat::factory()->for($this->affiliate)->create(['date' => '2026-10-14', 'clicks' => 40, 'unique_visitors' => 30]);
        AffiliateDailyStat::factory()->for($this->affiliate)->create(['date' => '2026-10-14', 'campaign' => 'reel', 'clicks' => 10, 'unique_visitors' => 5]);
        AffiliateDailyStat::factory()->for($this->affiliate)->create(['date' => '2026-10-07', 'clicks' => 8, 'unique_visitors' => 8]);

        $this->confirmedSale($this->affiliate, '2026-10-14 09:00:00');
        Order::factory()->referredBy($this->affiliate)->pending()->create(['created_at' => '2026-10-13 09:00:00']);
        Order::factory()->referredBy($this->affiliate)->rejected()->create(['created_at' => '2026-10-13 09:00:00']);

        $this->actingAs($this->affiliate->user)->getJson('/affiliate/stats?range=7d')
            ->assertOk()
            ->assertJsonPath('text', 'Oct 9 – Oct 15, 2026')
            ->assertJsonPath('monthly', false)
            ->assertJsonPath('cur', ['clicks' => 50, 'visitors' => 35, 'checkouts' => 3, 'sales' => 1, 'revenue' => 30000, 'commission' => 6000])
            ->assertJsonPath('pv.clicks', 8)
            ->assertJsonPath('pv.sales', 0)
            ->assertJsonCount(7, 'buckets')
            ->assertJsonPath('buckets.5', ['lab' => 'Oct 14', 'clicks' => 50, 'visitors' => 35, 'checkouts' => 1, 'sales' => 1, 'revenue' => 30000, 'commission' => 6000]);
    }

    public function test_days_are_bucketed_in_karachi_time(): void
    {
        // 20:30 UTC on the 13th is already 01:30 on the 14th in Karachi.
        $this->confirmedSale($this->affiliate, '2026-10-13 20:30:00');

        $this->actingAs($this->affiliate->user)->getJson('/affiliate/stats?range=7d')
            ->assertJsonPath('buckets.4.sales', 0)
            ->assertJsonPath('buckets.5.sales', 1);
    }

    public function test_long_ranges_are_bucketed_by_month(): void
    {
        $this->confirmedSale($this->affiliate, '2026-03-10 08:00:00');

        $this->actingAs($this->affiliate->user)->getJson('/affiliate/stats?range=yr')
            ->assertJsonPath('monthly', true)
            ->assertJsonCount(10, 'buckets')
            ->assertJsonPath('buckets.2.lab', 'Mar 2026')
            ->assertJsonPath('buckets.2.commission', 6000);
    }

    public function test_a_custom_range_is_honoured_and_a_bad_one_is_rejected(): void
    {
        $this->confirmedSale($this->affiliate, '2026-09-10 08:00:00');
        $this->actingAs($this->affiliate->user);

        $this->getJson('/affiliate/stats?range=custom&from=2026-09-01&to=2026-09-30')
            ->assertJsonPath('text', 'Sep 1 – Sep 30, 2026')
            ->assertJsonPath('cur.sales', 1);

        $this->getJson('/affiliate/stats?range=forever')->assertJsonValidationErrors('range');
        $this->getJson('/affiliate/stats?range=custom&from=last-week')->assertJsonValidationErrors('from');
    }

    public function test_an_affiliate_never_sees_another_affiliates_data(): void
    {
        $other = $this->affiliate(['code' => 'OTHER']);

        AffiliateDailyStat::factory()->for($other)->create(['date' => '2026-10-14', 'clicks' => 99]);
        $this->confirmedSale($other, '2026-10-14 09:00:00', ['name' => 'Usman Tariq']);
        $this->confirmedSale($other, '2026-09-14 09:00:00');

        $this->actingAs($this->affiliate->user);

        $this->getJson('/affiliate/stats?range=30d&affiliate='.$other->id)
            ->assertJsonPath('cur', ['clicks' => 0, 'visitors' => 0, 'checkouts' => 0, 'sales' => 0, 'revenue' => 0, 'commission' => 0]);

        $this->get('/affiliate')->assertDontSee('U***n T.')->assertDontSee('OTHER')->assertSeeInOrder(['APPROVED', '$0']);

        $this->get('/affiliate/invoices/2026-09')->assertOk()->assertSee('INV-SANA20-202609')->assertSee('No confirmed sales in September 2026.');
    }

    public function test_the_invoice_total_equals_the_months_commissions(): void
    {
        $this->affiliate->update(['payout_details' => "Meezan Bank\nIBAN PK00MEZN"]);

        $first = $this->confirmedSale($this->affiliate, '2026-09-03 08:00:00', ['amount' => 30000, 'commission_amount' => 6000]);
        $second = $this->confirmedSale($this->affiliate, '2026-09-28 08:00:00', ['amount' => 55000, 'commission_amount' => 11000, 'plan_name' => 'Complete Journey']);
        // 20:00 UTC on Sep 30 is already October in Karachi; August and rejected orders are out too.
        $this->confirmedSale($this->affiliate, '2026-09-30 20:00:00', ['amount' => 99000, 'commission_amount' => 19800]);
        $this->confirmedSale($this->affiliate, '2026-08-31 08:00:00', ['amount' => 77000, 'commission_amount' => 15400]);
        Order::factory()->referredBy($this->affiliate)->rejected()->create(['created_at' => '2026-09-10 08:00:00', 'amount' => 44000]);

        $this->actingAs($this->affiliate->user)->get('/affiliate/invoices/2026-09')
            ->assertOk()
            ->assertSee('INV-SANA20-202609')
            ->assertSee('Sana Ahmed')
            ->assertSee('IBAN PK00MEZN')
            ->assertSee($first->number)
            ->assertSee($second->number)
            ->assertSee('Complete Journey')
            ->assertSeeInOrder(['Total', '$85,000', '$17,000'])
            ->assertSee('Unpaid')
            ->assertDontSee('$19,800')
            ->assertDontSee('$15,400')
            ->assertDontSee('$44,000');
    }

    public function test_only_completed_months_can_be_invoiced(): void
    {
        $this->actingAs($this->affiliate->user);

        $this->get('/affiliate/invoices/2026-10')->assertNotFound();
        $this->get('/affiliate/invoices/2026-13')->assertNotFound();
        $this->get('/affiliate/invoices/next-month')->assertNotFound();

        $this->get('/affiliate/invoices?month=2026-10')->assertRedirect(route('affiliate.portal'))->assertSessionHasErrors('month');
        $this->get('/affiliate/invoices?month=2026-09')->assertRedirect(route('affiliate.invoices.show', '2026-09'));
    }

    public function test_an_affiliate_can_update_payout_details_and_change_their_password(): void
    {
        $user = $this->affiliate->user;
        $this->actingAs($user);

        $this->put('/affiliate/profile', ['payout_details' => 'Meezan Bank, IBAN PK00'])->assertRedirect(route('affiliate.profile'));

        $this->assertSame('Meezan Bank, IBAN PK00', $this->affiliate->refresh()->payout_details);
        $this->assertTrue(Hash::check('password', $user->refresh()->password));

        $this->put('/affiliate/profile', ['current_password' => 'wrong', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'])
            ->assertSessionHasErrors('current_password');

        $this->put('/affiliate/profile', ['current_password' => 'password', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-1', $user->refresh()->password));
    }

    public function test_site_time_today_is_the_karachi_calendar_day(): void
    {
        $this->travelTo('2026-10-15 20:00:00');

        $this->assertSame('2026-10-16', SiteTime::today()->toDateString());
    }
}
