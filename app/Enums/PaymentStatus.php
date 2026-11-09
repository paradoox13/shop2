<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Canceled = 'canceled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار تأیید',
            self::Paid => 'پرداخت شده',
            self::Failed => 'ناموفق',
            self::Canceled => 'لغو شده',
            self::Refunded => 'بازگشت وجه',
        };
    }
}
