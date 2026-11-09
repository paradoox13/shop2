<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\NumberFilterService;
use App\Support\Db;
use App\Support\Jalali;
use App\Support\Str;
use PDOException;
use RuntimeException;

final class PhoneNumber
{
    private const SELECT_BASE = 'SELECT p.*, o.name AS operator_name, o.slug AS operator_slug,
            COALESCE(rp.display_name, u.username) AS reseller_name
        FROM phone_numbers p
        INNER JOIN operators o ON o.id = p.operator_id
        LEFT JOIN users u ON u.id = p.reseller_id
        LEFT JOIN reseller_profiles rp ON rp.user_id = p.reseller_id';

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::one(self::SELECT_BASE . ' WHERE p.id = ? LIMIT 1', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByNumber(string $number): ?array
    {
        return Db::one('SELECT * FROM phone_numbers WHERE number = ? LIMIT 1', [$number]);
    }

    public static function exists(string $number, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM phone_numbers WHERE number = ?';
        $params = [$number];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        return (int) Db::scalar($sql, $params, 0) > 0;
    }

    /**
     * جستجوی پیشرفته (عمومی، نماینده یا ادمین).
     *
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

        [$where, $params, $order] = NumberFilterService::build($filters, $resellerId, $publicOnly);

        $total = (int) Db::scalar('SELECT COUNT(*) FROM phone_numbers p WHERE ' . $where, $params, 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $items = Db::all(
            self::SELECT_BASE . ' WHERE ' . $where . ' ORDER BY ' . $order . ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $number = self::normalizeNumber((string) ($data['number'] ?? ''));

        if ($number === null) {
            throw new RuntimeException('شماره سیم‌کارت باید ۱۱ رقم و با 09 شروع شود.');
        }

        if (self::exists($number)) {
            throw new RuntimeException('این شماره قبلاً در سامانه ثبت شده است.');
        }

        $operatorId = (int) ($data['operator_id'] ?? 0);

        if ($operatorId <= 0) {
            $operator = (new \App\Services\OperatorDetector())->detect($number);

            if ($operator === null) {
                throw new RuntimeException('اپراتور شماره قابل تشخیص نیست؛ اپراتور را دستی انتخاب کنید.');
            }

            $operatorId = (int) $operator['id'];
        }

        $birthDate = self::normalizeDate($data['birth_date'] ?? null);
        [$jy, $jm, $jd] = Jalali::parts($birthDate);

        try {
            return Db::insert('phone_numbers', [
                'operator_id' => $operatorId,
                'number' => $number,
                'price' => max(0, (int) ($data['price'] ?? 0)),
                'status' => (string) ($data['status'] ?? 'available'),
                'reseller_id' => isset($data['reseller_id']) && (int) $data['reseller_id'] > 0 ? (int) $data['reseller_id'] : null,
                'birth_date' => $birthDate,
                'birth_jy' => $birthDate !== null ? $jy : null,
                'birth_jm' => $birthDate !== null ? $jm : null,
                'birth_jd' => $birthDate !== null ? $jd : null,
                'description' => isset($data['description']) ? mb_substr(trim((string) $data['description']), 0, 500) : null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                throw new RuntimeException('این شماره قبلاً در سامانه ثبت شده است.');
            }

            throw $e;
        }
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data): void
    {
        $current = self::find($id);

        if ($current === null) {
            throw new RuntimeException('شماره یافت نشد.');
        }

        $update = [
            'operator_id' => (int) ($data['operator_id'] ?? $current['operator_id']),
            'price' => max(0, (int) ($data['price'] ?? $current['price'])),
            'description' => isset($data['description']) ? mb_substr(trim((string) $data['description']), 0, 500) : $current['description'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (array_key_exists('number', $data)) {
            $number = self::normalizeNumber((string) $data['number']);

            if ($number === null) {
                throw new RuntimeException('شماره وارد شده معتبر نیست.');
            }

            if (self::exists($number, $id)) {
                throw new RuntimeException('این شماره قبلاً ثبت شده است.');
            }

            $update['number'] = $number;
        }

        if (array_key_exists('status', $data)) {
            $update['status'] = (string) $data['status'];
        }

        if (array_key_exists('reseller_id', $data)) {
            $update['reseller_id'] = (int) $data['reseller_id'] > 0 ? (int) $data['reseller_id'] : null;
        }

        if (array_key_exists('birth_date', $data)) {
            $birthDate = self::normalizeDate($data['birth_date']);
            [$jy, $jm, $jd] = Jalali::parts($birthDate);
            $update['birth_date'] = $birthDate;
            $update['birth_jy'] = $birthDate !== null ? $jy : null;
            $update['birth_jm'] = $birthDate !== null ? $jm : null;
            $update['birth_jd'] = $birthDate !== null ? $jd : null;
        }

        Db::update('phone_numbers', $update, 'id = ?', [$id]);
    }

    public static function delete(int $id): void
    {
        Db::delete('phone_numbers', 'id = ?', [$id]);
    }

    /** فروش اتمیک؛ فقط از وضعیت موجود/رزرو. */
    public static function markSold(int $id): bool
    {
        $affected = Db::query(
            "UPDATE phone_numbers SET status = 'sold', updated_at = NOW()
             WHERE id = ? AND status IN ('available', 'reserved')",
            [$id]
        )->rowCount();

        return $affected === 1;
    }

    public static function setStatus(int $id, string $status): void
    {
        Db::update('phone_numbers', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public static function incrementViews(int $id): void
    {
        Db::query('UPDATE phone_numbers SET views = views + 1 WHERE id = ?', [$id]);
    }

    /** @param array<int, int> $ids */
    public static function countUnavailable(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return (int) Db::scalar(
            "SELECT COUNT(*) FROM phone_numbers WHERE id IN ({$placeholders}) AND status <> 'available'",
            $ids,
            0
        );
    }

    public static function countAvailable(?int $resellerId = null): int
    {
        $sql = "SELECT COUNT(*) FROM phone_numbers WHERE status = 'available'";
        $params = [];

        if ($resellerId !== null) {
            $sql .= ' AND reseller_id = ?';
            $params[] = $resellerId;
        }

        return (int) Db::scalar($sql, $params, 0);
    }

    /** @return array{count:int, total:int} */
    public static function soldSummary(?int $resellerId = null): array
    {
        $sql = "SELECT COUNT(*) AS count, COALESCE(SUM(price), 0) AS total FROM phone_numbers WHERE status = 'sold'";
        $params = [];

        if ($resellerId !== null) {
            $sql .= ' AND reseller_id = ?';
            $params[] = $resellerId;
        }

        $row = Db::one($sql, $params) ?? ['count' => 0, 'total' => 0];

        return ['count' => (int) $row['count'], 'total' => (int) $row['total']];
    }

    /** @return array<int, string> */
    public static function popularPrefixes(int $limit = 8): array
    {
        $rows = Db::all(
            "SELECT LEFT(number, 4) AS prefix, COUNT(*) AS c
             FROM phone_numbers WHERE status = 'available'
             GROUP BY prefix ORDER BY c DESC LIMIT " . max(1, min(20, $limit))
        );

        return array_column($rows, 'prefix');
    }

    public static function normalizeNumber(string $number): ?string
    {
        $number = preg_replace('/\D+/', '', Str::toEnglishDigits($number)) ?? '';

        return preg_match('/^09\d{9}$/', $number) === 1 ? $number : null;
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return Jalali::parse($value);
    }
}
