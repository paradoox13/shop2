<?php

declare(strict_types=1);

namespace App\Services\Gateways;

final readonly class GatewayResult
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public bool $success,
        public string $message,
        public ?string $reference = null,
        public ?string $cardPan = null,
        public array $raw = [],
        public bool $retryable = false,
    ) {
    }

    /** @param array<string, mixed> $raw */
    public static function success(?string $reference, ?string $cardPan = null, array $raw = []): self
    {
        return new self(true, 'پرداخت با موفقیت تأیید شد.', $reference, $cardPan, $raw);
    }

    /** @param array<string, mixed> $raw */
    public static function failure(string $message, array $raw = [], bool $retryable = false): self
    {
        return new self(false, $message, null, null, $raw, $retryable);
    }
}
