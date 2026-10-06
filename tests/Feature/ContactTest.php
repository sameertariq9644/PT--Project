<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Hamza Ali',
            'email' => 'hamza@example.com',
            'whatsapp' => '',
            'topic' => 'About a plan',
            'message' => 'Which plan fits a final-year student?',
            '_elapsed' => 7000,
            ...$overrides,
        ];
    }

    public function test_a_message_is_stored_and_the_admin_is_notified(): void
    {
        Settings::set(['notifications.lead_recipients' => 'owner@example.com']);

        $this->postJson('/contact', $this->payload())->assertCreated();

        $message = ContactMessage::query()->sole();

        $this->assertSame('About a plan', $message->topic);
        $this->assertNull($message->whatsapp);

        Mail::assertQueued(ContactMessageMail::class, function (ContactMessageMail $mail) use ($message): bool {
            return $mail->contactMessage->is($message) && $mail->hasTo('owner@example.com') && $mail->hasReplyTo('hamza@example.com');
        });
    }

    public function test_invalid_messages_are_rejected(): void
    {
        $this->postJson('/contact', $this->payload(['email' => 'nope', 'topic' => 'Free money', 'message' => 'Hi']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'topic', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_spam_is_dropped_silently(): void
    {
        $this->postJson('/contact', $this->payload(['website' => 'https://spam.example']))->assertCreated();

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingQueued();
    }

    public function test_the_endpoint_is_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/contact', $this->payload())->assertCreated();
        }

        $this->postJson('/contact', $this->payload())->assertTooManyRequests();
    }
}
