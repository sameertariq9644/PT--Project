<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');
    }

    public function test_the_index_defaults_to_pending_orders_and_shows_the_selected_one(): void
    {
        $pending = Order::factory()->pending()->create(['name' => 'Ayesha Khan', 'transaction_id' => 'FT2610039921']);
        Order::factory()->confirmed()->create(['name' => 'Zainab Raza']);

        $this->actingAs($this->admin())->get('/admin/orders')
            ->assertOk()
            ->assertSee('Ayesha Khan')
            ->assertDontSee('Zainab Raza')
            ->assertSee('Pending · 1')
            ->assertSee('Confirmed · 1')
            ->assertSee('FT2610039921')
            ->assertSee(route('admin.orders.receipt', $pending))
            ->assertSee('$30,000')
            ->assertDontSee('SAMPLE DATA');
    }

    public function test_an_order_can_be_selected_and_searched(): void
    {
        $first = Order::factory()->pending()->create(['name' => 'Ayesha Khan']);
        $second = Order::factory()->pending()->create(['name' => 'Usman Tariq', 'transaction_id' => 'USMAN-TXN']);

        $this->actingAs($this->admin());

        $this->get("/admin/orders?order={$second->number}")->assertSee('USMAN-TXN');
        $this->get('/admin/orders?q=usman')->assertSee('Usman Tariq')->assertDontSee('Ayesha Khan');
        $this->get("/admin/orders?status=all&q={$first->number}")->assertSee('Ayesha Khan')->assertDontSee('Usman Tariq');
    }

    public function test_only_an_admin_can_view_a_receipt(): void
    {
        $path = UploadedFile::fake()->image('receipt.jpg')->store('receipts', 'local');
        $order = Order::factory()->pending()->create(['receipt_path' => $path]);

        $this->get("/admin/orders/{$order->number}/receipt")->assertRedirect(route('login'));
        $this->get('/storage/'.$path)->assertNotFound();

        $this->actingAs($this->affiliate()->user)->get("/admin/orders/{$order->number}/receipt")->assertForbidden();

        $this->actingAs($this->admin())->get("/admin/orders/{$order->number}/receipt")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_confirming_twice_changes_nothing_the_second_time(): void
    {
        $affiliate = $this->affiliate(['commission_rate' => 20]);
        $order = Order::factory()->pending()->referredBy($affiliate)->create();

        $this->actingAs($this->admin());
        $this->post("/admin/orders/{$order->number}/confirm");

        $confirmedAt = $order->refresh()->confirmed_at;
        $this->travel(5)->minutes();

        $this->post("/admin/orders/{$order->number}/confirm")->assertSessionHas('status', "Order {$order->number} was already confirmed, nothing changed.");
        $this->post("/admin/orders/{$order->number}/reject", ['admin_note' => 'Changed my mind']);

        $order->refresh();

        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertEquals($confirmedAt, $order->confirmed_at);
        $this->assertSame(6000, $order->commission_amount);
        Mail::assertQueuedCount(1);
    }

    public function test_an_unattributed_order_earns_no_commission(): void
    {
        $order = Order::factory()->pending()->create();

        $this->actingAs($this->admin())->post("/admin/orders/{$order->number}/confirm");

        $this->assertSame(0, $order->refresh()->commission_amount);
    }

    public function test_confirming_links_a_lead_found_by_email(): void
    {
        $lead = Lead::factory()->create(['email' => 'ayesha@example.com']);
        $order = Order::factory()->pending()->create(['email' => 'ayesha@example.com']);

        $this->actingAs($this->admin())->post("/admin/orders/{$order->number}/confirm");

        $this->assertSame(LeadStatus::Customer, $lead->refresh()->status);
        $this->assertSame($lead->id, $order->refresh()->lead_id);
    }

    public function test_rejecting_requires_a_note_and_emails_the_customer(): void
    {
        $lead = Lead::factory()->create();
        $order = Order::factory()->pending()->create(['email' => 'ayesha@example.com', 'lead_id' => $lead->id]);

        $this->actingAs($this->admin());

        $this->post("/admin/orders/{$order->number}/reject", ['admin_note' => ''])->assertSessionHasErrors('admin_note');
        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);

        $this->post("/admin/orders/{$order->number}/reject", ['admin_note' => 'Receipt unclear'])->assertSessionHasNoErrors();

        $order->refresh();

        $this->assertSame(OrderStatus::Rejected, $order->status);
        $this->assertSame('Receipt unclear', $order->admin_note);
        $this->assertNotNull($order->rejected_at);
        $this->assertSame(0, $order->commission_amount);
        $this->assertSame(LeadStatus::NeedsReview, $lead->refresh()->status);

        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail): bool => $mail->hasTo('ayesha@example.com') && $mail->template->key === EmailTemplate::OrderRejected);
    }
}
