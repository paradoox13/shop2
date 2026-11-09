<?php

declare(strict_types=1);

namespace App\Support;

/**
 * محدودیت نرخ درخواست بر پایه فایل (بدون Redis).
 * پنجره ثابت با قفل فایل؛ برای ورود، فرم تماس و APIها استفاده می‌شود.
 */
final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $data = self::read($key);

        if ($data === null) {
            return false;
        }

        if ($data['reset'] <= time()) {
            return false;
        }

        return $data['count'] >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds): void
    {
        $file = self::file($key);
        $handle = fopen($file, 'c+');

        if ($handle === false) {
            return;
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);

            return;
        }

        $raw = stream_get_contents($handle) ?: '';
        $data = json_decode($raw, true);
        $now = time();

        if (!is_array($data) || !isset($data['count'], $data['reset']) || (int) $data['reset'] <= $now) {
            $data = ['count' => 1, 'reset' => $now + $decaySeconds];
        } else {
            $data['count'] = (int) $data['count'] + 1;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data, JSON_UNESCAPED_UNICODE));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
        @chmod($file, 0640);
    }

    public static function clear(string $key): void
    {
        @unlink(self::file($key));
    }

    public static function availableIn(string $key): int
    {
        $data = self::read($key);

        if ($data === null) {
            return 0;
        }

        return max(0, $data['reset'] - time());
    }

    /** @return array{count:int, reset:int}|null */
    private static function read(string $key): ?array
    {
        $file = self::file($key);

        if (!is_file($file)) {
            return null;
        }

        $raw = @file_get_contents($file);
        $data = $raw !== false ? json_decode($raw, true) : null;

        if (!is_array($data) || !isset($data['count'], $data['reset'])) {
            return null;
        }

        return ['count' => (int) $data['count'], 'reset' => (int) $data['reset']];
    }

    private static function file(string $key): string
    {
        $dir = (string) config('paths.storage', BASE_PATH . '/storage') . '/cache';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir . '/rl_' . hash('sha256', $key) . '.json';
    }
}
