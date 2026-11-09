<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Support\Config;
use App\Support\Http;
use App\Support\Logger;
use RuntimeException;

final class KavenegarProvider implements SmsProviderInterface
{
    public function name(): string
    {
        return 'kavenegar';
    }

    public function send(string $phone, string $message): array
    {
        $apiKey = (string) Config::get('sms.kavenegar.api_key', '');

        if ($apiKey === '') {
            throw new RuntimeException('کلید API کاوه‌نگار در تنظیمات وارد نشده است.');
        }

        $payload = [
            'receptor' => $phone,
            'message' => $message,
        ];

        $sender = (string) Config::get('sms.kavenegar.sender', '');
        if ($sender !== '') {
            $payload['sender'] = $sender;
        }

        $url = 'https://api.kavenegar.com/v1/' . rawurlencode($apiKey) . '/sms/send.json';
        $response = Http::postForm($url, $payload);
        $data = $response['data'] ?? [];
        $status = (int) ($data['return']['status'] ?? 0);
        $messageId = $data['entries'][0]['messageid'] ?? null;

        $raw = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($response['ok'] && $status === 200 && $messageId !== null) {
            return ['success' => true, 'response' => mb_substr((string) $raw, 0, 5000)];
        }

        Logger::warning('Kavenegar send failed', ['http' => $response['status'], 'status' => $status]);

        $error = (string) ($data['return']['message'] ?? $response['error'] ?? 'ارسال پیامک ناموفق بود.');

        return ['success' => false, 'response' => mb_substr($error . ' | ' . (string) $raw, 0, 5000)];
    }
}
