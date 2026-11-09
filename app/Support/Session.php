<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Session
{
    private static bool $started = false;

    /** @param array<string, mixed> $config */
    public static function start(array $config): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;

            return;
        }

        if (headers_sent()) {
            return;
        }

        $savePath = (string) ($config['path'] ?? BASE_PATH . '/storage/sessions');

        if (!is_dir($savePath) && !@mkdir($savePath, 0775, true) && !is_dir($savePath)) {
            throw new RuntimeException('پوشه سشن قابل ایجاد نیست. دسترسی پوشه storage/sessions را بررسی کنید.');
        }

        if (!is_writable($savePath)) {
            throw new RuntimeException('پوشه storage/sessions قابل نوشتن نیست.');
        }

        session_save_path($savePath);
        session_name((string) ($config['name'] ?? 'AVSESSID'));

        $secure = (bool) ($config['secure'] ?? true);
        $lifetime = (int) ($config['lifetime'] ?? 7200);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', (string) ($config['samesite'] ?? 'Lax'));
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        ini_set('session.gc_probability', '1');
        ini_set('session.gc_divisor', '100');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => (string) ($config['samesite'] ?? 'Lax'),
        ]);

        if (!session_start()) {
            throw new RuntimeException('شروع سشن ناموفق بود.');
        }

        self::$started = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::forget($key);

        return $value;
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        return $_SESSION;
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
        self::$started = false;
    }
}
