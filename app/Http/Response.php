<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    public static function html(string $html, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo $html;
        exit;
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-store, max-age=0');
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $path, int $status = 302): never
    {
        $location = self::safeLocation($path);

        if (!headers_sent()) {
            http_response_code($status);
            header('Location: ' . $location, true, $status);
        }

        exit;
    }

    /** برای هدایت به درگاه پرداخت (فقط http/https معتبر). */
    public static function redirectExternal(string $url): never
    {
        $url = str_replace(["\r", "\n", "\0"], '', trim($url));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (!in_array($scheme, ['http', 'https'], true)) {
            self::redirect('/');
        }

        if (!headers_sent()) {
            header('Location: ' . $url, true, 302);
        }

        exit;
    }

    public static function back(string $fallback = '/'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? parse_url((string) config('app.url'), PHP_URL_HOST);
        $refererHost = $referer !== '' ? parse_url($referer, PHP_URL_HOST) : null;

        if (is_string($referer) && $referer !== '' && is_string($refererHost) && $refererHost === $host) {
            self::redirect($referer, 302);
        }

        self::redirect($fallback);
    }

    public static function download(string $content, string $filename, string $contentType = 'text/csv; charset=UTF-8'): never
    {
        if (!headers_sent()) {
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
            header('Content-Length: ' . strlen($content));
            header('Cache-Control: no-store');
        }

        echo $content;
        exit;
    }

    private static function safeLocation(string $path): string
    {
        $path = str_replace(["\r", "\n", "\0"], '', $path);

        if (str_starts_with($path, '/')) {
            return $path;
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        if (str_starts_with($path, $appUrl)) {
            return $path;
        }

        return $appUrl . '/' . ltrim($path, '/');
    }
}
