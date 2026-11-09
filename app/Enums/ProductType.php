<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductType: string
{
    case Number = 'number';
    case Modem = 'modem';
    case ActivationCode = 'activation_code';

    public function label(): string
    {
        return match ($this) {
            self::Number => 'شماره سیم‌کارت',
            self::Modem => 'مودم',
            self::ActivationCode => 'کد فعال‌سازی',
        };
    }

    public function isPhysical(): bool
    {
        return $this === self::Modem || $this === self::ActivationCode;
    }
}
