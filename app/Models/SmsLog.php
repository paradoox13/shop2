<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Db;

final class SmsLog
{
    public static function queue(string $phone, string $template, string $message, ?int $orderId = null): int
    {
        return Db::insert('sms_logs', [
            'phone' => mb_substr($phone, 0, 20),
            'template' => mb_substr($template, 0, 60),
            'message' => $message,
            'status' => 'queued',
            'attempts' => 0,
            'order_id' => $orderId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM sms_logs WHERE id = ?', [$id]);
    }

    public static function markSent(int $id, string $provider, string $response): void
    {
        Db::query(
            "UPDATE sms_logs SET status = 'sent', provider = ?, provider_response = ?, sent_at = NOW(), attempts = attempts + 1
             WHERE id = ?",
            [mb_substr($provider, 0, 30), mb_substr($response, 0, 5000), $id]
        );
    }

    public static function markFailed(int $id, string $response): void
    {
        Db::query(
            "UPDATE sms_logs SET status = 'failed', provider_response = ?, attempts = attempts + 1 WHERE id = ?",
            [mb_substr($response, 0, 5000), $id]
        );
    }

    public static function resetForRetry(int $id): void
    {
        Db::query(
            "UPDATE sms_logs SET status = 'queued', provider_response = NULL, sent_at = NULL WHERE id = ? AND status IN ('failed', 'queued')",
            [$id]
        );
    }

    /**
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, pages:int, per_page:int}
     */
    public static function paginate(?string $status = null, ?string $search = null, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $where = ['1 = 1'];
        $params = [];

        if ($status !== null && in_array($status, ['queued', 'sent', 'failed'], true)) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        if ($search !== null && trim($search) !== '') {
            $like = '%' . trim($search) . '%';
            $where[] = '(phone LIKE ? OR message LIKE ? OR template LIKE ?)';
            $params = array_merge($params, [$like, $like, $like]);
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) Db::scalar('SELECT COUNT(*) FROM sms_logs WHERE ' . $whereSql, $params, 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $items = Db::all(
            'SELECT * FROM sms_logs WHERE ' . $whereSql . ' ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** @return array<string, int> */
    public static function stats(): array
    {
        $rows = Db::all('SELECT status, COUNT(*) AS c FROM sms_logs GROUP BY status');
        $stats = ['queued' => 0, 'sent' => 0, 'failed' => 0];

        foreach ($rows as $row) {
            $stats[(string) $row['status']] = (int) $row['c'];
        }

        return $stats;
    }
}
