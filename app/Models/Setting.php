<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Cache;
use App\Support\Db;
use Throwable;

final class Setting
{
    /** @var array<string, string>|null */
    private static ?array $cache = null;

    /** @return array<string, string> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $cached = Cache::get('settings:all');
        if (is_array($cached)) {
            /** @var array<string, string> $cached */
            return self::$cache = $cached;
        }

        $settings = [];

        try {
            $rows = Db::all('SELECT `key`, `value` FROM `settings`');
            foreach ($rows as $row) {
                $settings[(string) $row['key']] = (string) ($row['value'] ?? '');
            }
        } catch (Throwable) {
            $settings = [];
        }

        Cache::put('settings:all', $settings, 600);

        return self::$cache = $settings;
    }

    public static function get(string $key, string $default = ''): string
    {
        $settings = self::all();

        return array_key_exists($key, $settings) && $settings[$key] !== '' ? $settings[$key] : $default;
    }

    public static function set(string $key, string $value): void
    {
        Db::query(
            'INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = ?',
            [$key, $value, $value]
        );

        self::flush();
    }

    /** @param array<string, string> $values */
    public static function setMany(array $values): void
    {
        Db::transaction(static function () use ($values): void {
            foreach ($values as $key => $value) {
                Db::query(
                    'INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)
                     ON DUPLICATE KEY UPDATE `value` = ?',
                    [$key, $value, $value]
                );
            }
        });

        self::flush();
    }

    public static function flush(): void
    {
        self::$cache = null;
        Cache::forget('settings:all');
    }
}
