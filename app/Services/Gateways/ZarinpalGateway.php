<?php

declare(strict_types=1);

namespace App\Services\Gateways;

use App\Support\Config;
use App\Support\Http;
use App\Support\Logger;

final class ZarinpalGateway implements GatewayInterface
{
    private const ERROR_MESSAGES = [
        -9 => 'اطلاعات ارسالی به درگاه معتبر نیست.',
        -10 => 'پذیرنده یا مرچنت‌کد معتبر نیست.',
        -11 => 'مرچنت‌کد فعال نشده است.',
        -12 => 'تلاش‌های بیش از حد؛ کمی بعد دوباره امتحان کنید.',
        -15 => 'پذیرنده توسط شاپرک مسدود شده است.',
        -16 => 'سطح تأیید پذیرنده نامعتبر است.',
        -17 => 'مبلغ تراکنش با مبلغ درخواستی یکسان نیست.',
        -30 => 'عملیات برای پشتیبانی بانک تعریف نشده است.',
        -31 => 'بانک پذیرنده غیرفعال است.',
        -32 => 'مبلغ تراکنش از سقف مجاز بیشتر است.',
        -33 => 'مبلغ تراکنش از حداقل مجاز کمتر است.',
        -34 => 'مبلغ تراکنش از سقف مجاز روزانه بیشتر است.',
        -40 => 'داده ارسالی معتبر نیست.',
        -50 => 'مبلغ پرداخت‌شده با مبلغ سفارش مغایرت دارد.',
        -51 => 'پرداخت انجام نشده یا ناموفق بوده است.',
        -52 => 'خطای غیرمنتظره در درگاه رخ داد.',
        -53 => 'پرداخت ناموفق بود.',
        -54 => 'شناسه پرداخت (Authority) معتبر نیست.',
    ];

    public function name(): string
    {
        return 'zarinpal';
    }

    public function label(): string
    {
        return 'زرین‌پال';
    }

    public function request(int $amountToman, string $orderId, string $description, string $callbackUrl, array $customer = []): GatewayRequestResult
    {
        $merchantId = (string) Config::get('payment.gateways.zarinpal.merchant_id', '');

        if ($merchantId === '') {
            return GatewayRequestResult::failure('مرچنت‌کد زرین‌پال در تنظیمات وارد نشده است.');
        }

        $sandbox = (bool) Config::get('payment.gateways.zarinpal.sandbox', false);
        $base = $sandbox ? 'https://sandbox.zarinpal.com' : 'https://api.zarinpal.com';

        $payload = [
            'merchant_id' => $merchantId,
            'amount' => $this->toGatewayAmount($amountToman),
            'callback_url' => $callbackUrl,
            'description' => $description,
        ];

        if (isset($customer['phone']) && $customer['phone'] !== '') {
            $payload['metadata'] = ['mobile' => (string) $customer['phone']];
        }

        if (isset($customer['email']) && $customer['email'] !== '') {
            $payload['metadata']['email'] = (string) $customer['email'];
        }

        $response = Http::postJson($base . '/pg/v4/payment/request.json', $payload);
        $data = $response['data'];
        $code = (int) ($data['data']['code'] ?? 0);

        if (!$response['ok'] || $code !== 100 || empty($data['data']['authority'])) {
            $errorCode = (int) ($data['errors']['code'] ?? 0);
            $message = self::ERROR_MESSAGES[$errorCode] ?? (string) ($data['errors']['message'] ?? $response['error'] ?? 'خطای درگاه زرین‌پال');
            Logger::warning('Zarinpal request failed', ['code' => $errorCode, 'http' => $response['status']]);

            return GatewayRequestResult::failure($message, $data ?? []);
        }

        $authority = (string) $data['data']['authority'];
        $redirect = ($sandbox ? 'https://sandbox.zarinpal.com' : 'https://www.zarinpal.com') . '/pg/StartPay/' . $authority;

        return GatewayRequestResult::success($authority, $redirect, $data);
    }

    public function verify(array $callback, int $amountToman, string $orderId): GatewayResult
    {
        $authority = $this->gatewayReference($callback);

        if ($authority === null) {
            return GatewayResult::failure('شناسه پرداخت (Authority) در پاسخ درگاه وجود ندارد.');
        }

        if (($callback['Status'] ?? '') !== 'OK') {
            return GatewayResult::failure('پرداخت توسط کاربر لغو شد یا ناموفق بود.', $callback);
        }

        $merchantId = (string) Config::get('payment.gateways.zarinpal.merchant_id', '');
        $sandbox = (bool) Config::get('payment.gateways.zarinpal.sandbox', false);
        $base = $sandbox ? 'https://sandbox.zarinpal.com' : 'https://api.zarinpal.com';

        $response = Http::postJson($base . '/pg/v4/payment/verify.json', [
            'merchant_id' => $merchantId,
            'amount' => $this->toGatewayAmount($amountToman),
            'authority' => $authority,
        ]);

        $data = $response['data'];
        $code = (int) ($data['data']['code'] ?? 0);
        $errorCode = (int) ($data['errors']['code'] ?? 0);

        if ($response['ok'] && in_array($code, [100, 101], true)) {
            return GatewayResult::success(
                isset($data['data']['ref_id']) ? (string) $data['data']['ref_id'] : $authority,
                isset($data['data']['card_pan']) ? (string) $data['data']['card_pan'] : null,
                $data ?? []
            );
        }

        $message = self::ERROR_MESSAGES[$errorCode] ?? (string) ($data['errors']['message'] ?? $response['error'] ?? 'تأیید پرداخت ناموفق بود.');
        $networkFailure = $response['status'] === 0;

        return GatewayResult::failure($message, $data ?? [], $networkFailure);
    }

    public function gatewayReference(array $callback): ?string
    {
        $reference = $callback['Authority'] ?? $callback['authority'] ?? null;

        return is_string($reference) && $reference !== '' ? $reference : null;
    }

    private function toGatewayAmount(int $amountToman): int
    {
        return (int) round($amountToman * (int) Config::get('payment.gateway_amount_multiplier', 10));
    }
}
