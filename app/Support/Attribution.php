<?php

namespace App\Support;

use App\Models\Affiliate;
use Illuminate\Http\Request;

/**
 * Who referred this visitor, according to the first-party cookies set by
 * resources/js/tracking.js when they arrived through a `?ref=CODE` link.
 */
class Attribution
{
    public const REF_COOKIE = 'dpt_ref';

    public const CAMPAIGN_COOKIE = 'dpt_campaign';

    public function __construct(public readonly ?Affiliate $affiliate, public readonly ?string $campaign) {}

    public static function fromRequest(Request $request): self
    {
        $ref = $request->cookie(self::REF_COOKIE);
        $campaign = $request->cookie(self::CAMPAIGN_COOKIE);

        return new self(
            is_string($ref) ? Affiliate::findActiveByCode($ref) : null,
            is_string($campaign) ? static::normalizeCampaign($campaign) : null,
        );
    }

    public static function none(): self
    {
        return new self(null, null);
    }

    public static function normalizeCampaign(?string $campaign): ?string
    {
        $campaign = trim(preg_replace('/[^a-z0-9_-]+/', '-', mb_strtolower(mb_substr((string) $campaign, 0, 80))), '-');

        return $campaign === '' ? null : $campaign;
    }

    /**
     * The affiliate to credit for someone with this email, or null. An
     * affiliate never earns on a purchase made with their own email.
     */
    public function affiliateFor(string $email): ?Affiliate
    {
        if ($this->affiliate === null) {
            return null;
        }

        return mb_strtolower($this->affiliate->user->email) === mb_strtolower($email) ? null : $this->affiliate;
    }
}
