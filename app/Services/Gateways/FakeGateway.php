<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\Support\Config;
use App\Support\Str;

/**
 * درگاه آزمایشی فقط برای local/development.
 * در محیط production به‌صورت خودکار غیرفعال است.
 */
final class FakeGateway implements GatewayInterface
{
    public function name(): string
    {
        return 'fake';
    }

    public function label(): string
    {
        return 'پرداخت آزمایشی';
    }

    public function request(int $amountToman, string $orderId, string $description, string $callbackUrl, array $customer = []): GatewayRequestResult
    {
        if (!Config::bool('payment.gateways.fake.enabled', false) || Config::string('app.env') === 'production') {
            return GatewayRequestResult::failure('درگاه آزمایشی در این محیط فعال نیست.');
        }

        $authority = 'FAKE-' . Str::random(8);
        $separator = str_contains($callbackUrl, '?') ? '&' : '?';

        return GatewayRequestResult::success(
            $authority,
            $callbackUrl . $separator . http_build_query(['authority' => $authority, 'status' => 'ok'])
        );
    }

    public function verify(array $callback, int $amountToman, string $orderId): GatewayResult
    {
        if (!Config::bool('payment.gateways.fake.enabled', false) || Config::string('app.env') === 'production') {
            return GatewayResult::failure('درگاه آزمایشی غیرفعال است.');
        }

        $authority = $this->gatewayReference($callback);

        if ($authority === null) {
            return GatewayResult::failure('شناسه پرداخت آزمایشی یافت نشد.');
        }

        if (($callback['status'] ?? '') !== 'ok') {
            return GatewayResult::failure('پرداخت آزمایشی ناموفق اعلام شد.', $callback);
        }

        return GatewayResult::success('FAKE-REF-' . Str::random(6), '6037-****-****-1234', $callback);
    }

    public function gatewayReference(array $callback): ?string
    {
        $reference = $callback['authority'] ?? $callback['Authority'] ?? null;

        return is_string($reference) && $reference !== '' ? $reference : null;
    }
}
