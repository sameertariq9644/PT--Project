<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Support\TemplateRenderer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RejectOrder
{
    /**
     * Reject the payment with a reason and email the customer.
     * Returns false if the order was already decided.
     */
    public function handle(Order $order, string $note): bool
    {
        return DB::transaction(function () use ($order, $note): bool {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status->isDecided()) {
                return false;
            }

            $order->status = OrderStatus::Rejected;
            $order->rejected_at = now();
            $order->admin_note = $note;
            $order->save();

            Mail::to($order->email)->queue(
                TemplatedMail::forKey(EmailTemplate::OrderRejected, TemplateRenderer::variables(order: $order)),
            );

            return true;
        });
    }
}
