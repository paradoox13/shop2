<?php

declare(strict_types=1);

namespace App\Support;

/**
 * کلاینت HTTP سبک بر پایه cURL با Timeout و خطاهای ساختاریافته.
 * تمام درخواست‌های بیرونی (درگاه/پیامک) از این کلاس عبور می‌کنند.
 */
final class Http
{
    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $headers
     * @return array{ok:bool, status:int, body:string, data:array<string,mixed>|null, error:string|null}
     */
    public static function postJson(string $url, array $data, array $headers = [], int $timeout = 20): array
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'data' => null, 'error' => 'JSON encode failed'];
        }

        return self::request($url, 'POST', $json, array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers), $timeout);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $headers
     * @return array{ok:bool, status:int, body:string, data:array<string,mixed>|null, error:string|null}
     */
    public static function postForm(string $url, array $data, array $headers = [], int $timeout = 20): array
    {
        $body = http_build_query($data);

        return self::request($url, 'POST', $body, array_merge(['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'], $headers), $timeout);
    }

    /**
     * @param array<int, string> $headers
     * @return array{ok:bool, status:int, body:string, data:array<string,mixed>|null, error:string|null}
     */
    public static function get(string $url, array $headers = [], int $timeout = 20): array
    {
        return self::request($url, 'GET', null, $headers, $timeout);
    }

    /**
     * @param array<int, string> $headers
     * @return array{ok:bool, status:int, body:string, data:array<string,mixed>|null, error:string|null}
     */
    private static function request(string $url, string $method, ?string $body, array $headers, int $timeout): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'data' => null, 'error' => 'cURL در سرور فعال نیست.'];
        }

        $handle = curl_init($url);

        if ($handle === false) {
            return ['ok' => false, 'status' => 0, 'body' => '', 'data' => null, 'error' => 'cURL init failed'];
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'AvalSim/1.0 (+https://avvalsim.ir)',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($handle, $options);

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            Logger::warning('HTTP request failed', ['url' => self::redactUrl($url), 'error' => $error]);

            return ['ok' => false, 'status' => $status, 'body' => '', 'data' => null, 'error' => $error !== '' ? $error : 'خطای شبکه'];
        }

        $decoded = json_decode($response, true);
        $data = is_array($decoded) ? $decoded : null;

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'body' => $response,
            'data' => $data,
            'error' => $status >= 200 && $status < 300 ? null : 'پاسخ غیر موفق از سرویس (HTTP ' . $status . ')',
        ];
    }

    private static function redactUrl(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return $url;
        }

        $safe = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');

        return $safe . ($parts['path'] ?? '');
    }
}
