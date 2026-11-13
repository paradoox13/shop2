<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Cache;
use App\Support\Db;

final class Operator
{
    /** @return array<int, array<string, mixed>> */
    public static function all(bool $activeOnly = true): array
    {
        $cacheKey = 'operators:all:' . ($activeOnly ? '1' : '0');
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $sql = 'SELECT * FROM operators' . ($activeOnly ? ' WHERE is_active = 1' : '') . ' ORDER BY sort_order ASC, id ASC';
        $rows = Db::all($sql);

        foreach ($rows as &$row) {
            $row['patterns'] = self::decodePatterns((string) ($row['prefix_patterns'] ?? '[]'));
        }
        unset($row);

        Cache::put($cacheKey, $rows, 600);

        return $rows;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        foreach (self::all(false) as $operator) {
            if ((int) $operator['id'] === $id) {
                return $operator;
            }
        }

        return null;
    }

    /**
     * افزودن اپراتور جدید از پنل مدیریت.
     * @param array<int, string> $patterns
     */
    public static function create(string $name, string $slug, array $patterns = [], int $sortOrder = 0): int
    {
        $name = trim($name);
        $slug = strtolower(trim($slug));

        if ($name === '' || preg_match('/^[a-z0-9_-]{2,60}$/', $slug) !== 1) {
            throw new \RuntimeException('نام و شناسه اپراتور معتبر نیست.');
        }

        $cleanPatterns = array_values(array_filter(array_map(
            static fn ($value): string => preg_replace('/\D+/', '', (string) $value) ?? '',
            $patterns
        )));

        $id = Db::insert('operators', [
            'name' => $name,
            'slug' => $slug,
            'prefix_patterns' => json_encode($cleanPatterns, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'sort_order' => max(0, $sortOrder),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        self::flushCache();
        ActivationCodePrice::flushCache();

        return $id;
    }

    /** @return array<int, string> */
    public static function decodePatterns(string $json): array
    {
        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($value): string => preg_replace('/\D+/', '', (string) $value) ?? '',
            $decoded
        )));
    }

    /** @return array<int, array<string, mixed>> */
    public static function withPrices(): array
    {
        $cached = Cache::get('operators:with_prices');

        if (is_array($cached)) {
            return $cached;
        }

        $rows = Db::all(
            'SELECT o.*, COALESCE(acp.price, 0) AS activation_price
             FROM operators o
             LEFT JOIN activation_code_prices acp ON acp.operator_id = o.id
             WHERE o.is_active = 1
             ORDER BY o.sort_order ASC, o.id ASC'
        );

        foreach ($rows as &$row) {
            $row['patterns'] = self::decodePatterns((string) ($row['prefix_patterns'] ?? '[]'));
        }
        unset($row);

        Cache::put('operators:with_prices', $rows, 600);

        return $rows;
    }

    public static function flushCache(): void
    {
        Cache::forget('operators:all:1');
        Cache::forget('operators:all:0');
        Cache::forget('operators:with_prices');
    }
}
