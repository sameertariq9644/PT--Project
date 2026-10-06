<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveAffiliate
{
    /**
     * Create or update an affiliate together with its login, in one
     * transaction. Pausing the affiliate also blocks the login.
     *
     * @param  array{name: string, email: string, username: string, password?: ?string, code: string, channel?: ?string, commission_rate: float|int|string, is_active: bool}  $data
     */
    public function handle(array $data, ?Affiliate $affiliate = null): Affiliate
    {
        return DB::transaction(function () use ($data, $affiliate): Affiliate {
            $user = $affiliate?->user ?? new User;

            $user->fill([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'username' => mb_strtolower($data['username']),
            ]);

            if (filled($data['password'] ?? null)) {
                $user->password = $data['password'];
            }

            $user->role = UserRole::Affiliate;
            $user->is_active = $data['is_active'];
            $user->save();

            $affiliate ??= new Affiliate;

            $affiliate->fill([
                'code' => Affiliate::normalizeCode($data['code']),
                'channel' => $data['channel'] ?? null,
                'commission_rate' => $data['commission_rate'],
                'is_active' => $data['is_active'],
            ]);

            $affiliate->user_id = $user->id;
            $affiliate->save();

            return $affiliate;
        });
    }
}
