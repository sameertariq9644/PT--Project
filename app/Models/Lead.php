<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Support\AdminNavCounts;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'email', 'whatsapp', 'answers', 'utm_campaign', 'ip'])]
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'needs_review',
        'answers' => '{}',
    ];

    /**
     * @return BelongsTo<Affiliate, $this>
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * The lead's answer to one of the configurable form fields.
     */
    public function answer(string $key): ?string
    {
        $value = $this->answers[$key] ?? null;

        return $value === null || $value === '' ? null : (string) $value;
    }

    protected static function booted(): void
    {
        static::saved(fn () => AdminNavCounts::flush());
        static::deleted(fn () => AdminNavCounts::flush());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'status' => LeadStatus::class,
            'video_sent_at' => 'datetime',
        ];
    }
}
