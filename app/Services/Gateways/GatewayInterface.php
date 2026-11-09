<?php

declare(strict_types=1);

namespace App\Services\Gateways;

interface GatewayInterface
{
    public function name(): string;

    public function label(): string;

    /**
     * @param array<string, mixed> $customer
     */
    public function request(int $amountToman, string $orderId, string $description, string $callbackUrl, array $customer = []): GatewayRequestResult;

    /**
     * @param array<string, mixed> $callback
     */
    public function verify(array $callback, int $amountToman, string $orderId): GatewayResult;

    /**
     * استخراج شناسه پرداخت (authority/token/id) از داده Callback
     * @param array<string, mixed> $callback
     */
    public function gatewayReference(array $callback): ?string;
}
