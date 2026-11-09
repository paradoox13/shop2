<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Cache;
use App\Support\Db;

final class ActivationCodePrice
{
    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        $cached = Cache::get('activation:prices');

        if (is_array($cached)) {
            return $cached;
        }

        $rows = Db::all(
            'SELECT o.id AS operator_id, o.name AS operator_name, o.slug, COALESCE(acp.price, 0) AS price,
                    acp.updated_at
             FROM operators o
             LEFT JOIN activation_code_prices acp ON acp.operator_id = o.id
             WHERE o.is_active = 1
             ORDER BY o.sort_order ASC, o.id ASC'
        );

        Cache::put('activation:prices', $rows, 300);

        return $rows;
    }

    public static function price(int $operatorId): int
    {
        foreach (self::all() as $row) {
            if ((int) $row['operator_id'] === $operatorId) {
                return (int) $row['price'];
            }
        }

        return 0;
    }

    /** @param array<int, int|string> $prices operator_id => price */
    public static function updatePrices(array $prices): void
    {
        Db::transaction(static function () use ($prices): void {
            foreach ($prices as $operatorId => $price) {
                Db::query(
                    'INSERT INTO activation_code_prices (operator_id, price) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE price = ?',
                    [(int) $operatorId, max(0, (int) $price), max(0, (int) $price)]
                );
            }
        });

        self::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget('activation:prices');
        Cache::forget('operators:with_prices');
    }
}
