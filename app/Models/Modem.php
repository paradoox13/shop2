<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Db;
use PDOException;
use RuntimeException;

final class Modem
{
    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::one(
            'SELECT m.*, COALESCE(rp.display_name, u.username) AS reseller_name
             FROM modems m
             LEFT JOIN users u ON u.id = m.reseller_id
             LEFT JOIN reseller_profiles rp ON rp.user_id = m.reseller_id
             WHERE m.id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, pages:int, per_page:int}
     */
    public static function search(
        array $filters,
        int $page = 1,
        int $perPage = 12,
        ?int $resellerId = null,
        bool $publicOnly = false,
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $where = ['1 = 1'];
        $params = [];

        if ($publicOnly) {
            $where[] = "m.status = 'active'";
            $where[] = 'm.stock > 0';
        }

        if ($resellerId !== null) {
            $where[] = 'm.reseller_id = ?';
            $params[] = $resellerId;
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(m.brand LIKE ? OR m.model LIKE ? OR m.description LIKE ?)';
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like, $like]);
        }

        $brand = trim((string) ($filters['brand'] ?? ''));
        if ($brand !== '') {
            $where[] = 'm.brand = ?';
            $params[] = $brand;
        }

        if (isset($filters['min_price']) && is_numeric($filters['min_price']) && (int) $filters['min_price'] > 0) {
            $where[] = 'm.price >= ?';
            $params[] = (int) $filters['min_price'];
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price']) && (int) $filters['max_price'] > 0) {
            $where[] = 'm.price <= ?';
            $params[] = (int) $filters['max_price'];
        }

        $sort = match ((string) ($filters['sort'] ?? 'newest')) {
            'price_asc' => 'm.price ASC',
            'price_desc' => 'm.price DESC',
            default => 'm.created_at DESC',
        };

        $whereSql = implode(' AND ', $where);
        $total = (int) Db::scalar('SELECT COUNT(*) FROM modems m WHERE ' . $whereSql, $params, 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $items = Db::all(
            'SELECT m.*, COALESCE(rp.display_name, u.username) AS reseller_name
             FROM modems m
             LEFT JOIN users u ON u.id = m.reseller_id
             LEFT JOIN reseller_profiles rp ON rp.user_id = m.reseller_id
             WHERE ' . $whereSql . ' ORDER BY ' . $sort . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        $brand = trim((string) ($data['brand'] ?? ''));
        $model = trim((string) ($data['model'] ?? ''));

        if ($brand === '' || $model === '') {
            throw new RuntimeException('برند و مدل مودم الزامی است.');
        }

        try {
            return Db::insert('modems', [
                'brand' => mb_substr($brand, 0, 60),
                'model' => mb_substr($model, 0, 80),
                'price' => max(0, (int) ($data['price'] ?? 0)),
                'stock' => max(0, (int) ($data['stock'] ?? 0)),
                'image_path' => $data['image_path'] ?? null,
                'description' => isset($data['description']) ? trim((string) $data['description']) : null,
                'reseller_id' => isset($data['reseller_id']) && (int) $data['reseller_id'] > 0 ? (int) $data['reseller_id'] : null,
                'status' => (string) ($data['status'] ?? 'active'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                throw new RuntimeException('این برند و مدل قبلاً برای همین فروشنده ثبت شده است.');
            }

            throw $e;
        }
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data): void
    {
        $current = self::find($id);

        if ($current === null) {
            throw new RuntimeException('مودم یافت نشد.');
        }

        $update = [
            'brand' => mb_substr(trim((string) ($data['brand'] ?? $current['brand'])), 0, 60),
            'model' => mb_substr(trim((string) ($data['model'] ?? $current['model'])), 0, 80),
            'price' => max(0, (int) ($data['price'] ?? $current['price'])),
            'stock' => max(0, (int) ($data['stock'] ?? $current['stock'])),
            'description' => isset($data['description']) ? trim((string) $data['description']) : $current['description'],
            'status' => (string) ($data['status'] ?? $current['status']),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (array_key_exists('image_path', $data)) {
            $update['image_path'] = $data['image_path'];
        }

        if (array_key_exists('reseller_id', $data)) {
            $update['reseller_id'] = (int) $data['reseller_id'] > 0 ? (int) $data['reseller_id'] : null;
        }

        try {
            Db::update('modems', $update, 'id = ?', [$id]);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                throw new RuntimeException('این برند و مدل قبلاً ثبت شده است.');
            }

            throw $e;
        }
    }

    public static function delete(int $id): void
    {
        Db::delete('modems', 'id = ?', [$id]);
    }

    public static function decrementStock(int $id, int $qty): bool
    {
        if ($qty < 1) {
            return false;
        }

        return Db::query(
            'UPDATE modems SET stock = stock - ?, updated_at = NOW() WHERE id = ? AND stock >= ?',
            [$qty, $id, $qty]
        )->rowCount() === 1;
    }

    /** @return array<int, string> */
    public static function brands(): array
    {
        $rows = Db::all("SELECT DISTINCT brand FROM modems WHERE status = 'active' ORDER BY brand ASC");

        return array_column($rows, 'brand');
    }

    public static function countActive(?int $resellerId = null): int
    {
        $sql = "SELECT COUNT(*) FROM modems WHERE status = 'active' AND stock > 0";
        $params = [];

        if ($resellerId !== null) {
            $sql .= ' AND reseller_id = ?';
            $params[] = $resellerId;
        }

        return (int) Db::scalar($sql, $params, 0);
    }

    public static function lowStock(int $threshold = 3): int
    {
        return (int) Db::scalar(
            "SELECT COUNT(*) FROM modems WHERE status = 'active' AND stock <= ?",
            [$threshold],
            0
        );
    }
}
