<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Customer = 'customer';
    case Reseller = 'reseller';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'مشتری',
            self::Reseller => 'نماینده',
            self::Admin => 'ادمین',
            self::SuperAdmin => 'مدیر کل',
        };
    }

    public function isStaff(): bool
    {
        return $this === self::Admin || $this === self::SuperAdmin;
    }

    public function homeUrl(): string
    {
        return match ($this) {
            self::SuperAdmin, self::Admin => '/admin',
            self::Reseller => '/reseller',
            self::Customer => '/',
        };
    }

    public static function tryFromString(string $value): self
    {
        return self::tryFrom($value) ?? self::Customer;
    }
}
