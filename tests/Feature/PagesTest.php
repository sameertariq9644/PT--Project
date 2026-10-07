<?php

namespace Tests\Feature;

use App\Http\Middleware\PublicCacheHeaders;
use App\Models\Plan;
use App\Support\Settings;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
    }

    #[DataProvider('pages')]
    public function test_page_renders(string $uri, string $text): void
    {
        $this->get($uri)->assertOk()->assertSee($text);
    }

    #[DataProvider('pages')]
    public function test_public_pages_are_cacheable_and_set_no_cookies(string $uri): void
    {
        $response = $this->get($uri)->assertOk();

        $this->assertSame([], $response->headers->getCookies());

        foreach (explode(', ', PublicCacheHeaders::HEADER) as $directive) {
            $this->assertStringContainsString($directive, $response->headers->get('Cache-Control'));
        }
    }

    #[DataProvider('pages')]
    public function test_public_pages_run_no_queries_once_the_cache_is_warm(string $uri): void
    {
        $this->get($uri)->assertOk();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->get($uri)->assertOk();

        $this->assertSame(0, $queries);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function pages(): array
    {
        return [
            'home' => ['/', 'licensed PT'],
            'pathway' => ['/pathway', 'Six steps from Pakistan'],
            'services' => ['/services', 'Choose the support that fits'],
            'about' => ['/about', 'A Pakistani DPT who made it'],
            'faq' => ['/faq', 'Answers to what students ask most.'],
            'contact' => ['/contact', 'Send a message'],
            'checkout' => ['/checkout/roadmap-session', 'Order summary'],
        ];
    }

    public function test_eligibility_link_keeps_the_referral_code(): void
    {
        $this->get('/eligibility?ref=SANA20')->assertRedirect(route('home', ['ref' => 'SANA20']).'#eligibility');
    }

    public function test_plan_prices_come_from_the_database(): void
    {
        Plan::query()->where('slug', 'roadmap-session')->firstOrFail()->update(['price' => 30000]);

        $this->get('/services')
            ->assertSee('$30,000')
            ->assertSee('$[-]')
            ->assertSee(route('checkout.show', 'roadmap-session'));
    }

    public function test_a_hidden_plan_disappears_from_the_public_site(): void
    {
        Plan::query()->where('slug', 'complete-journey')->firstOrFail()->update(['is_active' => false]);

        $this->get('/services')->assertDontSee('Start to finish');
        $this->get('/checkout/complete-journey')->assertNotFound();
    }

    public function test_contact_details_come_from_settings(): void
    {
        $this->get('/contact')->assertSee('[email@domain.com]');

        Settings::set(['contact.email' => 'hello@dpt.example', 'contact.whatsapp' => '+92 300 1112223']);

        $this->get('/contact')
            ->assertSee('hello@dpt.example')
            ->assertSee('https://wa.me/923001112223')
            ->assertDontSee('[email@domain.com]');
    }

    public function test_the_intro_video_button_carries_the_embed_url_from_settings(): void
    {
        Settings::set(['content.intro_video_url' => 'https://www.youtube.com/watch?v=abc123XYZ_-']);

        $this->get('/')->assertSee('data-video="https://www.youtube-nocookie.com/embed/abc123XYZ_-?autoplay=1&amp;rel=0"', false);
    }

    public function test_responses_carry_security_headers(): void
    {
        $this->get('/about')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
