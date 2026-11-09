<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * File Cache امن و سبک (بدون Redis/Memcached).
 * - هر کلید به فایل هش‌شده تبدیل می‌شود (بدون دسترسی مستقیم به نام کلید)
 * - نوشتن اتمیک با rename
 * - TTL داخلی + پاکسازی با Cron
 */
final class Cache
{
    private static ?string $dir = null;

    public static function init(string $dir): void
    {
        $dir = rtrim($dir, '/');

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('پوشه کش قابل ایجاد نیست: ' . $dir);
        }

        self::$dir = $dir;
    }

    private static function directory(): string
    {
        if (self::$dir === null) {
            self::$dir = (string) config('paths.storage', BASE_PATH . '/storage') . '/cache';

            if (!is_dir(self::$dir)) {
                @mkdir(self::$dir, 0775, true);
            }
        }

        return self::$dir;
    }

    private static function file(string $key): string
    {
        return self::directory() . '/' . hash('sha256', $key) . '.cache';
    }

    /** @return array{expires:int, value:mixed}|null */
    private static function read(string $file): ?array
    {
        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);

        if ($raw === false || $raw === '') {
            return null;
        }

        try {
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            @unlink($file);

            return null;
        }

        if (!is_array($payload) || !array_key_exists('expires', $payload) || !array_key_exists('value', $payload)) {
            @unlink($file);

            return null;
        }

        if ((int) $payload['expires'] !== 0 && (int) $payload['expires'] < time()) {
            @unlink($file);

            return null;
        }

        return ['expires' => (int) $payload['expires'], 'value' => $payload['value']];
    }

    public static function has(string $key): bool
    {
        return self::read(self::file($key)) !== null;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $payload = self::read(self::file($key));

        return $payload === null ? $default : $payload['value'];
    }

    public static function put(string $key, mixed $value, int $ttl = 300): bool
    {
        $file = self::file($key);
        $expires = $ttl > 0 ? time() + $ttl : 0;

        try {
            $json = json_encode(
                ['expires' => $expires, 'value' => $value],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (\JsonException $e) {
            Logger::error('Cache encode failed', ['key' => $key, 'error' => $e->getMessage()]);

            return false;
        }

        $tmp = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';

        if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
            return false;
        }

        @chmod($tmp, 0640);

        if (!@rename($tmp, $file)) {
            @unlink($tmp);

            return false;
        }

        return true;
    }

    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = self::get($key, self::missing());

        if ($value !== self::missing()) {
            return $value;
        }

        $value = $callback();
        self::put($key, $value, $ttl);

        return $value;
    }

    public static function forget(string $key): void
    {
        $file = self::file($key);

        if (is_file($file)) {
            @unlink($file);
        }
    }

    public static function flush(): int
    {
        $count = 0;

        foreach (glob(self::directory() . '/*.cache') ?: [] as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }

        return $count;
    }

    /** پاکسازی فایل‌های منقضی و قدیمی (Cron) */
    public static function gc(int $maxAge = 172800): int
    {
        $count = 0;

        foreach (glob(self::directory() . '/*.cache') ?: [] as $file) {
            $payload = self::read($file);

            if ($payload === null) {
                $count++;
                continue;
            }

            if ($payload['expires'] === 0 && filemtime($file) !== false && filemtime($file) < time() - $maxAge) {
                @unlink($file);
                $count++;
            }
        }

        foreach (glob(self::directory() . '/*.tmp') ?: [] as $file) {
            if (filemtime($file) !== false && filemtime($file) < time() - 3600) {
                @unlink($file);
            }
        }

        return $count;
    }

    private static function missing(): object
    {
        static $sentinel = null;
        $sentinel ??= new \stdClass();

        return $sentinel;
    }
}
