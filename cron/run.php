<?php

declare(strict_types=1);

/**
 * اجرای تسک‌های Cron از CLI یا وب.
 *
 * CLI:
 *   php cron/run.php release_reservations
 *
 * وب (در صورت نداشتن دسترسی SSH):
 *   https://example.com/cron/run.php?task=sms_worker&token=CRON_TOKEN
 */

require __DIR__ . '/_bootstrap.php';

$tasks = [
    'release_reservations' => 'release_reservations.php',
    'sms_worker' => 'sms_worker.php',
    'reconcile_payments' => 'reconcile_payments.php',
    'import_worker' => 'import_worker.php',
    'backup_db' => 'backup_db.php',
    'cleanup' => 'cleanup.php',
];

$isCli = PHP_SAPI === 'cli';
$task = $isCli ? (string) ($argv[1] ?? '') : (string) ($_GET['task'] ?? '');

if (!$isCli) {
    $expected = (string) \App\Support\Env::get('CRON_TOKEN', '');
    $token = (string) ($_GET['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? '');

    if ($expected === '' || !hash_equals($expected, $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => false, 'error' => 'دسترسی غیرمجاز'], JSON_UNESCAPED_UNICODE) . PHP_EOL;
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
}

if (!isset($tasks[$task])) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'تسک نامعتبر است.',
        'tasks' => array_keys($tasks),
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
}

$GLOBALS['cron_task'] = $task;

@set_time_limit(240);

require __DIR__ . '/' . $tasks[$task];
