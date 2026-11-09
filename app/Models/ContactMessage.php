<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Db;

final class ContactMessage
{
    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        return Db::insert('contact_messages', [
            'name' => mb_substr(trim((string) $data['name']), 0, 120),
            'phone' => isset($data['phone']) ? mb_substr(trim((string) $data['phone']), 0, 20) : null,
            'email' => isset($data['email']) ? mb_substr(trim((string) $data['email']), 0, 190) : null,
            'subject' => mb_substr(trim((string) $data['subject']), 0, 150),
            'message' => trim((string) $data['message']),
            'ip' => $data['ip'] ?? null,
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, pages:int, per_page:int}
     */
    public static function paginate(int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $total = (int) Db::scalar('SELECT COUNT(*) FROM contact_messages', [], 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $items = Db::all('SELECT * FROM contact_messages ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    public static function markRead(int $id): void
    {
        Db::update('contact_messages', ['status' => 'read'], 'id = ?', [$id]);
    }

    public static function countNew(): int
    {
        return (int) Db::scalar("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'", [], 0);
    }
}
