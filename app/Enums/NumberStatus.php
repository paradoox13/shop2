<?php

declare(strict_types=1);

namespace App\Enums;

enum NumberStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'موجود',
            self::Reserved => 'رزرو شده',
            self::Sold => 'فروخته شده',
            self::Hidden => 'مخفی',
        };
    }

    public function isPurchasable(): bool
    {
        return $this === self::Available;
    }
}
