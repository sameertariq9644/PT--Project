<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Support\AdminNavCounts;
use App\Support\Money;
use App\Support\SiteTime;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'email', 'whatsapp', 'transaction_id', 'transferred_on', 'utm_campaign'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'awaiting_payment',
        'commission_amount' => 0,
    ];

    /**
     * Order numbers are derived from the primary key: DPT-1001, DPT-1002, …
     */
    public static function numberFor(int $id): string
    {
        return 'DPT-'.(1000 + $id);
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<Affiliate, $this>
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function formattedAmount(): string
    {
        return Money::usd($this->amount);
    }

    /**
     * The commission this order earns (or would earn once confirmed).
     */
    public function expectedCommission(): int
    {
        if ($this->affiliate_id === null || $this->commission_rate === null) {
            return 0;
        }

        return (int) round($this->amount * $this->commission_rate / 100);
    }

    protected static function booted(): void
    {
        static::saving(function (Order $order): void {
            $order->placed_on ??= SiteTime::date($order->created_at ?? now());

            if ($order->isDirty('confirmed_at')) {
                $order->confirmed_on = $order->confirmed_at ? SiteTime::date($order->confirmed_at) : null;
            }
        });

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
            'amount' => 'integer',
            'status' => OrderStatus::class,
            'transferred_on' => 'date',
            'commission_rate' => 'float',
            'commission_amount' => 'integer',
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
