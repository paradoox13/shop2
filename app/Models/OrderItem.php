<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Db;
use App\Support\Jalali;

final class OrderItem
{
    /**
     * @param array<int, array{product_type:string, product_id:?int, snapshot:array<string,mixed>, price:int, qty:int}> $items
     */
    public static function createMany(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            Db::insert('order_items', [
                'order_id' => $orderId,
                'product_type' => $item['product_type'],
                'product_id' => $item['product_id'],
                'snapshot' => json_encode($item['snapshot'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'price' => max(0, (int) $item['price']),
                'qty' => max(1, (int) $item['qty']),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function byOrder(int $orderId): array
    {
        $rows = Db::all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC', [$orderId]);

        foreach ($rows as &$row) {
            $row['snapshot_array'] = self::decodeSnapshot((string) $row['snapshot']);
        }
        unset($row);

        return $rows;
    }

    /**
     * فروش‌های مربوط به محصولات یک نماینده.
     *
     * @param array<string, mixed> $filters
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, pages:int, per_page:int, revenue:int}
     */
    public static function salesForReseller(int $resellerId, array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $paid = implode(',', array_fill(0, count(Order::PAID_STATUSES), '?'));
        $where = [
            "o.status IN ({$paid})",
            "CAST(JSON_UNQUOTE(JSON_EXTRACT(oi.snapshot, '$.reseller_id')) AS UNSIGNED) = ?",
        ];
        $params = array_merge(Order::PAID_STATUSES, [$resellerId]);

        $from = Jalali::parse((string) ($filters['from'] ?? ''));
        if ($from !== null) {
            $where[] = 'o.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }

        $to = Jalali::parse((string) ($filters['to'] ?? ''));
        if ($to !== null) {
            $where[] = 'o.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }

        $type = (string) ($filters['type'] ?? '');
        if (in_array($type, ['number', 'modem'], true)) {
            $where[] = 'oi.product_type = ?';
            $params[] = $type;
        }

        $whereSql = implode(' AND ', $where);
        $base = " FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id WHERE {$whereSql}";

        $total = (int) Db::scalar('SELECT COUNT(*)' . $base, $params, 0);
        $revenue = (int) Db::scalar('SELECT COALESCE(SUM(oi.price * oi.qty), 0)' . $base, $params, 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $rows = Db::all(
            'SELECT oi.*, o.tracking_code, o.customer_name, o.customer_phone, o.status AS order_status, o.created_at AS order_created_at'
            . $base . ' ORDER BY oi.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        foreach ($rows as &$row) {
            $row['snapshot_array'] = self::decodeSnapshot((string) $row['snapshot']);
        }
        unset($row);

        return ['items' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage, 'revenue' => $revenue];
    }

    /** @return array<int, array<string, mixed>> */
    public static function allSalesForReseller(int $resellerId, array $filters = []): array
    {
        $paid = implode(',', array_fill(0, count(Order::PAID_STATUSES), '?'));
        $params = array_merge(Order::PAID_STATUSES, [$resellerId]);
        $where = [
            "o.status IN ({$paid})",
            "CAST(JSON_UNQUOTE(JSON_EXTRACT(oi.snapshot, '$.reseller_id')) AS UNSIGNED) = ?",
        ];

        $from = Jalali::parse((string) ($filters['from'] ?? ''));
        if ($from !== null) {
            $where[] = 'o.created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }

        $to = Jalali::parse((string) ($filters['to'] ?? ''));
        if ($to !== null) {
            $where[] = 'o.created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }

        $rows = Db::all(
            'SELECT oi.*, o.tracking_code, o.customer_name, o.customer_phone, o.status AS order_status, o.created_at AS order_created_at
             FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY oi.id DESC LIMIT 5000',
            $params
        );

        foreach ($rows as &$row) {
            $row['snapshot_array'] = self::decodeSnapshot((string) $row['snapshot']);
        }
        unset($row);

        return $rows;
    }

    /** @return array<string, mixed> */
    public static function decodeSnapshot(string $json): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
