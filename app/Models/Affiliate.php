<?php

namespace App\Models;

use Database\Factories\AffiliateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable(['code', 'channel', 'commission_rate', 'payout_details', 'is_active'])]
class Affiliate extends Model
{
    /** @use HasFactory<AffiliateFactory> */
    use HasFactory;

    /**
     * Find the active affiliate behind a referral code, using a short-lived
     * cache so click tracking and form posts do not query on every request.
     */
    public static function findActiveByCode(?string $code): ?self
    {
        $code = static::normalizeCode($code);

        if ($code === '') {
            return null;
        }

        $id = Cache::remember(static::codeCacheKey($code), now()->addMinutes(10), function () use ($code): int {
            return (int) static::query()->active()->where('code', $code)->value('id');
        });

        return $id ? static::query()->active()->find($id) : null;
    }

    public static function normalizeCode(?string $code): string
    {
        return mb_strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', mb_substr((string) $code, 0, 40)));
    }

    public static function codeCacheKey(string $code): string
    {
        return 'affiliate.code.'.$code;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<AffiliatePayout, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    /**
     * @return HasMany<AffiliateDailyStat, $this>
     */
    public function dailyStats(): HasMany
    {
        return $this->hasMany(AffiliateDailyStat::class);
    }

    /**
     * @param  Builder<Affiliate>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::saved(function (Affiliate $affiliate): void {
            Cache::forget(static::codeCacheKey($affiliate->code));

            if ($affiliate->wasChanged('code')) {
                Cache::forget(static::codeCacheKey((string) $affiliate->getOriginal('code')));
            }
        });

        static::deleted(fn (Affiliate $affiliate) => Cache::forget(static::codeCacheKey($affiliate->code)));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commission_rate' => 'float',
            'is_active' => 'boolean',
        ];
    }
}
