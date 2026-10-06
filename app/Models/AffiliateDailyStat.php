<?php

namespace App\Models;

use Database\Factories\AffiliateDailyStatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['affiliate_id', 'date', 'campaign', 'clicks', 'unique_visitors'])]
class AffiliateDailyStat extends Model
{
    /** @use HasFactory<AffiliateDailyStatFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Affiliate, $this>
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clicks' => 'integer',
            'unique_visitors' => 'integer',
        ];
    }
}
