<?php

declare(strict_types=1);

/**
 * راه‌اندازی مشترک Cron (بدون Session و Router).
 * اجرای CLI: php cron/run.php <task>
 * اجرای وب: /cron/run.php?task=<task>&token=CRON_TOKEN
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/app/Support/Autoloader.php';
\App\Support\Autoloader::register(BASE_PATH . '/app');

require_once BASE_PATH . '/app/Support/helpers.php';
require_once BASE_PATH . '/lib/jdf.php';

\App\Support\Env::load(BASE_PATH . '/config/env.php');

if ((string) \App\Support\Env::get('APP_KEY', '') === '') {
    fwrite(STDERR, "APP_KEY is not configured.\n");
    exit(1);
}

\App\Support\Config::load(require BASE_PATH . '/config/config.php');
date_default_timezone_set('Asia/Tehran');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

\App\Support\Cache::init((string) config('paths.storage') . '/cache');
\App\Support\Db::configure((array) config('db'));

$GLOBALS['cron_started_at'] = microtime(true);

if (!function_exists('cron_log')) {
    /** @param array<string, mixed> $context */
    function cron_log(string $task, string $message, array $context = []): void
    {
        $line = json_encode([
            'time' => date('c'),
            'task' => $task,
            'message' => $message,
            'context' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $dir = rtrim((string) config('paths.logs', (string) config('paths.storage', BASE_PATH . '/storage') . '/logs'), '/');

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents($dir . '/cron.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('cron_finish')) {
    /** @param array<string, mixed> $result */
    function cron_finish(string $task, array $result): void
    {
        $payload = [
            'ok' => true,
            'task' => $task,
            'result' => $result,
            'duration_ms' => (int) round((microtime(true) - (float) ($GLOBALS['cron_started_at'] ?? microtime(true))) * 1000),
            'time' => date('c'),
        ];

        cron_log($task, 'completed', $result);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }
}

set_exception_handler(static function (\Throwable $e): void {
    $task = $GLOBALS['cron_task'] ?? 'unknown';

    cron_log($task, 'failed', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);

    if (PHP_SAPI !== 'cli' && !headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }

    echo json_encode([
        'ok' => false,
        'task' => $task,
        'error' => config('app.debug') ? $e->getMessage() : 'خطای داخلی در اجرای Cron',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    exit(1);
});
