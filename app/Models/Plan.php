<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['slug', 'name', 'kicker', 'teaser', 'description', 'price', 'is_featured', 'highlights', 'features', 'sort_order', 'is_active'])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    public const CACHE_KEY = 'plans.active';

    /**
     * The plans shown on the public site, cached until an admin changes one.
     *
     * The cache holds plain attribute arrays, not models: Laravel does not
     * unserialize PHP classes from the cache (config cache.serializable_classes).
     *
     * @return Collection<int, Plan>
     */
    public static function cachedActive(): Collection
    {
        $rows = Cache::rememberForever(static::CACHE_KEY, function (): array {
            return static::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->toBase()
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all();
        });

        return static::hydrate($rows);
    }

    public static function findActiveBySlug(string $slug): ?self
    {
        return static::cachedActive()->firstWhere('slug', $slug);
    }

    /**
     * The price as shown to visitors, e.g. "$30,000".
     */
    public function formattedPrice(): string
    {
        return Money::usd($this->price);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(static::CACHE_KEY));
        static::deleted(fn () => Cache::forget(static::CACHE_KEY));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'highlights' => 'array',
            'features' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
