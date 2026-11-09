<?php

declare(strict_types=1);

/**
 * آوال سیم | Front Controller
 * تنها نقطه ورود وب‌اپلیکیشن. تمام درخواست‌ها با .htaccess به این فایل هدایت می‌شوند.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Support/Autoloader.php';

App\Support\Autoloader::register(BASE_PATH . '/app');

App\Bootstrap::run();
