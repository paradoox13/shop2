<?php

declare(strict_types=1);

namespace App\Support;

/**
 * بارگذار تنظیمات محیطی از config/env.php (بازگرداندن آرایه PHP).
 * دلیل انتخاب PHP array به‌جای .env: نیازی به پارسر اضافه نیست، خطای تایپی
 * در زمان اجرا مشخص می‌شود و توسط .htaccess هم محافظت شده است.
 */
final class Env
{
    /** @var array<string, mixed> */
    private static array $values = [];

    private static bool $loaded = false;

    public static function load(string $file): void
    {
        if (!is_file($file)) {
            throw new \RuntimeException(
                'فایل تنظیمات محیطی یافت نشد. config/env.php.example را به config/env.php کپی کنید.'
            );
        }

        $data = require $file;

        if (!is_array($data)) {
            throw new \RuntimeException('ساختار config/env.php نامعتبر است؛ باید یک آرایه PHP برگرداند.');
        }

        self::$values = $data;
        self::$loaded = true;
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        return self::$values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            return $default;
        }

        return array_key_exists($key, self::$values) && self::$values[$key] !== ''
            ? self::$values[$key]
            : $default;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, null);

        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key, null);

        return is_numeric($value) ? (int) $value : $default;
    }
}
