<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case PendingPhysicalSend = 'pending_physical_send';
    case Sent = 'sent';
    case Completed = 'completed';
    case Canceled = 'canceled';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پرداخت',
            self::AwaitingPayment => 'در انتظار پرداخت',
            self::Paid => 'پرداخت شده',
            self::PendingPhysicalSend => 'در انتظار ارسال فیزیکی',
            self::Sent => 'ارسال شده',
            self::Completed => 'تکمیل شده',
            self::Canceled => 'لغو شده',
            self::Failed => 'ناموفق',
            self::Refunded => 'بازگشت وجه',
        };
    }

    public function isPaid(): bool
    {
        return in_array($this, [self::Paid, self::PendingPhysicalSend, self::Sent, self::Completed], true);
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending, self::AwaitingPayment => in_array($next, [self::Paid, self::Failed, self::Canceled], true),
            self::Paid => in_array($next, [self::Sent, self::Completed, self::Refunded], true),
            self::PendingPhysicalSend => in_array($next, [self::Sent, self::Completed, self::Refunded], true),
            self::Sent => in_array($next, [self::Completed, self::Refunded], true),
            self::Completed => $next === self::Refunded,
            default => false,
        };
    }
}
