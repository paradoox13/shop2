<?php

declare(strict_types=1);

namespace App\Support;

/**
 * مدیریت احراز هویت و RBAC.
 * شناسه کاربر در سشن نگهداری می‌شود و دسترسی‌ها از جدول‌های نقش/مجوز خوانده
 * و برای مدت کوتاه کش می‌شوند.
 */
final class Auth
{
    private static ?array $user = null;

    private static ?array $permissions = null;

    /** @return array{status:string, user?:array<string,mixed>} */
    public static function attempt(string $username, #[\SensitiveParameter] string $password): array
    {
        $user = \App\Models\User::findByUsername($username);

        if ($user === null) {
            return ['status' => 'invalid'];
        }

        if (($user['status'] ?? '') !== 'active') {
            return ['status' => 'suspended'];
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return ['status' => 'invalid'];
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_BCRYPT, ['cost' => (int) config('security.password_cost', 12)])) {
            \App\Models\User::updatePassword((int) $user['id'], $password);
        }

        if (!empty($user['totp_secret'])) {
            Session::put('2fa_user_id', (int) $user['id']);
            Session::put('2fa_started_at', time());

            return ['status' => '2fa', 'user' => $user];
        }

        self::login($user);

        return ['status' => 'ok', 'user' => $user];
    }

    public static function loginByPendingTwoFactor(): bool
    {
        $userId = Session::get('2fa_user_id');
        $startedAt = (int) Session::get('2fa_started_at', 0);

        if (!is_int($userId) && !is_numeric($userId)) {
            return false;
        }

        if ($startedAt > 0 && time() - $startedAt > 600) {
            Session::forget('2fa_user_id');
            Session::forget('2fa_started_at');

            return false;
        }

        $user = \App\Models\User::find((int) $userId);

        if ($user === null || $user['status'] !== 'active') {
            return false;
        }

        Session::forget('2fa_user_id');
        Session::forget('2fa_started_at');
        self::login($user);

        return true;
    }

    /** @param array<string, mixed> $user */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('auth_id', (int) $user['id']);
        Session::put('auth_time', time());
        Csrf::rotate();

        self::$user = $user;
        self::$permissions = null;

        \App\Models\User::touchLogin((int) $user['id']);
        Audit::log('auth.login', 'users', (string) $user['id']);
    }

    public static function logout(): void
    {
        if (self::check()) {
            Audit::log('auth.logout', 'users', (string) self::id());
        }

        Session::forget('auth_id');
        Session::forget('2fa_user_id');
        self::$user = null;
        self::$permissions = null;
        Csrf::rotate();
        Session::regenerate();
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $id = Session::get('auth_id');

        if (!is_numeric($id)) {
            return null;
        }

        $user = \App\Models\User::find((int) $id);

        if ($user === null || $user['status'] !== 'active') {
            Session::forget('auth_id');

            return null;
        }

        return self::$user = $user;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): string
    {
        return (string) (self::user()['role'] ?? 'guest');
    }

    public static function isStaff(): bool
    {
        return in_array(self::role(), ['admin', 'super_admin'], true);
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === 'super_admin';
    }

    public static function homeUrl(): string
    {
        return match (self::role()) {
            'super_admin', 'admin' => url('/admin'),
            'reseller' => url('/reseller'),
            default => url('/'),
        };
    }

    public static function can(string $permission): bool
    {
        $user = self::user();

        if ($user === null) {
            return false;
        }

        if ($user['role'] === 'super_admin') {
            return true;
        }

        $permissions = self::permissionsForRole((string) $user['role']);

        return in_array($permission, $permissions, true);
    }

    /** @return array<int, string> */
    private static function permissionsForRole(string $role): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }

        $cached = Cache::get('rbac:role:' . $role);

        if (is_array($cached)) {
            return self::$permissions = $cached;
        }

        try {
            $rows = Db::all(
                'SELECT p.name FROM role_permissions rp
                 INNER JOIN permissions p ON p.name = rp.permission_name
                 WHERE rp.role_name = ?',
                [$role]
            );
            $permissions = array_column($rows, 'name');
        } catch (\Throwable) {
            $permissions = [];
        }

        Cache::put('rbac:role:' . $role, $permissions, 3600);

        return self::$permissions = $permissions;
    }

    public static function flushPermissionsCache(): void
    {
        foreach (['super_admin', 'admin', 'reseller', 'customer'] as $role) {
            Cache::forget('rbac:role:' . $role);
        }

        self::$permissions = null;
    }

    public static function refresh(): void
    {
        self::$user = null;
        self::$permissions = null;
    }
}
