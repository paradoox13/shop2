<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\Models\Setting;
use InvalidArgumentException;

final class GatewayFactory
{
    public static function activeName(): string
    {
        $fromSettings = Setting::get('payment_driver');

        return $fromSettings !== '' ? $fromSettings : (string) config('payment.driver', 'zarinpal');
    }

    public static function make(?string $name = null): GatewayInterface
    {
        $name ??= self::activeName();

        return match ($name) {
            'zarinpal' => new ZarinpalGateway(),
            'idpay' => new IdPayGateway(),
            'nextpay' => new NextPayGateway(),
            'fake' => new FakeGateway(),
            default => throw new InvalidArgumentException('درگاه پرداخت نامعتبر است.'),
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            'zarinpal' => 'زرین‌پال',
            'idpay' => 'آی‌دی‌پی',
            'nextpay' => 'نکست‌پی',
        ];
    }
}
