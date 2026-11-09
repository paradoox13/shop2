<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

final class Audit
{
    /** @param array<string, mixed> $meta */
    public static function log(string $action, ?string $entity = null, ?string $entityId = null, array $meta = []): void
    {
        try {
            Db::insert('audit_logs', [
                'user_id' => Auth::id(),
                'action' => mb_substr($action, 0, 80),
                'entity' => $entity !== null ? mb_substr($entity, 0, 60) : null,
                'entity_id' => $entityId !== null ? mb_substr($entityId, 0, 40) : null,
                'meta' => $meta !== [] ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            Logger::warning('Audit log failed', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public static function recent(int $limit = 100, ?string $action = null): array
    {
        $sql = 'SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id';
        $params = [];

        if ($action !== null && $action !== '') {
            $sql .= ' WHERE a.action LIKE ?';
            $params[] = $action . '%';
        }

        $sql .= ' ORDER BY a.id DESC LIMIT ' . max(1, min($limit, 500));

        return Db::all($sql, $params);
    }
}
