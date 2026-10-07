<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the first admin from ADMIN_EMAIL / ADMIN_PASSWORD. Credentials
     * are never hard-coded; without them, use `php artisan app:create-admin`.
     */
    public function run(): void
    {
        $email = config('site.admin.email');
        $password = config('site.admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD are not set, no admin created. Run: php artisan app:create-admin you@example.com');

            return;
        }

        $user = User::query()->firstOrNew(['email' => mb_strtolower($email)]);

        if ($user->exists) {
            return;
        }

        $user->name = 'Admin';
        $user->username = 'admin';
        $user->password = $password;
        $user->role = UserRole::Admin;
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();
    }
}
