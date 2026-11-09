<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Db;

final class Payment
{
    public static function create(int $orderId, string $gateway, int $amount, string $status = 'pending'): int
    {
        return Db::insert('payments', [
            'order_id' => $orderId,
            'gateway' => $gateway,
            'amount' => max(0, $amount),
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function setAuthority(int $id, string $authority): void
    {
        Db::update('payments', ['authority' => $authority, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM payments WHERE id = ?', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByAuthority(string $authority): ?array
    {
        return Db::one('SELECT * FROM payments WHERE authority = ? LIMIT 1', [$authority]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function byOrder(int $orderId): array
    {
        return Db::all('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC', [$orderId]);
    }

    /**
     * تغییر اتمیک به paid. اگر قبلاً paid شده باشد false برمی‌گرداند (Idempotency).
     * @param array<string, mixed> $extra
     */
    public static function claimPaid(int $id, string $refId, ?string $cardPan = null, array $extra = []): bool
    {
        $affected = Db::query(
            "UPDATE payments SET status = 'paid', ref_id = ?, card_pan = ?, error_message = NULL, paid_at = NOW(), updated_at = NOW()
             WHERE id = ? AND status <> 'paid'",
            [$refId, $cardPan, $id]
        )->rowCount();

        if ($affected === 1 && $extra !== []) {
            // فیلدهای اضافی در آینده (در حال حاضر فقط ref_id/pan ذخیره می‌شوند)
            unset($extra);
        }

        return $affected === 1;
    }

    public static function markFailed(int $id, string $message, string $status = 'failed'): void
    {
        if (!in_array($status, ['failed', 'canceled'], true)) {
            $status = 'failed';
        }

        Db::query(
            'UPDATE payments SET status = ?, error_message = ?, updated_at = NOW() WHERE id = ? AND status <> ?',
            [$status, mb_substr($message, 0, 500), $id, 'paid']
        );
    }

    public static function isPaid(int $id): bool
    {
        return Db::scalar('SELECT status FROM payments WHERE id = ?', [$id]) === 'paid';
    }

    /** @return array<int, array<string, mixed>> */
    public static function pendingOlderThan(int $minutes, int $limit = 20): array
    {
        return Db::all(
            "SELECT pay.*, o.tracking_code, o.status AS order_status
             FROM payments pay INNER JOIN orders o ON o.id = pay.order_id
             WHERE pay.status = 'pending' AND pay.amount > 0
               AND pay.created_at <= (NOW() - INTERVAL ? MINUTE)
               AND o.status IN ('pending', 'awaiting_payment')
             ORDER BY pay.id ASC LIMIT " . max(1, min(100, $limit)),
            [max(1, $minutes)]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public static function latest(int $limit = 10): array
    {
        return Db::all(
            'SELECT pay.*, o.tracking_code FROM payments pay
             INNER JOIN orders o ON o.id = pay.order_id ORDER BY pay.id DESC LIMIT ' . max(1, min(50, $limit))
        );
    }

    /** @return array<string, int> */
    public static function stats(): array
    {
        $rows = Db::all('SELECT status, COUNT(*) AS c, COALESCE(SUM(amount), 0) AS total FROM payments GROUP BY status');
        $stats = [];

        foreach ($rows as $row) {
            $stats[(string) $row['status']] = (int) $row['c'];
            $stats[(string) $row['status'] . '_amount'] = (int) $row['total'];
        }

        return $stats;
    }
}
