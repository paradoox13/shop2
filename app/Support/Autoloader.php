<?php

declare(strict_types=1);

namespace App\Support;

/**
 * اتولودر PSR-4 مانند، بدون نیاز به Composer.
 * نگاشت: App\Foo\Bar  =>  BASE_PATH/app/Foo/Bar.php
 */
final class Autoloader
{
    private static ?string $appRoot = null;

    public static function register(string $appRoot): void
    {
        self::$appRoot = rtrim($appRoot, '/');

        if (!spl_autoload_register([self::class, 'load'])) {
            throw new \RuntimeException('ثبت اتولودر ممکن نشد.');
        }
    }

    public static function load(string $class): void
    {
        $prefix = 'App\\';

        if (!str_starts_with($class, $prefix) || self::$appRoot === null) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $path = self::$appRoot . '/' . str_replace('\\', '/', $relative) . '.php';

        if (is_file($path)) {
            require $path;
        }
    }
}
