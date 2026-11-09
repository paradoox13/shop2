<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Cache;
use App\Support\Db;

final class User
{
    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
    }

    /** @return array<string, mixed>|null */
    public static function findByUsername(string $username): ?array
    {
        return Db::one('SELECT * FROM users WHERE username = ? LIMIT 1', [$username]);
    }

    /**
     * @param array<string, mixed> $extra
     */
    public static function create(
        string $username,
        #[\SensitiveParameter] string $password,
        string $role = 'customer',
        array $extra = [],
    ): int {
        $data = [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => (int) config('security.password_cost', 12)]),
            'role' => $role,
            'status' => 'active',
            'full_name' => $extra['full_name'] ?? null,
            'phone' => $extra['phone'] ?? null,
            'email' => $extra['email'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return Db::insert('users', $data);
    }

    public static function updatePassword(int $id, #[\SensitiveParameter] string $password): void
    {
        Db::update('users', [
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => (int) config('security.password_cost', 12)]),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    public static function touchLogin(int $id): void
    {
        Db::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public static function setTotpSecret(int $id, ?string $secret): void
    {
        Db::update('users', [
            'totp_secret' => $secret,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data): int
    {
        $allowed = array_intersect_key($data, array_flip(['full_name', 'phone', 'email', 'status', 'role', 'username']));
        $allowed['updated_at'] = date('Y-m-d H:i:s');

        return Db::update('users', $allowed, 'id = ?', [$id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Db::update('users', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }

    public static function countByRole(string $role): int
    {
        return (int) Db::scalar('SELECT COUNT(*) FROM users WHERE role = ?', [$role], 0);
    }

    /**
     * ساخت نماینده + پروفایل در یک تراکنش.
     * @param array<string, mixed> $data
     */
    public static function createReseller(array $data): int
    {
        return Db::transaction(static function () use ($data): int {
            $userId = self::create(
                (string) $data['username'],
                (string) $data['password'],
                'reseller',
                [
                    'full_name' => $data['full_name'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                ]
            );

            self::upsertResellerProfile(
                $userId,
                (string) ($data['display_name'] ?? $data['full_name'] ?? $data['username']),
                isset($data['contact']) ? (string) $data['contact'] : null,
                (float) ($data['commission_rate'] ?? config('commission_default', 10))
            );

            return $userId;
        });
    }

    public static function upsertResellerProfile(int $userId, string $displayName, ?string $contact, float $commission): void
    {
        Db::query(
            'INSERT INTO reseller_profiles (user_id, display_name, contact, commission_rate)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE display_name = ?, contact = ?, commission_rate = ?',
            [$userId, $displayName, $contact, $commission, $displayName, $contact, $commission]
        );
    }

    /** @return array<string, mixed>|null */
    public static function resellerProfile(int $userId): ?array
    {
        return Db::one('SELECT * FROM reseller_profiles WHERE user_id = ?', [$userId]);
    }

    /**
     * لیست نمایندگان با آمار محصولات.
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, pages:int, per_page:int}
     */
    public static function paginateResellers(?string $search, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $where = "u.role = 'reseller'";
        $params = [];

        if ($search !== null && trim($search) !== '') {
            $like = '%' . trim($search) . '%';
            $where .= ' AND (u.username LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ? OR rp.display_name LIKE ?)';
            $params = [$like, $like, $like, $like];
        }

        $total = (int) Db::scalar(
            "SELECT COUNT(*) FROM users u LEFT JOIN reseller_profiles rp ON rp.user_id = u.id WHERE {$where}",
            $params,
            0
        );

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $items = Db::all(
            "SELECT u.id, u.username, u.full_name, u.phone, u.email, u.status, u.created_at, u.last_login_at,
                    rp.display_name, rp.contact, rp.commission_rate,
                    (SELECT COUNT(*) FROM phone_numbers p WHERE p.reseller_id = u.id) AS numbers_count,
                    (SELECT COUNT(*) FROM phone_numbers p WHERE p.reseller_id = u.id AND p.status = 'sold') AS sold_count,
                    (SELECT COUNT(*) FROM modems m WHERE m.reseller_id = u.id) AS modems_count
             FROM users u
             LEFT JOIN reseller_profiles rp ON rp.user_id = u.id
             WHERE {$where}
             ORDER BY u.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** @return array<int, array<string, mixed>> */
    public static function resellersForSelect(): array
    {
        $cached = Cache::get('users:resellers:select');

        if (is_array($cached)) {
            return $cached;
        }

        $rows = Db::all(
            "SELECT u.id, u.username, COALESCE(rp.display_name, u.full_name, u.username) AS name
             FROM users u LEFT JOIN reseller_profiles rp ON rp.user_id = u.id
             WHERE u.role = 'reseller' AND u.status = 'active'
             ORDER BY name ASC"
        );

        Cache::put('users:resellers:select', $rows, 300);

        return $rows;
    }

    public static function flushResellerCache(): void
    {
        Cache::forget('users:resellers:select');
    }
}
