<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Gateways\GatewayFactory;
use App\Services\Gateways\GatewayInterface;
use App\Services\Gateways\GatewayResult;
use App\Support\Logger;
use RuntimeException;
use Throwable;

final class PaymentService
{
    public function gateway(): GatewayInterface
    {
        return GatewayFactory::make();
    }

    public function gatewayByName(string $name): GatewayInterface
    {
        return GatewayFactory::make($name);
    }

    public function callbackUrl(string $gateway): string
    {
        return url('/payment/callback/' . $gateway);
    }

    /**
     * ایجاد پرداخت برای سفارش و هدایت به درگاه.
     * @return array{redirect:string, payment_id:int}
     */
    public function initiate(int $orderId): array
    {
        $order = Order::find($orderId);

        if ($order === null) {
            throw new RuntimeException('سفارش یافت نشد.');
        }

        if (in_array((string) $order['status'], Order::PAID_STATUSES, true)) {
            throw new RuntimeException('این سفارش قبلاً پرداخت شده است.');
        }

        if ((int) $order['total_price'] <= 0) {
            throw new RuntimeException('مبلغ سفارش معتبر نیست.');
        }

        $gateway = $this->gateway();
        $paymentId = Payment::create($orderId, $gateway->name(), (int) $order['total_price']);

        $result = $gateway->request(
            (int) $order['total_price'],
            (string) $order['tracking_code'],
            'خرید از ' . (string) config('app.name', 'آوال سیم') . ' - ' . $order['tracking_code'],
            $this->callbackUrl($gateway->name()),
            [
                'name' => (string) $order['customer_name'],
                'phone' => (string) $order['customer_phone'],
                'email' => (string) ($order['customer_email'] ?? ''),
            ]
        );

        if (!$result->success || $result->reference === null || $result->redirectUrl === null) {
            Payment::markFailed($paymentId, $result->message);
            Logger::warning('Gateway init failed', ['order_id' => $orderId, 'gateway' => $gateway->name(), 'message' => $result->message]);

            throw new RuntimeException($result->message);
        }

        Payment::setAuthority($paymentId, $result->reference);
        Order::updateStatus($orderId, 'awaiting_payment');

        return ['redirect' => $result->redirectUrl, 'payment_id' => $paymentId];
    }

    /**
     * @param array<string, mixed> $callback
     * @return array{status:string, message:string, order?:array<string,mixed>, payment?:array<string,mixed>}
     */
    public function handleCallback(string $gatewayName, array $callback): array
    {
        try {
            $gateway = $this->gatewayByName($gatewayName);
        } catch (Throwable) {
            return ['status' => 'failed', 'message' => 'درگاه پرداخت نامعتبر است.'];
        }

        $reference = $gateway->gatewayReference($callback);

        if ($reference === null) {
            return ['status' => 'failed', 'message' => 'شناسه پرداخت در پاسخ درگاه یافت نشد.'];
        }

        $payment = Payment::findByAuthority($reference);

        if ($payment === null) {
            Logger::warning('Payment callback with unknown authority', ['gateway' => $gatewayName]);

            return ['status' => 'failed', 'message' => 'پرداخت مورد نظر در سامانه ثبت نشده است.'];
        }

        if ((string) $payment['gateway'] !== $gatewayName) {
            return ['status' => 'failed', 'message' => 'درگاه پرداخت با تراکنش هم‌خوانی ندارد.'];
        }

        $order = Order::find((int) $payment['order_id']);

        if ($order === null) {
            return ['status' => 'failed', 'message' => 'سفارش مربوط به این پرداخت یافت نشد.'];
        }

        if ((string) $payment['status'] === 'paid') {
            if (!in_array((string) $order['status'], Order::PAID_STATUSES, true)) {
                $status = (new OrderService())->completePayment(
                    $order,
                    $payment,
                    GatewayResult::success((string) ($payment['ref_id'] ?? $reference), $payment['card_pan'] ?? null)
                );

                return [
                    'status' => 'paid',
                    'message' => 'پرداخت شما قبلاً تأیید شده بود و سفارش تکمیل شد.',
                    'order' => Order::find((int) $order['id']),
                    'payment' => Payment::find((int) $payment['id']),
                    'order_status' => $status,
                ];
            }

            return [
                'status' => 'paid',
                'message' => 'این پرداخت قبلاً تأیید شده است.',
                'order' => $order,
                'payment' => $payment,
            ];
        }

        $result = $gateway->verify($callback, (int) $payment['amount'], (string) $order['tracking_code']);

        if ($result->success) {
            $status = (new OrderService())->completePayment($order, $payment, $result);

            return [
                'status' => 'paid',
                'message' => $result->message,
                'order' => Order::find((int) $order['id']),
                'payment' => Payment::find((int) $payment['id']),
                'order_status' => $status,
            ];
        }

        if ($result->retryable) {
            // پاسخ مبهم یا خطای شبکه: وضعیت pending می‌ماند و Cron آن را بررسی می‌کند.
            return [
                'status' => 'pending',
                'message' => 'وضعیت پرداخت در حال بررسی است. اگر مبلغ کسر شده باشد، سفارش به‌صورت خودکار تکمیل می‌شود.',
                'order' => $order,
                'payment' => $payment,
            ];
        }

        (new OrderService())->failPayment($order, $payment, $result->message);

        return [
            'status' => 'failed',
            'message' => $result->message,
            'order' => Order::find((int) $order['id']),
            'payment' => Payment::find((int) $payment['id']),
        ];
    }

    /**
     * تلاش مجدد تأیید یک پرداخت pending (برای Cron).
     */
    public function reconcile(array $payment): GatewayResult
    {
        $order = Order::find((int) $payment['order_id']);

        if ($order === null) {
            return GatewayResult::failure('سفارش یافت نشد.');
        }

        try {
            $gateway = $this->gatewayByName((string) $payment['gateway']);
        } catch (Throwable) {
            return GatewayResult::failure('درگاه نامعتبر است.');
        }

        $reference = (string) ($payment['authority'] ?? '');

        if ($reference === '') {
            return GatewayResult::failure('شناسه پرداخت ذخیره نشده است.');
        }

        $callback = match ($gateway->name()) {
            'zarinpal' => ['Authority' => $reference, 'Status' => 'OK'],
            'idpay' => ['id' => $reference],
            'nextpay' => ['trans_id' => $reference],
            default => ['authority' => $reference, 'status' => 'ok'],
        };

        return $gateway->verify($callback, (int) $payment['amount'], (string) $order['tracking_code']);
    }
}
