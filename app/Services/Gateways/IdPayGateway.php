<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\Support\Config;
use App\Support\Http;
use App\Support\Logger;

final class IdPayGateway implements GatewayInterface
{
    public function name(): string
    {
        return 'idpay';
    }

    public function label(): string
    {
        return 'آی‌دی‌پی';
    }

    public function request(int $amountToman, string $orderId, string $description, string $callbackUrl, array $customer = []): GatewayRequestResult
    {
        $apiKey = (string) Config::get('payment.gateways.idpay.api_key', '');

        if ($apiKey === '') {
            return GatewayRequestResult::failure('کلید API آی‌دی‌پی در تنظیمات وارد نشده است.');
        }

        $sandbox = (bool) Config::get('payment.gateways.idpay.sandbox', false);

        $payload = [
            'order_id' => $orderId,
            'amount' => $this->toGatewayAmount($amountToman),
            'callback' => $callbackUrl,
            'description' => $description,
        ];

        if (isset($customer['phone']) && $customer['phone'] !== '') {
            $payload['phone'] = (string) $customer['phone'];
        }

        if (isset($customer['email']) && $customer['email'] !== '') {
            $payload['mail'] = (string) $customer['email'];
        }

        if (isset($customer['name']) && $customer['name'] !== '') {
            $payload['name'] = (string) $customer['name'];
        }

        $headers = ['X-API-KEY: ' . $apiKey, 'X-SANDBOX: ' . ($sandbox ? '1' : '0')];
        $response = Http::postJson('https://api.idpay.ir/v1.1/payment', $payload, $headers);
        $data = $response['data'] ?? [];

        if (!$response['ok'] || empty($data['id']) || empty($data['link'])) {
            $message = (string) ($data['error_message'] ?? $data['error_code'] ?? $response['error'] ?? 'خطای درگاه آی‌دی‌پی');
            Logger::warning('IDPay request failed', ['http' => $response['status'], 'error' => $message]);

            return GatewayRequestResult::failure($message, $data);
        }

        return GatewayRequestResult::success((string) $data['id'], (string) $data['link'], $data);
    }

    public function verify(array $callback, int $amountToman, string $orderId): GatewayResult
    {
        $id = $this->gatewayReference($callback);

        if ($id === null) {
            return GatewayResult::failure('شناسه پرداخت در پاسخ درگاه وجود ندارد.');
        }

        $apiKey = (string) Config::get('payment.gateways.idpay.api_key', '');
        $sandbox = (bool) Config::get('payment.gateways.idpay.sandbox', false);
        $headers = ['X-API-KEY: ' . $apiKey, 'X-SANDBOX: ' . ($sandbox ? '1' : '0')];

        $response = Http::postJson('https://api.idpay.ir/v1.1/payment/verify', [
            'id' => $id,
            'order_id' => $orderId,
        ], $headers);

        $data = $response['data'] ?? [];
        $status = (int) ($data['status'] ?? 0);

        if ($response['ok'] && in_array($status, [100, 101], true)) {
            return GatewayResult::success(
                isset($data['track_id']) ? (string) $data['track_id'] : $id,
                isset($data['card_no']) ? (string) $data['card_no'] : null,
                $data
            );
        }

        $message = match ($status) {
            10 => 'در انتظار پرداخت توسط کاربر.',
            11 => 'پرداخت تأیید شده اما هنوز نهایی نشده است.',
            12 => 'پرداخت در حال انجام است.',
            100, 101 => 'پرداخت تأیید شد.',
            default => (string) ($data['error_message'] ?? 'تأیید پرداخت آی‌دی‌پی ناموفق بود.'),
        };

        $retryable = $response['status'] === 0 || in_array($status, [10, 11, 12], true);

        return GatewayResult::failure($message, $data, $retryable);
    }

    public function gatewayReference(array $callback): ?string
    {
        $reference = $callback['id'] ?? null;

        return is_string($reference) && $reference !== '' ? $reference : null;
    }

    private function toGatewayAmount(int $amountToman): int
    {
        return (int) round($amountToman * (int) Config::get('payment.gateway_amount_multiplier', 10));
    }
}
