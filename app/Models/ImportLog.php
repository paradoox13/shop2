<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Db;

final class ImportLog
{
    /**
     * @param array<int, string> $headers
     */
    public static function create(int $resellerId, string $type, string $filePath, array $headers, int $totalRows): int
    {
        return Db::insert('import_logs', [
            'reseller_id' => $resellerId,
            'type' => $type,
            'file_path' => $filePath,
            'total_rows' => max(0, $totalRows),
            'processed_rows' => 0,
            'inserted_rows' => 0,
            'failed_rows' => 0,
            'cursor_offset' => 0,
            'headers' => json_encode(array_values($headers), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
            'error_log' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id, ?int $resellerId = null): ?array
    {
        $sql = 'SELECT * FROM import_logs WHERE id = ?';
        $params = [$id];

        if ($resellerId !== null) {
            $sql .= ' AND reseller_id = ?';
            $params[] = $resellerId;
        }

        return Db::one($sql . ' LIMIT 1', $params);
    }

    public static function updateProgress(
        int $id,
        int $processedRows,
        int $insertedRows,
        int $failedRows,
        int $cursorOffset,
        string $status,
        ?string $errorLog = null,
    ): void {
        Db::update('import_logs', [
            'processed_rows' => $processedRows,
            'inserted_rows' => $insertedRows,
            'failed_rows' => $failedRows,
            'cursor_offset' => $cursorOffset,
            'status' => $status,
            'error_log' => $errorLog,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    public static function markFailed(int $id, string $errorLog): void
    {
        Db::update('import_logs', [
            'status' => 'failed',
            'error_log' => mb_substr($errorLog, 0, 60000),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    /**
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, pages:int, per_page:int}
     */
    public static function forReseller(int $resellerId, int $page = 1, int $perPage = 15): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $total = (int) Db::scalar('SELECT COUNT(*) FROM import_logs WHERE reseller_id = ?', [$resellerId], 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $items = Db::all(
            'SELECT * FROM import_logs WHERE reseller_id = ? ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            [$resellerId]
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** @return array<int, array<string, mixed>> */
    public static function pendingForWorker(int $limit = 5): array
    {
        return Db::all(
            "SELECT * FROM import_logs
             WHERE status IN ('pending', 'processing') AND updated_at <= (NOW() - INTERVAL 30 SECOND)
             ORDER BY id ASC LIMIT " . max(1, min(20, $limit))
        );
    }

    public static function resetStuck(int $minutes = 10): int
    {
        return Db::query(
            "UPDATE import_logs SET status = 'pending', updated_at = NOW()
             WHERE status = 'processing' AND updated_at < (NOW() - INTERVAL ? MINUTE)",
            [$minutes]
        )->rowCount();
    }

    public static function delete(int $id): void
    {
        Db::delete('import_logs', 'id = ?', [$id]);
    }
}
