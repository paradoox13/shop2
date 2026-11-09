<?php

declare(strict_types=1);

namespace App\Services\Gateways;

final readonly class GatewayRequestResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public bool $success,
        public string $message,
        public ?string $reference = null,
        public ?string $redirectUrl = null,
        public array $raw = [],
    ) {
    }

    /** @param array<string, mixed> $raw */
    public static function success(string $reference, string $redirectUrl, array $raw = []): self
    {
        return new self(true, 'درخواست پرداخت ایجاد شد.', $reference, $redirectUrl, $raw);
    }

    /** @param array<string, mixed> $raw */
    public static function failure(string $message, array $raw = []): self
    {
        return new self(false, $message, null, null, $raw);
    }
}
