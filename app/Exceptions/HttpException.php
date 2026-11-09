<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
    ) {
        parent::__construct($message !== '' ? $message : 'خطای HTTP');
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @param array<string, string> $headers */
    public function getHeaders(): array
    {
        return [];
    }
}
