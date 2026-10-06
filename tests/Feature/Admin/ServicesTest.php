<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Roadmap Session',
            'kicker' => 'Starting out',
            'teaser' => 'A 1:1 call.',
            'description' => 'A focused call.',
            'price' => '15000',
            'highlights' => "60-minute call\n\n  State shortlist  ",
            'features' => "60-minute call\nRoadmap PDF",
            'sort_order' => '1',
            'is_featured' => '1',
            'is_active' => '1',
            ...$overrides,
        ];
    }

    public function test_saving_a_plan_updates_the_public_site_immediately(): void
    {
        $plan = Plan::factory()->unpriced()->create(['slug' => 'roadmap-session', 'name' => 'Roadmap Session']);

        // Warm the cached plan list the public pages read.
        $this->get('/services')->assertSee('$[—]');

        $this->actingAs($this->admin())
            ->put("/admin/services/{$plan->id}", $this->payload())
            ->assertRedirect(route('admin.services'));

        $plan->refresh();

        $this->assertSame(15000, $plan->price);
        $this->assertTrue($plan->is_featured);
        $this->assertSame(['60-minute call', 'State shortlist'], $plan->highlights);
        $this->assertSame(['60-minute call', 'Roadmap PDF'], $plan->features);

        $this->get('/services')->assertSee('$15,000')->assertSee('MOST CHOSEN')->assertDontSee('$[—]');
    }

    public function test_an_empty_price_and_unticked_boxes_are_stored_as_such(): void
    {
        $plan = Plan::factory()->create(['is_featured' => true]);

        $this->actingAs($this->admin())->put("/admin/services/{$plan->id}", $this->payload(['price' => '', 'is_featured' => '0', 'is_active' => '0']));

        $plan->refresh();

        $this->assertNull($plan->price);
        $this->assertFalse($plan->is_featured);
        $this->assertFalse($plan->is_active);
    }

    public function test_invalid_plan_details_are_rejected(): void
    {
        $plan = Plan::factory()->create(['price' => 30000]);

        $this->actingAs($this->admin())
            ->put("/admin/services/{$plan->id}", $this->payload(['name' => '', 'price' => '-5']))
            ->assertSessionHasErrors(['name', 'price']);

        $this->assertSame(30000, $plan->refresh()->price);
    }

    public function test_the_index_lists_every_plan_including_hidden_ones(): void
    {
        Plan::factory()->create(['name' => 'Visible Plan']);
        Plan::factory()->inactive()->create(['name' => 'Hidden Plan']);

        $this->actingAs($this->admin())->get('/admin/services')->assertOk()->assertSee('Visible Plan')->assertSee('Hidden Plan');
    }
}
