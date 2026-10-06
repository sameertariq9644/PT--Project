<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Affiliate;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Plan;
use App\Support\Attribution;
use App\Support\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaceOrder
{
    /**
     * Create an order awaiting payment, snapshotting the plan's name and
     * price and the affiliate's commission rate as they are right now.
     *
     * @param  array{name: string, email: string, whatsapp: string}  $details
     */
    public function handle(Plan $plan, array $details, Attribution $attribution): Order
    {
        $email = mb_strtolower(trim($details['email']));

        return DB::transaction(function () use ($plan, $details, $email, $attribution): Order {
            $lead = Lead::query()->where('email', $email)->latest('id')->first();

            $affiliate = $attribution->affiliateFor($email);
            $campaign = $affiliate ? $attribution->campaign : null;

            if ($affiliate === null && $lead !== null) {
                $affiliate = $this->affiliateFromLead($lead, $email);
                $campaign = $affiliate ? $lead->utm_campaign : null;
            }

            $order = new Order([
                'name' => trim($details['name']),
                'email' => $email,
                'whatsapp' => trim($details['whatsapp']),
                'utm_campaign' => $campaign,
            ]);

            $order->number = 'TMP-'.Str::uuid();
            $order->plan_id = $plan->id;
            $order->plan_name = $plan->name;
            $order->amount = $plan->price;
            $order->status = OrderStatus::AwaitingPayment;
            $order->lead_id = $lead?->id;
            $order->affiliate_id = $affiliate?->id;
            $order->commission_rate = $affiliate?->commission_rate;
            $order->save();

            $order->number = Order::numberFor($order->id);
            $order->save();

            return $order;
        });
    }

    /**
     * Fall back to the affiliate who referred the lead, when the buyer has no
     * referral cookie (e.g. another device) and is still inside the window.
     */
    protected function affiliateFromLead(Lead $lead, string $email): ?Affiliate
    {
        if ($lead->affiliate_id === null || $lead->created_at->lt(now()->subDays(Site::attributionDays()))) {
            return null;
        }

        $affiliate = Affiliate::query()->active()->with('user')->find($lead->affiliate_id);

        return (new Attribution($affiliate, null))->affiliateFor($email);
    }
}
