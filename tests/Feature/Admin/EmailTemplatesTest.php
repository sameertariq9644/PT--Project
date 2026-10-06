<?php

namespace Tests\Feature\Admin;

use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Support\TemplateRenderer;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_index_lists_the_system_templates(): void
    {
        $this->get('/admin/email-templates')
            ->assertOk()
            ->assertSee('Lead video')
            ->assertSee('Order confirmed');

        $this->assertSame(4, EmailTemplate::query()->where('is_system', true)->count());
    }

    public function test_an_admin_can_create_edit_and_delete_a_custom_template(): void
    {
        $this->post('/admin/email-templates', ['name' => 'Follow up', 'subject' => 'Hi {{name}}', 'body' => 'Just checking in.'])
            ->assertRedirect(route('admin.email-templates'));

        $template = EmailTemplate::query()->where('name', 'Follow up')->sole();
        $this->assertFalse($template->is_system);

        $this->put("/admin/email-templates/{$template->id}", ['name' => 'Follow up', 'subject' => 'Hello {{name}}', 'body' => 'Updated.'])
            ->assertRedirect(route('admin.email-templates'));

        $this->assertSame('Hello {{name}}', $template->refresh()->subject);

        $this->delete("/admin/email-templates/{$template->id}")->assertRedirect(route('admin.email-templates'));

        $this->assertModelMissing($template);
    }

    public function test_a_system_template_can_be_edited_but_not_deleted(): void
    {
        $this->seed(EmailTemplateSeeder::class);
        $template = EmailTemplate::findByKey(EmailTemplate::LeadVideo);

        $this->put("/admin/email-templates/{$template->id}", ['name' => 'Lead video', 'subject' => 'New subject', 'body' => 'New body'])
            ->assertSessionHasNoErrors();

        $this->assertSame('New subject', $template->refresh()->subject);
        $this->assertSame(EmailTemplate::LeadVideo, $template->key);

        $this->delete("/admin/email-templates/{$template->id}")->assertSessionHasErrors('template');

        $this->assertModelExists($template);
    }

    public function test_a_template_needs_a_name_subject_and_body(): void
    {
        $this->post('/admin/email-templates', ['name' => '', 'subject' => '', 'body' => ''])
            ->assertSessionHasErrors(['name', 'subject', 'body']);
    }

    public function test_placeholders_are_replaced_and_markdown_is_rendered(): void
    {
        $rendered = (new TemplateRenderer)->render(
            'Welcome, {{ name }}',
            "Hi **{{name}}**,\n\n[Watch the video]({{video_url}}) or call {{whatsapp}}.",
            ['name' => 'Ayesha', 'video_url' => 'https://youtu.be/abc?x=1&y=2', 'whatsapp' => '+92 300 1112223'],
        );

        $this->assertSame('Welcome, Ayesha', $rendered['subject']);
        $this->assertStringContainsString('<strong>Ayesha</strong>', $rendered['html']);
        $this->assertStringContainsString('<a href="https://youtu.be/abc?x=1&amp;y=2">Watch the video</a>', $rendered['html']);
        $this->assertStringContainsString('[Watch the video](https://youtu.be/abc?x=1&y=2) or call +92 300 1112223.', $rendered['text']);
    }

    public function test_html_in_values_and_in_the_template_is_neutralised(): void
    {
        $rendered = (new TemplateRenderer)->render(
            'Hello',
            'Hi {{name}} <script>alert("template")</script> [click](javascript:alert(1))',
            ['name' => '<img src=x onerror=alert(1)> [link](https://evil.example)'],
        );

        $this->assertStringNotContainsString('<script', $rendered['html']);
        $this->assertStringNotContainsString('<img', $rendered['html']);
        $this->assertStringNotContainsString('javascript:', $rendered['html']);
        $this->assertStringNotContainsString('href="https://evil.example"', $rendered['html']);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $rendered['html']);
    }

    public function test_blade_syntax_in_a_template_is_never_executed(): void
    {
        $template = EmailTemplate::factory()->create([
            'subject' => 'Result {{ 7*7 }}',
            'body' => "Sum: {{ 7*7 }}\n\n@php echo 'EXECUTED'; @endphp\n\n{!! phpversion() !!}",
        ]);

        $mail = new TemplatedMail($template, ['name' => 'Ayesha']);

        $mail->assertHasSubject('Result {{ 7*7 }}');
        $mail->assertSeeInHtml('{{ 7*7 }}');
        $mail->assertSeeInHtml('@php echo');
        $mail->assertDontSeeInHtml('49');
        $mail->assertDontSeeInHtml(phpversion());
        $mail->assertDontSeeInText('49');
    }

    public function test_the_preview_endpoint_renders_with_sample_values(): void
    {
        $this->postJson('/admin/email-templates/preview', ['subject' => 'For {{name}}', 'body' => 'Order **{{order_number}}**'])
            ->assertOk()
            ->assertJsonPath('subject', 'For Ayesha Khan')
            ->assertJson(fn ($json) => $json->where('html', fn (string $html): bool => str_contains($html, '<strong>DPT-1042</strong>'))->etc());
    }
}
