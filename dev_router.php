<?php

declare(strict_types=1);

/**
 * روتر محیط توسعه (فقط برای اجرای محلی با PHP Built-in Server).
 * در هاست واقعی از public_html/.htaccess استفاده می‌شود و این فایل آپلود نمی‌شود.
 */

$path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$public = __DIR__ . '/public_html';
$file = realpath($public . $path);

if ($path !== '/' && $file !== false && is_file($file) && str_starts_with($file, realpath($public) . DIRECTORY_SEPARATOR)) {
    return false;
}

require $public . '/index.php';
