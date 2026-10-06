<?php

namespace App\Actions;

use App\Enums\LeadStatus;
use App\Enums\OrderStatus;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\Order;
use App\Support\TemplateRenderer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ConfirmOrder
{
    /**
     * Confirm the payment: the order becomes Confirmed, the affiliate's
     * commission is fixed, the matching lead becomes a Customer and the
     * customer is emailed. Returns false if the order was already decided.
     */
    public function handle(Order $order, ?string $note = null): bool
    {
        return DB::transaction(function () use ($order, $note): bool {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status->isDecided()) {
                return false;
            }

            $order->status = OrderStatus::Confirmed;
            $order->confirmed_at = now();
            $order->commission_amount = $order->expectedCommission();

            if (filled($note)) {
                $order->admin_note = $note;
            }

            $order->save();

            $lead = $order->lead_id
                ? Lead::query()->find($order->lead_id)
                : Lead::query()->where('email', $order->email)->latest('id')->first();

            if ($lead !== null) {
                $lead->status = LeadStatus::Customer;
                $lead->save();

                if ($order->lead_id === null) {
                    $order->lead_id = $lead->id;
                    $order->save();
                }
            }

            Mail::to($order->email)->queue(
                TemplatedMail::forKey(EmailTemplate::OrderConfirmed, TemplateRenderer::variables(order: $order)),
            );

            return true;
        });
    }
}
