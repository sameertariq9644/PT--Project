<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailables\Address;

class OrderReceivedAdminMail extends AdminNotificationMail
{
    public function __construct(public Order $order)
    {
        parent::__construct();
    }

    protected function subjectLine(): string
    {
        return 'Payment receipt to verify: '.$this->order->number;
    }

    protected function heading(): string
    {
        return 'A payment receipt is waiting for verification';
    }

    protected function rows(): array
    {
        return [
            'Order' => $this->order->number,
            'Student' => $this->order->name,
            'Email' => $this->order->email,
            'WhatsApp' => $this->order->whatsapp,
            'Plan' => $this->order->plan_name,
            'Amount due' => $this->order->formattedAmount(),
            'Transaction ID' => $this->order->transaction_id,
            'Transfer date' => $this->order->transferred_on?->format('M j, Y'),
        ];
    }

    protected function replyToAddress(): Address
    {
        return new Address($this->order->email, $this->order->name);
    }

    protected function action(): ?array
    {
        return ['url' => route('admin.orders', ['order' => $this->order->number, 'status' => 'all']), 'label' => 'Verify in admin'];
    }
}
