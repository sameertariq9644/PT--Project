<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Mail\OrderReceivedAdminMail;
use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Support\AdminNavCounts;
use App\Support\Site;
use App\Support\TemplateRenderer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SubmitPaymentProof
{
    /**
     * Attach the transfer details and receipt to an order awaiting payment
     * and move it to "Pending" for the admin to verify.
     *
     * Returns false, storing nothing, when proof was already submitted.
     *
     * @param  array{transaction_id: ?string, transferred_on: string}  $details
     */
    public function handle(Order $order, array $details, UploadedFile $receipt): bool
    {
        // Private disk, random name: receipts are only served through the admin route.
        $path = $receipt->store('receipts', 'local');

        $updated = Order::query()
            ->whereKey($order->id)
            ->where('status', OrderStatus::AwaitingPayment->value)
            ->update([
                'transaction_id' => $details['transaction_id'] ?? null,
                'transferred_on' => $details['transferred_on'],
                'receipt_path' => $path,
                'status' => OrderStatus::Pending->value,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            Storage::disk('local')->delete($path);

            return false;
        }

        $order->refresh();

        // Model events do not fire for the conditional update above.
        AdminNavCounts::flush();

        Mail::to($order->email)->queue(
            TemplatedMail::forKey(EmailTemplate::OrderReceived, TemplateRenderer::variables(order: $order)),
        );

        $recipients = Site::recipients('notifications.order_recipients');

        if ($recipients !== []) {
            Mail::to($recipients)->queue(new OrderReceivedAdminMail($order));
        }

        return true;
    }
}
