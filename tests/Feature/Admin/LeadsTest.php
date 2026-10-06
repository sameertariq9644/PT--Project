<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStatus;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->actingAs($this->admin());
    }

    public function test_the_index_lists_leads_with_their_answers_and_real_counts(): void
    {
        Lead::factory()->create(['name' => 'Ayesha Khan']);
        Lead::factory()->status(LeadStatus::Eligible)->count(2)->create();

        $this->get('/admin/leads')
            ->assertOk()
            ->assertSee('Ayesha Khan')
            ->assertSee('Riphah International University')
            ->assertSee('All · 3')
            ->assertSee('Eligible · 2')
            ->assertSee('Needs review · 1')
            ->assertDontSee('SAMPLE DATA');
    }

    public function test_the_status_tab_and_search_filter_the_list(): void
    {
        Lead::factory()->status(LeadStatus::Eligible)->create(['name' => 'Hamza Ali', 'email' => 'hamza@example.com']);
        Lead::factory()->status(LeadStatus::Eligible)->create(['name' => 'Zainab Raza']);
        Lead::factory()->create(['name' => 'Fatima Noor']);

        $this->get('/admin/leads?status=eligible')
            ->assertSee('Hamza Ali')->assertSee('Zainab Raza')->assertDontSee('Fatima Noor');

        $this->get('/admin/leads?q=hamza@')
            ->assertSee('Hamza Ali')->assertDontSee('Zainab Raza')->assertDontSee('Fatima Noor');
    }

    public function test_the_list_is_paginated(): void
    {
        Lead::factory()->count(26)->create();
        $oldest = Lead::query()->oldest('id')->first();

        $this->get('/admin/leads')->assertDontSee($oldest->email)->assertSee('Showing 1–25 of 26');
        $this->get('/admin/leads?page=2')->assertSee($oldest->email);
    }

    public function test_the_nav_badge_counts_leads_that_need_review(): void
    {
        Lead::factory()->count(3)->create();
        Lead::factory()->status(LeadStatus::Eligible)->create();

        $this->get('/admin/leads')->assertSee('<span class="admin-nav__count">3</span>', false);
    }

    public function test_an_admin_can_change_a_leads_status(): void
    {
        $lead = Lead::factory()->create();

        $this->patch("/admin/leads/{$lead->id}/status", ['status' => 'not_eligible'])->assertRedirect();

        $this->assertSame(LeadStatus::NotEligible, $lead->refresh()->status);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $lead = Lead::factory()->create();

        $this->patch("/admin/leads/{$lead->id}/status", ['status' => 'vip'])->assertSessionHasErrors('status');

        $this->assertSame(LeadStatus::NeedsReview, $lead->refresh()->status);
    }

    public function test_send_video_queues_the_lead_video_template_and_marks_the_lead_eligible(): void
    {
        Settings::set(['content.lead_video_url' => 'https://youtu.be/detailed123']);
        $lead = Lead::factory()->create(['name' => 'Ayesha Khan', 'email' => 'ayesha@example.com']);

        $this->freezeSecond();
        $this->post("/admin/leads/{$lead->id}/video")->assertRedirect();

        $lead->refresh();

        $this->assertSame(LeadStatus::Eligible, $lead->status);
        $this->assertEquals(now(), $lead->video_sent_at);

        Mail::assertQueued(TemplatedMail::class, function (TemplatedMail $mail): bool {
            return $mail->hasTo('ayesha@example.com')
                && $mail->template->key === EmailTemplate::LeadVideo
                && $mail->variables['name'] === 'Ayesha Khan'
                && $mail->variables['video_url'] === 'https://youtu.be/detailed123';
        });

        $this->get('/admin/leads')->assertSee('Sent')->assertDontSee('Send video');
    }

    public function test_resending_the_video_keeps_a_customer_a_customer(): void
    {
        $lead = Lead::factory()->status(LeadStatus::Customer)->create();

        $this->post("/admin/leads/{$lead->id}/video");

        $this->assertSame(LeadStatus::Customer, $lead->refresh()->status);
    }

    public function test_a_custom_template_can_be_sent_from_the_lead_page(): void
    {
        $lead = Lead::factory()->create(['email' => 'ayesha@example.com']);
        $template = EmailTemplate::factory()->create(['name' => 'Follow up']);

        $this->get("/admin/leads/{$lead->id}")->assertOk()->assertSee('Follow up')->assertSee('Clinical experience');

        $this->post("/admin/leads/{$lead->id}/email", ['template_id' => $template->id])->assertRedirect();

        Mail::assertQueued(TemplatedMail::class, fn (TemplatedMail $mail): bool => $mail->hasTo('ayesha@example.com') && $mail->template->is($template));
        $this->assertNull($lead->refresh()->video_sent_at);
    }

    public function test_the_export_streams_the_filtered_leads_as_csv(): void
    {
        Lead::factory()->status(LeadStatus::Eligible)->create(['name' => 'Hamza Ali', 'email' => 'hamza@example.com']);
        Lead::factory()->create(['name' => '=HYPERLINK("http://evil.example")']);

        $response = $this->get('/admin/leads/export')->assertOk()->assertDownload();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Name,Email,WhatsApp,"Grad year",University', $csv);
        $this->assertStringContainsString('"Hamza Ali",hamza@example.com', $csv);
        $this->assertStringContainsString('"\'=HYPERLINK(""http://evil.example"")"', $csv);

        $filtered = $this->get('/admin/leads/export?status=eligible')->streamedContent();

        $this->assertStringContainsString('Hamza Ali', $filtered);
        $this->assertStringNotContainsString('HYPERLINK', $filtered);
    }
}
