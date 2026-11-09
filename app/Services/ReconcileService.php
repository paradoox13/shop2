<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Support\Logger;
use Throwable;

/**
 * بررسی دوره‌ای پرداخت‌های معلق (Callback غیر ۲۰۰، Timeout یا قطع شبکه).
 * هر ۱۰ دقیقه توسط cron/reconcile_payments.php اجرا می‌شود.
 */
final class ReconcileService
{
    /** @return array{checked:int, paid:int, failed:int, pending:int} */
    public function run(): array
    {
        $stats = ['checked' => 0, 'paid' => 0, 'failed' => 0, 'pending' => 0];
        $payments = Payment::pendingOlderThan(5, 25);
        $service = new PaymentService();
        $orders = new OrderService();

        foreach ($payments as $payment) {
            $stats['checked']++;

            try {
                $result = $service->reconcile($payment);
                $order = Order::find((int) $payment['order_id']);

                if ($order === null) {
                    continue;
                }

                if ($result->success) {
                    $orders->completePayment($order, $payment, $result);
                    $stats['paid']++;
                    continue;
                }

                $ageMinutes = (time() - strtotime((string) $payment['created_at'])) / 60;

                if (!$result->retryable && $ageMinutes > 30) {
                    $orders->failPayment($order, $payment, 'تأیید خودکار ناموفق: ' . $result->message);
                    $stats['failed']++;
                } elseif ($ageMinutes > 120) {
                    $orders->failPayment($order, $payment, 'پرداخت پس از ۲ ساعت تأیید نشد.');
                    $stats['failed']++;
                } else {
                    $stats['pending']++;
                }
            } catch (Throwable $e) {
                Logger::error('Payment reconciliation error', [
                    'payment_id' => (int) $payment['id'],
                    'error' => $e->getMessage(),
                ]);
                $stats['pending']++;
            }
        }

        return $stats;
    }
}
