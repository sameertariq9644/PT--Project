<?php

namespace App\Enums;

enum OrderStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'Awaiting payment',
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * The `.status--*` modifier used to render this status.
     */
    public function tone(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'grey',
            self::Pending => 'amber',
            self::Confirmed => 'green',
            self::Rejected => 'red',
        };
    }

    /**
     * Whether an admin has already confirmed or rejected the order.
     */
    public function isDecided(): bool
    {
        return in_array($this, [self::Confirmed, self::Rejected], true);
    }
}
