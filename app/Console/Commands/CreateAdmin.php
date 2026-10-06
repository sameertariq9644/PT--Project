<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('app:create-admin {email : The admin\'s email address} {--name= : Display name} {--password= : Password (prompted for when omitted)}')]
#[Description('Create an admin account, or reset the password of an existing one')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = mb_strtolower((string) $this->argument('email'));
        $password = $this->option('password') ?: $this->secret('Password (at least 8 characters)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', Password::min(8)]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);

        if ($user->exists && ! $user->isAdmin()) {
            $this->error('That email belongs to an affiliate account.');

            return self::FAILURE;
        }

        $user->name = $this->option('name') ?: ($user->name ?? 'Admin');
        $user->password = $password;
        $user->role = UserRole::Admin;
        $user->is_active = true;
        $user->email_verified_at ??= now();
        $user->save();

        $this->info($user->wasRecentlyCreated ? 'Admin account created for '.$email.'.' : 'Password reset for '.$email.'.');

        return self::SUCCESS;
    }
}
