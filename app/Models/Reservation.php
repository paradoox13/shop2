<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Db;
use App\Support\Str;
use RuntimeException;

final class Reservation
{
    /**
     * رزرو شماره برای یک سفارش. باید درون تراکنش سفارش فراخوانی شود.
     */
    public static function reserve(int $phoneNumberId, int $orderId, int $minutes = 15): string
    {
        $affected = Db::query(
            "UPDATE phone_numbers SET status = 'reserved', updated_at = NOW()
             WHERE id = ? AND status = 'available'",
            [$phoneNumberId]
        )->rowCount();

        if ($affected !== 1) {
            throw new RuntimeException('شماره انتخاب‌شده دیگر در دسترس نیست.');
        }

        $token = bin2hex(random_bytes(24));
        $expiresAt = date('Y-m-d H:i:s', time() + max(1, $minutes) * 60);

        Db::insert('reservations', [
            'phone_number_id' => $phoneNumberId,
            'order_id' => $orderId,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    /** @return array<int, array<string, mixed>> */
    public static function forOrder(int $orderId): array
    {
        return Db::all('SELECT * FROM reservations WHERE order_id = ?', [$orderId]);
    }

    public static function releaseForOrder(int $orderId): int
    {
        $numberIds = array_map(
            static fn (array $row): int => (int) $row['phone_number_id'],
            self::forOrder($orderId)
        );

        Db::delete('reservations', 'order_id = ?', [$orderId]);
        self::releaseOrphanReservations($numberIds);

        return count($numberIds);
    }

    /**
     * آزادسازی شماره‌هایی که رزرو فعال ندارند (Cron هر ۵ دقیقه).
     */
    public static function expireStale(int $limit = 500): int
    {
        $expired = Db::all(
            'SELECT r.id, r.phone_number_id FROM reservations r
             WHERE r.expires_at <= NOW() ORDER BY r.id ASC LIMIT ?',
            [max(1, min(1000, $limit))]
        );

        if ($expired === []) {
            return 0;
        }

        $numberIds = array_map(static fn (array $row): int => (int) $row['phone_number_id'], $expired);

        Db::transaction(static function () use ($numberIds): void {
            Db::delete('reservations', 'expires_at <= NOW()');
            self::releaseOrphanReservations($numberIds);
        });

        return count($numberIds);
    }

    public static function activeCount(?int $orderId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM reservations WHERE expires_at > NOW()';
        $params = [];

        if ($orderId !== null) {
            $sql .= ' AND order_id = ?';
            $params[] = $orderId;
        }

        return (int) Db::scalar($sql, $params, 0);
    }

    public static function cleanupReleased(int $days = 7): int
    {
        return Db::delete('reservations', 'expires_at < (NOW() - INTERVAL ? DAY)', [$days]);
    }

    /**
     * @param array<int, int> $numberIds
     */
    private static function releaseOrphanReservations(array $numberIds): void
    {
        if ($numberIds === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($numberIds), '?'));

        Db::query(
            "UPDATE phone_numbers p SET p.status = 'available', p.updated_at = NOW()
             WHERE p.status = 'reserved' AND p.id IN ({$placeholders})
               AND NOT EXISTS (
                   SELECT 1 FROM reservations r
                   WHERE r.phone_number_id = p.id AND r.expires_at > NOW()
               )",
            $numberIds
        );
    }

    public static function generateToken(): string
    {
        return Str::random(24);
    }
}
