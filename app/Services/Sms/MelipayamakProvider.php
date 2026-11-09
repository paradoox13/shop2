<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Support\Config;
use App\Support\Http;
use App\Support\Logger;
use RuntimeException;

final class MelipayamakProvider implements SmsProviderInterface
{
    public function name(): string
    {
        return 'melipayamak';
    }

    public function send(string $phone, string $message): array
    {
        $apiKey = (string) Config::get('sms.melipayamak.api_key', '');
        $sender = (string) Config::get('sms.melipayamak.sender', '');

        if ($apiKey !== '') {
            return $this->sendWithApiKey($apiKey, $sender, $phone, $message);
        }

        $username = (string) Config::get('sms.melipayamak.username', '');
        $password = (string) Config::get('sms.melipayamak.password', '');

        if ($username === '' || $password === '') {
            throw new RuntimeException('تنظیمات ملی‌پیامک (API Key یا نام کاربری/رمز) کامل نیست.');
        }

        $response = Http::postForm('https://rest.payamak-panel.com/api/SendSMS/SendSMS', [
            'username' => $username,
            'password' => $password,
            'to' => $phone,
            'from' => $sender,
            'text' => $message,
            'isflash' => 'false',
        ]);

        $data = $response['data'] ?? [];
        $raw = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $success = $response['ok'] && ((int) ($data['RetStatus'] ?? 0) === 1 || (int) ($data['Value'] ?? 0) > 0);

        if ($success) {
            return ['success' => true, 'response' => mb_substr((string) $raw, 0, 5000)];
        }

        Logger::warning('Melipayamak legacy send failed', ['http' => $response['status']]);

        return ['success' => false, 'response' => mb_substr((string) ($data['StrRetStatus'] ?? $response['error'] ?? 'ارسال پیامک ناموفق بود.') . ' | ' . $raw, 0, 5000)];
    }

    private function sendWithApiKey(string $apiKey, string $sender, string $phone, string $message): array
    {
        $url = 'https://console.melipayamak.com/api/send/simple/' . rawurlencode($apiKey);
        $response = Http::postJson($url, [
            'from' => $sender,
            'to' => $phone,
            'text' => $message,
        ]);

        $data = $response['data'] ?? [];
        $raw = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $success = $response['ok'] && (isset($data['recId']) || isset($data['recid']) || !isset($data['error']));

        if ($success && ($data === [] || isset($data['recId']) || isset($data['recid']))) {
            return ['success' => true, 'response' => mb_substr((string) $raw, 0, 5000)];
        }

        Logger::warning('Melipayamak API send failed', ['http' => $response['status']]);

        $error = (string) ($data['error'] ?? $data['message'] ?? $response['error'] ?? 'ارسال پیامک ناموفق بود.');

        return ['success' => false, 'response' => mb_substr($error . ' | ' . (string) $raw, 0, 5000)];
    }
}
