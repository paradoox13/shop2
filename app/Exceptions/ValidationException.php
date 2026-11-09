<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    public function __construct(
        private readonly array $errors,
        private readonly array $old = [],
        string $message = 'اطلاعات وارد شده معتبر نیست.',
    ) {
        parent::__construct($message);
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<string, mixed> */
    public function old(): array
    {
        return $this->old;
    }
}
