<?php

declare(strict_types=1);

namespace App\Support;

use Throwable;

/**
 * لاگر فایلی ساده و thread-safe. هر روز یک فایل در storage/logs.
 */
final class Logger
{
    private static function dir(): string
    {
        $dir = (string) config('paths.storage', BASE_PATH . '/storage') . '/logs';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /** @param array<string, mixed> $context */
    public static function log(string $level, string $message, array $context = []): void
    {
        try {
            $line = sprintf(
                "[%s] %s: %s %s\n",
                date('Y-m-d H:i:s'),
                strtoupper($level),
                $message,
                $context !== [] ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
            );

            $file = self::dir() . '/app-' . date('Y-m-d') . '.log';
            @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        } catch (Throwable) {
            // لاگ نباید باعث خطای برنامه شود
        }
    }

    /** @param array<string, mixed> $context */
    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function exception(Throwable $e, string $prefix = 'Unhandled exception'): void
    {
        self::error($prefix . ': ' . $e->getMessage(), [
            'type' => $e::class,
            'file' => $e->getFile() . ':' . $e->getLine(),
            'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 15),
        ]);

        error_log(sprintf('%s: %s in %s:%d', $prefix, $e->getMessage(), $e->getFile(), $e->getLine()));
    }

    /** حذف لاگ‌های قدیمی‌تر از N روز (برای Cron) */
    public static function cleanup(int $days = 30): int
    {
        $deleted = 0;
        $threshold = time() - ($days * 86400);

        foreach (glob(self::dir() . '/*.log') ?: [] as $file) {
            if (is_file($file) && filemtime($file) !== false && filemtime($file) < $threshold) {
                if (@unlink($file)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
