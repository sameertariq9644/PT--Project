<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Mail\LeadReceivedMail;
use App\Models\Lead;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_submitting_the_form_stores_a_lead_and_queues_the_admin_notification(): void
    {
        Settings::set(['notifications.lead_recipients' => 'owner@example.com, staff@example.com']);

        $response = $this->postJson('/leads', $this->leadPayload())->assertCreated();

        $this->assertSame([], $response->headers->getCookies());

        $lead = Lead::query()->sole();

        $this->assertSame('Ayesha Khan', $lead->name);
        $this->assertSame('ayesha@example.com', $lead->email);
        $this->assertSame(LeadStatus::NeedsReview, $lead->status);
        $this->assertSame('Riphah International University', $lead->answer('university'));
        $this->assertSame('Not yet', $lead->answer('fccpt'));

        Mail::assertQueued(LeadReceivedMail::class, function (LeadReceivedMail $mail) use ($lead): bool {
            return $mail->lead->is($lead)
                && $mail->hasTo('owner@example.com')
                && $mail->hasTo('staff@example.com')
                && $mail->hasReplyTo('ayesha@example.com');
        });
    }

    public function test_the_notification_lists_every_answer(): void
    {
        $lead = Lead::factory()->create(['name' => 'Hamza Ali', 'whatsapp' => '+92 301 7654321']);

        (new LeadReceivedMail($lead))
            ->assertSeeInHtml('Hamza Ali')
            ->assertSeeInHtml('+92 301 7654321')
            ->assertSeeInHtml('Riphah International University')
            ->assertSeeInText('FCCPT: Not yet');
    }

    public function test_invalid_submissions_return_validation_errors_as_json(): void
    {
        $this->post('/leads', $this->leadPayload(['email' => 'not-an-email', 'fccpt' => 'Fluent', 'university' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'fccpt', 'university'])
            ->assertJsonMissingValidationErrors(['name', 'graduation_year']);

        $this->assertDatabaseCount('leads', 0);
        Mail::assertNothingQueued();
    }

    public function test_a_filled_honeypot_is_accepted_but_stores_nothing(): void
    {
        $this->postJson('/leads', $this->leadPayload(['website' => 'https://spam.example']))->assertCreated();

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_a_form_submitted_faster_than_a_person_could_fill_it_stores_nothing(): void
    {
        $this->postJson('/leads', $this->leadPayload(['_elapsed' => 300]))->assertCreated();

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_the_endpoint_is_rate_limited_per_ip(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/leads', $this->leadPayload(['email' => "student{$attempt}@example.com"]))->assertCreated();
        }

        $this->postJson('/leads', $this->leadPayload(['email' => 'student6@example.com']))->assertTooManyRequests();

        $this->assertDatabaseCount('leads', 5);
    }

    public function test_resubmitting_within_a_day_updates_the_existing_lead(): void
    {
        Settings::set(['notifications.lead_recipients' => 'owner@example.com']);

        $this->postJson('/leads', $this->leadPayload())->assertCreated();
        $this->postJson('/leads', $this->leadPayload(['email' => 'AYESHA@example.com', 'fccpt' => 'Completed']))->assertCreated();

        $this->assertSame('Completed', Lead::query()->sole()->answer('fccpt'));
        Mail::assertQueuedCount(1);
    }

    public function test_resubmitting_after_a_day_creates_a_new_lead(): void
    {
        Lead::factory()->create(['email' => 'ayesha@example.com', 'created_at' => now()->subDays(2)]);

        $this->postJson('/leads', $this->leadPayload())->assertCreated();

        $this->assertDatabaseCount('leads', 2);
    }

    public function test_a_lead_is_attributed_to_the_affiliate_in_the_referral_cookie(): void
    {
        $affiliate = $this->affiliate(['code' => 'SANA20']);

        $this->withCredentials()->withUnencryptedCookies(['dpt_ref' => 'sana20', 'dpt_campaign' => 'NPTE Reel'])
            ->postJson('/leads', $this->leadPayload())
            ->assertCreated();

        $lead = Lead::query()->sole();

        $this->assertSame($affiliate->id, $lead->affiliate_id);
        $this->assertSame('npte-reel', $lead->utm_campaign);
    }

    public function test_a_paused_or_unknown_affiliate_code_is_ignored(): void
    {
        $this->affiliate(['code' => 'PAUSED', 'is_active' => false]);

        $this->withCredentials()->withUnencryptedCookies(['dpt_ref' => 'PAUSED'])->postJson('/leads', $this->leadPayload())->assertCreated();
        $this->withCredentials()->withUnencryptedCookies(['dpt_ref' => 'NOBODY'])->postJson('/leads', $this->leadPayload(['email' => 'other@example.com']))->assertCreated();

        $this->assertSame(0, Lead::query()->whereNotNull('affiliate_id')->count());
    }

    public function test_adding_a_field_to_the_config_changes_the_form_validation_and_the_admin_table(): void
    {
        config(['lead_form.fields' => [
            ...config('lead_form.fields'),
            ['key' => 'cgpa', 'label' => 'CGPA', 'type' => 'number', 'rules' => ['required', 'numeric', 'between:0,4'], 'in_table' => true],
        ]]);

        $this->get('/')->assertSee('name="cgpa"', false);

        $this->postJson('/leads', $this->leadPayload())->assertJsonValidationErrors('cgpa');
        $this->postJson('/leads', $this->leadPayload(['cgpa' => '3.6']))->assertCreated();

        $this->assertSame('3.6', Lead::query()->sole()->answer('cgpa'));

        $this->actingAs($this->admin())->get('/admin/leads')->assertSeeInOrder(['CGPA', 'Ayesha Khan', '3.6']);
    }
}
