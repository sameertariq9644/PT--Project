<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Mail\OrderReceivedAdminMail;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Plan;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');

        $this->plan = Plan::factory()->create(['slug' => 'pathway-mentorship', 'name' => 'Pathway Mentorship', 'price' => 30000]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function details(array $overrides = []): array
    {
        return ['name' => 'Ayesha Khan', 'email' => 'ayesha@example.com', 'whatsapp' => '+92 300 1234567', '_elapsed' => 9000, ...$overrides];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function proof(array $overrides = []): array
    {
        return ['transaction_id' => 'FT2610039921', 'transferred_on' => now()->toDateString(), 'receipt' => UploadedFile::fake()->image('receipt.jpg'), ...$overrides];
    }

    private function submitProof(Order $order, array $data): TestResponse
    {
        return $this->post(URL::signedRoute('orders.proof', ['order' => $order]), $data, ['Accept' => 'application/json']);
    }

    public function test_the_whole_funnel_from_checkout_to_confirmed_customer(): void
    {
        Settings::set([
            'notifications.order_recipients' => 'owner@example.com',
            'payment.bank_name' => 'Meezan Bank',
            'payment.account_number' => '0123456789',
            'contact.whatsapp' => '+92 300 1112223',
        ]);

        $affiliate = $this->affiliate(['code' => 'SANA20', 'commission_rate' => 20]);
        $lead = Lead::factory()->create(['email' => 'ayesha@example.com']);

        // Step 1: details.
        $payUrl = $this->withCredentials()->withUnencryptedCookies(['dpt_ref' => 'SANA20', 'dpt_campaign' => 'npte-reel'])
            ->postJson('/checkout/pathway-mentorship', $this->details())
            ->assertCreated()
            ->json('redirect');

        $order = Order::query()->sole();

        $this->assertSame('DPT-'.(1000 + $order->id), $order->number);
        $this->assertSame(OrderStatus::AwaitingPayment, $order->status);
        $this->assertSame('Pathway Mentorship', $order->plan_name);
        $this->assertSame(30000, $order->amount);
        $this->assertSame($lead->id, $order->lead_id);
        $this->assertSame($affiliate->id, $order->affiliate_id);
        $this->assertSame(20.0, $order->commission_rate);
        $this->assertSame('npte-reel', $order->utm_campaign);

        // Step 2: bank details, then the receipt.
        $this->get($payUrl)
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Meezan Bank')
            ->assertSee('0123456789')
            ->assertSee('$30,000')
            ->assertSee($order->number);

        $this->submitProof($order, $this->proof())->assertCreated();

        $order->refresh();

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame('FT2610039921', $order->transaction_id);
        Storage::disk('local')->assertExists($order->receipt_path);
        $this->assertStringStartsWith('receipts/', $order->receipt_path);

        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail): bool => $mail->hasTo('ayesha@example.com') && $mail->template->key === EmailTemplate::OrderReceived);
        Mail::assertQueued(OrderReceivedAdminMail::class, fn (OrderReceivedAdminMail $mail): bool => $mail->hasTo('owner@example.com') && $mail->hasReplyTo('ayesha@example.com'));

        // Admin confirms.
        $this->actingAs($this->admin())
            ->post("/admin/orders/{$order->number}/confirm", ['admin_note' => 'Amount matches statement'])
            ->assertRedirect(route('admin.orders', ['status' => 'all', 'order' => $order->number]));

        $order->refresh();

        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertSame(6000, $order->commission_amount);
        $this->assertSame('Amount matches statement', $order->admin_note);
        $this->assertSame(LeadStatus::Customer, $lead->refresh()->status);

        Mail::assertQueued(TemplatedMail::class, function (TemplatedMail $mail) use ($order): bool {
            return $mail->hasTo('ayesha@example.com')
                && $mail->template->key === EmailTemplate::OrderConfirmed
                && $mail->variables['whatsapp'] === '+92 300 1112223'
                && $mail->variables['order_number'] === $order->number;
        });
    }

    public function test_the_plan_price_is_snapshotted_on_the_order(): void
    {
        $this->postJson('/checkout/pathway-mentorship', $this->details())->assertCreated();

        $this->plan->update(['price' => 45000, 'name' => 'Renamed']);

        $order = Order::query()->sole();

        $this->assertSame(30000, $order->amount);
        $this->assertSame('Pathway Mentorship', $order->plan_name);
    }

    public function test_checkout_details_are_validated(): void
    {
        $this->postJson('/checkout/pathway-mentorship', $this->details(['email' => 'nope', 'whatsapp' => 'call me', 'name' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'whatsapp']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_plan_without_a_price_cannot_be_ordered(): void
    {
        Plan::factory()->unpriced()->create(['slug' => 'unpriced']);

        $this->postJson('/checkout/unpriced', $this->details())->assertUnprocessable();
        $this->get('/checkout/unpriced')->assertOk()->assertSee('be bought online yet');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_an_unknown_or_hidden_plan_is_not_found(): void
    {
        Plan::factory()->inactive()->create(['slug' => 'hidden']);

        $this->postJson('/checkout/hidden', $this->details())->assertNotFound();
        $this->postJson('/checkout/nope', $this->details())->assertNotFound();
    }

    public function test_a_bot_submission_creates_no_order(): void
    {
        $this->postJson('/checkout/pathway-mentorship', $this->details(['website' => 'spam']))->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_an_affiliate_does_not_earn_on_their_own_purchase(): void
    {
        $affiliate = $this->affiliate(['code' => 'SANA20']);

        $this->withCredentials()->withUnencryptedCookies(['dpt_ref' => 'SANA20'])
            ->postJson('/checkout/pathway-mentorship', $this->details(['email' => $affiliate->user->email]))
            ->assertCreated();

        $this->assertNull(Order::query()->sole()->affiliate_id);
    }

    public function test_an_order_without_a_cookie_falls_back_to_the_affiliate_who_referred_the_lead(): void
    {
        $affiliate = $this->affiliate();
        $lead = Lead::factory()->create(['email' => 'ayesha@example.com', 'utm_campaign' => 'story']);
        $lead->forceFill(['affiliate_id' => $affiliate->id])->save();

        $this->postJson('/checkout/pathway-mentorship', $this->details())->assertCreated();

        $order = Order::query()->sole();

        $this->assertSame($affiliate->id, $order->affiliate_id);
        $this->assertSame('story', $order->utm_campaign);
    }

    public function test_the_payment_page_needs_a_valid_signature(): void
    {
        $order = Order::factory()->create();

        $this->get("/orders/{$order->number}/pay")->assertForbidden();
        $this->get(URL::signedRoute('orders.pay', ['order' => $order]).'x')->assertForbidden();
        $this->post("/orders/{$order->number}/pay", $this->proof(), ['Accept' => 'application/json'])->assertForbidden();

        $this->assertSame(OrderStatus::AwaitingPayment, $order->refresh()->status);
    }

    public function test_invalid_receipts_are_rejected(): void
    {
        $order = Order::factory()->create();

        $this->submitProof($order, $this->proof(['receipt' => UploadedFile::fake()->create('receipt.exe', 20, 'application/x-msdownload')]))
            ->assertJsonValidationErrors('receipt');

        $this->submitProof($order, $this->proof(['receipt' => UploadedFile::fake()->create('receipt.pdf', 6 * 1024, 'application/pdf')]))
            ->assertJsonValidationErrors('receipt');

        $this->submitProof($order, $this->proof(['receipt' => null, 'transferred_on' => 'yesterday']))
            ->assertJsonValidationErrors(['receipt', 'transferred_on']);

        $this->assertSame(OrderStatus::AwaitingPayment, $order->refresh()->status);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_pdf_receipt_is_accepted(): void
    {
        $order = Order::factory()->create();

        $this->submitProof($order, $this->proof(['receipt' => UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf')]))->assertCreated();

        $this->assertStringEndsWith('.pdf', $order->refresh()->receipt_path);
    }

    public function test_proof_can_only_be_submitted_once(): void
    {
        $order = Order::factory()->create();

        $this->submitProof($order, $this->proof())->assertCreated();
        $firstReceipt = $order->refresh()->receipt_path;

        $this->submitProof($order, $this->proof(['transaction_id' => 'SECOND']))->assertConflict();

        $this->assertSame($firstReceipt, $order->refresh()->receipt_path);
        $this->assertSame('FT2610039921', $order->transaction_id);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }
}
