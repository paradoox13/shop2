<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Db;
use App\Support\Jalali;
use App\Support\Str;

final class Order
{
    public const PAID_STATUSES = ['paid', 'pending_physical_send', 'sent', 'completed'];

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Db::insert('orders', [
            'user_id' => isset($data['user_id']) && (int) $data['user_id'] > 0 ? (int) $data['user_id'] : null,
            'customer_name' => mb_substr(trim((string) ($data['customer_name'] ?? '')), 0, 120),
            'customer_phone' => mb_substr(trim((string) ($data['customer_phone'] ?? '')), 0, 20),
            'customer_email' => isset($data['customer_email']) && trim((string) $data['customer_email']) !== ''
                ? mb_substr(trim((string) $data['customer_email']), 0, 190)
                : null,
            'type' => (string) ($data['type'] ?? 'mixed'),
            'total_price' => max(0, (int) ($data['total_price'] ?? 0)),
            'status' => (string) ($data['status'] ?? 'pending'),
            'tracking_code' => (string) ($data['tracking_code'] ?? self::generateTrackingCode()),
            'admin_note' => $data['admin_note'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function generateTrackingCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = date('Ymd') . '-' . strtoupper(Str::token(6));
            $exists = (int) Db::scalar('SELECT COUNT(*) FROM orders WHERE tracking_code = ?', [$code], 0);

            if ($exists === 0) {
                return $code;
            }
        }

        return date('Ymd') . '-' . strtoupper(Str::token(8));
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM orders WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findForUpdate(int $id): ?array
    {
        return Db::one('SELECT * FROM orders WHERE id = ? LIMIT 1 FOR UPDATE', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByTracking(string $trackingCode): ?array
    {
        return Db::one('SELECT * FROM orders WHERE tracking_code = ? LIMIT 1', [$trackingCode]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function items(int $orderId): array
    {
        return \App\Models\OrderItem::byOrder($orderId);
    }

    public static function updateStatus(int $id, string $status, ?string $note = null): void
    {
        $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];

        if ($note !== null) {
            $data['admin_note'] = $note;
        }

        Db::update('orders', $data, 'id = ?', [$id]);
    }

    public static function updateNote(int $id, ?string $note): void
    {
        Db::update('orders', ['admin_note' => $note, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, pages:int, per_page:int}
     */
    public static function adminSearch(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $where = ['1 = 1'];
        $params = [];

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(tracking_code LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ? OR customer_email LIKE ?)';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '' && preg_match('/^[a-z_]+$/', $status) === 1) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        $type = trim((string) ($filters['type'] ?? ''));
        if (in_array($type, ['number', 'modem', 'activation_code', 'mixed'], true)) {
            $where[] = 'type = ?';
            $params[] = $type;
        }

        $from = Jalali::parse((string) ($filters['from'] ?? ''));
        if ($from !== null) {
            $where[] = 'created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }

        $to = Jalali::parse((string) ($filters['to'] ?? ''));
        if ($to !== null) {
            $where[] = 'created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) Db::scalar('SELECT COUNT(*) FROM orders WHERE ' . $whereSql, $params, 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $items = Db::all(
            'SELECT * FROM orders WHERE ' . $whereSql . ' ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** @return array<string, mixed> */
    public static function stats(): array
    {
        $paidPlaceholders = implode(',', array_fill(0, count(self::PAID_STATUSES), '?'));

        $total = Db::one(
            "SELECT COUNT(*) AS orders_count, COALESCE(SUM(total_price), 0) AS total_sales
             FROM orders WHERE status IN ({$paidPlaceholders})",
            self::PAID_STATUSES
        ) ?? ['orders_count' => 0, 'total_sales' => 0];

        $today = Db::one(
            "SELECT COUNT(*) AS orders_count, COALESCE(SUM(total_price), 0) AS total_sales
             FROM orders WHERE status IN ({$paidPlaceholders}) AND DATE(created_at) = CURDATE()",
            self::PAID_STATUSES
        ) ?? ['orders_count' => 0, 'total_sales' => 0];

        $month = Db::one(
            "SELECT COUNT(*) AS orders_count, COALESCE(SUM(total_price), 0) AS total_sales
             FROM orders WHERE status IN ({$paidPlaceholders}) AND created_at >= (NOW() - INTERVAL 30 DAY)",
            self::PAID_STATUSES
        ) ?? ['orders_count' => 0, 'total_sales' => 0];

        $byStatus = Db::all('SELECT status, COUNT(*) AS c FROM orders GROUP BY status');
        $statuses = [];

        foreach ($byStatus as $row) {
            $statuses[(string) $row['status']] = (int) $row['c'];
        }

        return [
            'orders_count' => (int) $total['orders_count'],
            'total_sales' => (int) $total['total_sales'],
            'today_orders' => (int) $today['orders_count'],
            'today_sales' => (int) $today['total_sales'],
            'month_orders' => (int) $month['orders_count'],
            'month_sales' => (int) $month['total_sales'],
            'by_status' => $statuses,
            'pending_orders' => ($statuses['pending'] ?? 0) + ($statuses['awaiting_payment'] ?? 0),
            'physical_pending' => $statuses['pending_physical_send'] ?? 0,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function recent(int $limit = 8): array
    {
        return Db::all('SELECT * FROM orders ORDER BY id DESC LIMIT ' . max(1, min(50, $limit)));
    }

    /** @return array<int, array{label:string, total:int, count:int}> */
    public static function salesByDay(int $days = 14): array
    {
        $days = max(3, min(60, $days));
        $paidPlaceholders = implode(',', array_fill(0, count(self::PAID_STATUSES), '?'));
        $params = self::PAID_STATUSES;
        $params[] = $days;

        $rows = Db::all(
            "SELECT DATE(created_at) AS day, COUNT(*) AS c, COALESCE(SUM(total_price), 0) AS total
             FROM orders
             WHERE status IN ({$paidPlaceholders}) AND created_at >= (CURDATE() - INTERVAL ? DAY)
             GROUP BY DATE(created_at) ORDER BY day ASC",
            $params
        );

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[(string) $row['day']] = ['total' => (int) $row['total'], 'count' => (int) $row['c']];
        }

        $result = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime('-' . $i . ' days'));
            $result[] = [
                'label' => Jalali::format($date, 'm/d'),
                'total' => $byDay[$date]['total'] ?? 0,
                'count' => $byDay[$date]['count'] ?? 0,
            ];
        }

        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    public static function forExport(array $filters = []): array
    {
        $where = ['1 = 1'];
        $params = [];

        $from = Jalali::parse((string) ($filters['from'] ?? ''));
        if ($from !== null) {
            $where[] = 'created_at >= ?';
            $params[] = $from . ' 00:00:00';
        }

        $to = Jalali::parse((string) ($filters['to'] ?? ''));
        if ($to !== null) {
            $where[] = 'created_at <= ?';
            $params[] = $to . ' 23:59:59';
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '' && preg_match('/^[a-z_]+$/', $status) === 1) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        return Db::all(
            'SELECT id, tracking_code, customer_name, customer_phone, customer_email, type, total_price, status, created_at
             FROM orders WHERE ' . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT 5000',
            $params
        );
    }

    public static function countByStatus(string $status): int
    {
        return (int) Db::scalar('SELECT COUNT(*) FROM orders WHERE status = ?', [$status], 0);
    }
}
