<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\Support\Config;
use App\Support\Http;
use App\Support\Logger;

final class NextPayGateway implements GatewayInterface
{
    public function name(): string
    {
        return 'nextpay';
    }

    public function label(): string
    {
        return 'نکست‌پی';
    }

    public function request(int $amountToman, string $orderId, string $description, string $callbackUrl, array $customer = []): GatewayRequestResult
    {
        $apiKey = (string) Config::get('payment.gateways.nextpay.api_key', '');

        if ($apiKey === '') {
            return GatewayRequestResult::failure('کلید API نکست‌پی در تنظیمات وارد نشده است.');
        }

        $response = Http::postForm('https://nextpay.org/nx/gateway/token', [
            'api_key' => $apiKey,
            'order_id' => $orderId,
            'amount' => $this->toGatewayAmount($amountToman),
            'callback_uri' => $callbackUrl,
            'currency' => 'IRR',
        ]);
        $data = $response['data'] ?? [];
        $code = (int) ($data['code'] ?? -999);

        if (!$response['ok'] || $code !== 0 || empty($data['trans_id'])) {
            Logger::warning('NextPay request failed', ['http' => $response['status'], 'code' => $code]);
            $message = (string) ($data['message'] ?? $response['error'] ?? 'خطای درگاه نکست‌پی');

            return GatewayRequestResult::failure($message, $data);
        }

        $transId = (string) $data['trans_id'];

        return GatewayRequestResult::success($transId, 'https://nextpay.org/nx/gateway/payment/' . $transId, $data);
    }

    public function verify(array $callback, int $amountToman, string $orderId): GatewayResult
    {
        $transId = $this->gatewayReference($callback);

        if ($transId === null) {
            return GatewayResult::failure('شناسه تراکنش در پاسخ درگاه وجود ندارد.');
        }

        $apiKey = (string) Config::get('payment.gateways.nextpay.api_key', '');

        $response = Http::postForm('https://nextpay.org/nx/gateway/verify', [
            'api_key' => $apiKey,
            'trans_id' => $transId,
            'amount' => $this->toGatewayAmount($amountToman),
            'currency' => 'IRR',
        ]);
        $data = $response['data'] ?? [];
        $code = (int) ($data['code'] ?? -999);

        if ($response['ok'] && $code === 0) {
            return GatewayResult::success(
                isset($data['Shaparak_Ref_Id']) ? (string) $data['Shaparak_Ref_Id'] : $transId,
                isset($data['card_holder']) ? (string) $data['card_holder'] : null,
                $data
            );
        }

        $message = match ($code) {
            -1 => 'اطلاعات ارسالی به درگاه ناقص است.',
            -2 => 'API Key نامعتبر است.',
            -3 => 'مبلغ با مبلغ تراکنش هم‌خوانی ندارد.',
            -4 => 'تراکنش یافت نشد.',
            -49 => 'پرداخت انجام نشده است.',
            default => (string) ($data['message'] ?? 'تأیید پرداخت نکست‌پی ناموفق بود.'),
        };

        $retryable = $response['status'] === 0 || $code === -49;

        return GatewayResult::failure($message, $data, $retryable);
    }

    public function gatewayReference(array $callback): ?string
    {
        $reference = $callback['trans_id'] ?? null;

        return is_string($reference) && $reference !== '' ? $reference : null;
    }

    private function toGatewayAmount(int $amountToman): int
    {
        return (int) round($amountToman * (int) Config::get('payment.gateway_amount_multiplier', 10));
    }
}
