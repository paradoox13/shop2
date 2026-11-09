<?php

declare(strict_types=1);

namespace App\Services\Sms;

interface SmsProviderInterface
{
    public function name(): string;

    /**
     * @return array{success:bool, response:string}
     */
    public function send(string $phone, string $message): array;
}
