<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivationCodePrice;
use App\Models\Modem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PhoneNumber;
use App\Models\Reservation;
use App\Services\Gateways\GatewayResult;
use App\Support\Audit;
use App\Support\Db;
use App\Support\Logger;
use App\Support\Mailer;
use RuntimeException;
use Throwable;

/**
 * چرخه حیات سفارش:
 *  - ساخت سفارش از سبد (قفل ردیف‌ها + رزرو ۱۵ دقیقه‌ای)
 *  - تکمیل اتمیک پس از تأیید درگاه (Idempotent)
 *  - شکست/لغو و آزادسازی رزرو
 *  - تلاش مجدد پرداخت
 */
final class OrderService
{
    /**
     * @param array<string, mixed> $customer
     * @return array{order_id:int, tracking_code:string, total:int}
     */
    public function createFromCart(array $customer, ?int $userId = null): array
    {
        $errors = CartService::validate();

        if ($errors !== []) {
            throw new RuntimeException(implode(' ', $errors));
        }

        $items = CartService::items();

        $result = Db::transaction(function () use ($items, $customer, $userId): array {
            $numberIds = array_map(static fn (array $row): int => (int) $row['id'], $items['numbers']);
            $modemIds = array_map(static fn (array $row): int => (int) $row['id'], $items['modems']);

            $lockedNumbers = [];
            if ($numberIds !== []) {
                $placeholders = implode(',', array_fill(0, count($numberIds), '?'));
                $rows = Db::all(
                    "SELECT p.*, o.name AS operator_name
                     FROM phone_numbers p INNER JOIN operators o ON o.id = p.operator_id
                     WHERE p.id IN ({$placeholders}) FOR UPDATE",
                    $numberIds
                );

                foreach ($rows as $row) {
                    $lockedNumbers[(int) $row['id']] = $row;
                }

                foreach ($numberIds as $id) {
                    if (!isset($lockedNumbers[$id]) || $lockedNumbers[$id]['status'] !== 'available') {
                        throw new RuntimeException('یکی از شماره‌های سبد دیگر در دسترس نیست. سبد را به‌روزرسانی کنید.');
                    }
                }
            }

            $lockedModems = [];
            if ($modemIds !== []) {
                $placeholders = implode(',', array_fill(0, count($modemIds), '?'));
                $rows = Db::all("SELECT * FROM modems WHERE id IN ({$placeholders}) FOR UPDATE", $modemIds);

                foreach ($rows as $row) {
                    $lockedModems[(int) $row['id']] = $row;
                }
            }

            $orderItems = [];
            $total = 0;
            $hasPhysical = false;
            $types = [];

            foreach ($items['numbers'] as $item) {
                $row = $lockedNumbers[(int) $item['id']];
                $price = (int) $row['price'];
                $total += $price;
                $types[] = 'number';
                $orderItems[] = [
                    'product_type' => 'number',
                    'product_id' => (int) $row['id'],
                    'price' => $price,
                    'qty' => 1,
                    'snapshot' => [
                        'number' => $row['number'],
                        'operator_id' => (int) $row['operator_id'],
                        'operator_name' => $row['operator_name'],
                        'reseller_id' => $row['reseller_id'] !== null ? (int) $row['reseller_id'] : null,
                        'birth_date' => $row['birth_date'],
                        'description' => $row['description'],
                    ],
                ];
            }

            foreach ($items['modems'] as $item) {
                $id = (int) $item['id'];
                $row = $lockedModems[$id] ?? null;

                if ($row === null || $row['status'] !== 'active') {
                    throw new RuntimeException('مودم انتخاب‌شده دیگر موجود نیست.');
                }

                $qty = max(1, (int) $item['qty']);
                if ((int) $row['stock'] < $qty) {
                    throw new RuntimeException('موجودی مودم ' . $row['brand'] . ' ' . $row['model'] . ' کافی نیست.');
                }

                $price = (int) $row['price'];
                $total += $price * $qty;
                $types[] = 'modem';
                $hasPhysical = true;
                $orderItems[] = [
                    'product_type' => 'modem',
                    'product_id' => $id,
                    'price' => $price,
                    'qty' => $qty,
                    'snapshot' => [
                        'id' => $id,
                        'brand' => $row['brand'],
                        'model' => $row['model'],
                        'image_path' => $row['image_path'],
                        'reseller_id' => $row['reseller_id'] !== null ? (int) $row['reseller_id'] : null,
                    ],
                ];
            }

            foreach ($items['activation'] as $item) {
                $operatorId = (int) $item['operator_id'];
                $qty = max(1, (int) $item['qty']);
                $price = ActivationCodePrice::price($operatorId);

                if ($price <= 0) {
                    throw new RuntimeException('قیمت کد فعال‌سازی ' . $item['operator_name'] . ' تنظیم نشده است.');
                }

                $total += $price * $qty;
                $types[] = 'activation_code';
                $hasPhysical = true;
                $orderItems[] = [
                    'product_type' => 'activation_code',
                    'product_id' => $operatorId,
                    'price' => $price,
                    'qty' => $qty,
                    'snapshot' => [
                        'operator_id' => $operatorId,
                        'operator_name' => $item['operator_name'],
                    ],
                ];
            }

            if ($orderItems === []) {
                throw new RuntimeException('سبد خرید خالی است.');
            }

            $uniqueTypes = array_values(array_unique($types));
            $type = count($uniqueTypes) === 1 ? $uniqueTypes[0] : 'mixed';

            $orderId = Order::create([
                'user_id' => $userId,
                'customer_name' => (string) $customer['customer_name'],
                'customer_phone' => (string) $customer['customer_phone'],
                'customer_email' => $customer['customer_email'] ?? null,
                'type' => $type,
                'total_price' => $total,
                'status' => 'pending',
            ]);

            $order = Order::find($orderId);

            OrderItem::createMany($orderId, $orderItems);

            foreach ($numberIds as $id) {
                Reservation::reserve($id, $orderId, (int) config('security.reservation_minutes', 15));
            }

            Audit::log('order.created', 'orders', (string) $orderId, [
                'total' => $total,
                'type' => $type,
                'physical' => $hasPhysical,
            ]);

            return [
                'order_id' => $orderId,
                'tracking_code' => (string) ($order['tracking_code'] ?? ''),
                'total' => $total,
            ];
        });

        CartService::clear();

        return $result;
    }

    /**
     * تکمیل سفارش پس از تأیید درگاه. کاملاً idempotent است.
     */
    public function completePayment(array $order, array $payment, GatewayResult $result): string
    {
        $wasAlreadyPaid = in_array((string) $order['status'], Order::PAID_STATUSES, true);

        $newStatus = Db::transaction(function () use ($order, $payment, $result): string {
            $lockedOrder = Order::findForUpdate((int) $order['id']);

            if ($lockedOrder === null) {
                throw new RuntimeException('سفارش یافت نشد.');
            }

            if (in_array((string) $lockedOrder['status'], Order::PAID_STATUSES, true)) {
                // ممکن است Callback تکراری باشد؛ پرداخت را فقط یک‌بار paid کنید.
                Payment::claimPaid((int) $payment['id'], (string) ($result->reference ?? ''), $result->cardPan);

                return (string) $lockedOrder['status'];
            }

            Payment::claimPaid((int) $payment['id'], (string) ($result->reference ?? ''), $result->cardPan);

            $items = OrderItem::byOrder((int) $lockedOrder['id']);
            $issues = [];
            $isPhysical = false;

            foreach ($items as $item) {
                $snapshot = $item['snapshot_array'];
                $productId = (int) ($item['product_id'] ?? 0);
                $qty = max(1, (int) $item['qty']);

                if ($item['product_type'] === 'number') {
                    if (!PhoneNumber::markSold($productId)) {
                        $issues[] = 'شماره ' . ($snapshot['number'] ?? $productId) . ' در زمان تأیید پرداخت در دسترس نبود.';
                        Logger::error('Paid order could not mark number sold', [
                            'order_id' => (int) $lockedOrder['id'],
                            'number_id' => $productId,
                        ]);
                    }
                } elseif ($item['product_type'] === 'modem') {
                    $isPhysical = true;

                    if (!Modem::decrementStock($productId, $qty)) {
                        $issues[] = 'موجودی مودم ' . ($snapshot['brand'] ?? '') . ' ' . ($snapshot['model'] ?? '') . ' در زمان تأیید پرداخت کافی نبود.';
                        Logger::error('Paid order could not decrement modem stock', [
                            'order_id' => (int) $lockedOrder['id'],
                            'modem_id' => $productId,
                            'qty' => $qty,
                        ]);
                    }
                } elseif ($item['product_type'] === 'activation_code') {
                    $isPhysical = true;
                }
            }

            Reservation::releaseForOrder((int) $lockedOrder['id']);

            $status = $isPhysical ? 'pending_physical_send' : 'paid';
            $note = $issues !== [] ? 'نیازمند بررسی: ' . implode(' | ', $issues) : null;
            Order::updateStatus((int) $lockedOrder['id'], $status, $note);

            Audit::log('order.paid', 'orders', (string) $lockedOrder['id'], [
                'amount' => (int) $lockedOrder['total_price'],
                'gateway' => (string) $payment['gateway'],
                'reference' => $result->reference,
                'issues' => $issues,
            ]);

            return $status;
        });

        if (!$wasAlreadyPaid) {
            $this->notify((int) $order['id']);
        }

        return $newStatus;
    }

    public function failPayment(array $order, array $payment, string $message): void
    {
        Db::transaction(function () use ($order, $payment, $message): void {
            $lockedOrder = Order::findForUpdate((int) $order['id']);

            if ($lockedOrder === null || in_array((string) $lockedOrder['status'], Order::PAID_STATUSES, true)) {
                return;
            }

            Payment::markFailed((int) $payment['id'], $message);

            if ((string) $lockedOrder['status'] !== 'canceled') {
                Reservation::releaseForOrder((int) $lockedOrder['id']);
                Order::updateStatus((int) $lockedOrder['id'], 'failed', 'پرداخت ناموفق: ' . mb_substr($message, 0, 300));
            }

            Audit::log('order.payment_failed', 'orders', (string) $lockedOrder['id'], [
                'message' => mb_substr($message, 0, 300),
            ]);
        });

        Logger::warning('Order payment failed', ['order_id' => (int) $order['id'], 'message' => $message]);

        try {
            $messageText = 'آوال سیم' . "\n" . 'پرداخت سفارش ' . $order['tracking_code'] . ' ناموفق بود.';
            (new SmsService())->queue((string) $order['customer_phone'], 'payment_failed', $messageText, (int) $order['id']);
        } catch (Throwable $e) {
            Logger::warning('Failure SMS could not be queued', ['error' => $e->getMessage()]);
        }
    }

    /**
     * آماده‌سازی سفارش ناموفق برای تلاش مجدد (رزرو مجدد شماره‌ها).
     * @return array<string, mixed> سفارش به‌روزشده
     */
    public function prepareRetry(int $orderId): array
    {
        return Db::transaction(function () use ($orderId): array {
            $order = Order::findForUpdate($orderId);

            if ($order === null) {
                throw new RuntimeException('سفارش یافت نشد.');
            }

            if (in_array((string) $order['status'], Order::PAID_STATUSES, true)) {
                throw new RuntimeException('این سفارش قبلاً پرداخت شده است.');
            }

            $items = OrderItem::byOrder($orderId);
            $hasPhysical = false;

            foreach ($items as $item) {
                $qty = max(1, (int) $item['qty']);
                $productId = (int) ($item['product_id'] ?? 0);

                if ($item['product_type'] === 'number') {
                    $number = PhoneNumber::find($productId);

                    if ($number === null || $number['status'] !== 'available') {
                        throw new RuntimeException('شماره ' . ($item['snapshot_array']['number'] ?? '') . ' دیگر در دسترس نیست؛ سفارش جدید ثبت کنید.');
                    }

                    Reservation::reserve($productId, $orderId, (int) config('security.reservation_minutes', 15));
                } elseif ($item['product_type'] === 'modem') {
                    $hasPhysical = true;
                    $modem = Modem::find($productId);

                    if ($modem === null || $modem['status'] !== 'active' || (int) $modem['stock'] < $qty) {
                        throw new RuntimeException('موجودی مودم انتخابی کافی نیست؛ لطفاً سفارش جدید ثبت کنید.');
                    }
                } elseif ($item['product_type'] === 'activation_code') {
                    $hasPhysical = true;
                }
            }

            Order::updateStatus($orderId, 'pending', $hasPhysical ? 'در انتظار پرداخت مجدد (محصول فیزیکی)' : 'در انتظار پرداخت مجدد');

            return Order::find($orderId) ?? $order;
        });
    }

    private function notify(int $orderId): void
    {
        try {
            $order = Order::find($orderId);

            if ($order === null) {
                return;
            }

            $items = OrderItem::byOrder($orderId);
            $labels = [];
            $hasPhysical = false;

            foreach ($items as $item) {
                $snapshot = $item['snapshot_array'];

                if ($item['product_type'] === 'number') {
                    $labels[] = 'شماره ' . fa_num((string) ($snapshot['number'] ?? ''));
                } elseif ($item['product_type'] === 'modem') {
                    $labels[] = 'مودم ' . ($snapshot['brand'] ?? '') . ' ' . ($snapshot['model'] ?? '') . ' ×' . fa_num((string) $item['qty']);
                    $hasPhysical = true;
                } elseif ($item['product_type'] === 'activation_code') {
                    $labels[] = 'کد فعال‌سازی ' . ($snapshot['operator_name'] ?? '') . ' ×' . fa_num((string) $item['qty']);
                    $hasPhysical = true;
                }
            }

            $sms = new SmsService();
            $customerMessage = 'آوال سیم' . "\n"
                . 'سفارش ' . $order['tracking_code'] . ' با موفقیت پرداخت شد.' . "\n"
                . 'مبلغ: ' . money($order['total_price']) . "\n"
                . truncate(implode('، ', $labels), 80);

            $sms->queue((string) $order['customer_phone'], 'payment_success', $customerMessage, $orderId);

            $adminPhone = setting('notify_phone') !== '' ? setting('notify_phone') : (string) config('sms.admin_phone', '');

            if ($adminPhone !== '') {
                $adminMessage = 'آوال سیم | سفارش پرداخت‌شده' . "\n"
                    . 'کد: ' . $order['tracking_code'] . "\n"
                    . 'مبلغ: ' . money($order['total_price']) . "\n"
                    . 'مشتری: ' . truncate((string) $order['customer_name'], 30) . ' - ' . $order['customer_phone'] . "\n"
                    . ($hasPhysical ? 'نیازمند ارسال فیزیکی: ' . truncate(implode('، ', $labels), 70) : 'تحویل دیجیتال');

                $sms->queue($adminPhone, 'admin_paid_order', $adminMessage, $orderId);
            }

            if (!empty($order['customer_email'])) {
                Mailer::send(
                    (string) $order['customer_email'],
                    'پرداخت سفارش ' . $order['tracking_code'] . ' تأیید شد',
                    "سفارش شما با موفقیت پرداخت شد.\n\nکد رهگیری: " . $order['tracking_code']
                    . "\nمبلغ: " . money($order['total_price'])
                    . "\n\n" . implode("\n", $labels)
                    . "\n\nآوال سیم"
                );
            }

            if ($hasPhysical) {
                $adminEmail = (string) env('ADMIN_EMAIL', '');
                if ($adminEmail !== '') {
                    Mailer::send(
                        $adminEmail,
                        'سفارش فیزیکی جدید: ' . $order['tracking_code'],
                        "سفارش فیزیکی پرداخت شد و نیازمند ارسال است.\n\n"
                        . "کد رهگیری: " . $order['tracking_code'] . "\n"
                        . "مشتری: " . $order['customer_name'] . ' - ' . $order['customer_phone'] . "\n"
                        . "اقلام:\n" . implode("\n", $labels)
                    );
                }
            }
        } catch (Throwable $e) {
            Logger::error('Order notifications failed', ['order_id' => $orderId, 'error' => $e->getMessage()]);
        }
    }
}
