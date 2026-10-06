<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\EmailTemplate;
use App\Models\Plan;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_creates_plans_templates_settings_and_the_admin_from_env(): void
    {
        config(['site.admin' => ['email' => 'Owner@Example.com', 'password' => 'from-the-env']]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(['roadmap-session', 'pathway-mentorship', 'complete-journey'], Plan::cachedActive()->pluck('slug')->all());
        $this->assertTrue(Plan::query()->where('slug', 'pathway-mentorship')->sole()->is_featured);
        $this->assertSame(4, EmailTemplate::query()->where('is_system', true)->count());
        $this->assertEquals(20, Settings::get('affiliates.default_commission_rate'));

        $admin = User::query()->sole();

        $this->assertSame('owner@example.com', $admin->email);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(Hash::check('from-the-env', $admin->password));
    }

    public function test_seeding_again_keeps_an_admins_edits(): void
    {
        $this->seed(DatabaseSeeder::class);

        Plan::query()->where('slug', 'roadmap-session')->sole()->update(['price' => 15000]);
        EmailTemplate::findByKey(EmailTemplate::LeadVideo)->update(['subject' => 'My wording']);
        Settings::set(['affiliates.default_commission_rate' => '35']);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(15000, Plan::query()->where('slug', 'roadmap-session')->sole()->price);
        $this->assertSame('My wording', EmailTemplate::findByKey(EmailTemplate::LeadVideo)->subject);
        $this->assertSame('35', Settings::get('affiliates.default_commission_rate'));
        $this->assertSame(3, Plan::query()->count());
    }

    public function test_no_admin_is_seeded_without_credentials(): void
    {
        config(['site.admin' => ['email' => null, 'password' => null]]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_the_create_admin_command_creates_an_admin_and_resets_a_password(): void
    {
        $this->artisan('app:create-admin', ['email' => 'owner@example.com', '--password' => 'first-secret'])->assertSuccessful();

        $admin = User::query()->sole();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);

        $this->artisan('app:create-admin', ['email' => 'owner@example.com', '--password' => 'second-secret'])->assertSuccessful();

        $this->assertTrue(Hash::check('second-secret', $admin->refresh()->password));
        $this->assertSame(1, User::query()->count());
    }

    public function test_the_create_admin_command_rejects_weak_input_and_affiliate_accounts(): void
    {
        $this->artisan('app:create-admin', ['email' => 'owner@example.com', '--password' => 'short'])->assertFailed();
        $this->assertDatabaseCount('users', 0);

        $affiliate = $this->affiliate();

        $this->artisan('app:create-admin', ['email' => $affiliate->user->email, '--password' => 'long-enough-1'])->assertFailed();
        $this->assertSame(UserRole::Affiliate, $affiliate->user->refresh()->role);
    }
}
