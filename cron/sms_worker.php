<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$service = new \App\Services\SmsService();
$sent = 0;
$failed = 0;
$started = time();

while ($sent + $failed < 30 && (time() - $started) < 20) {
    $job = \App\Models\Job::claimNext('sms');

    if ($job === null) {
        break;
    }

    $logId = (int) (($job['payload_array']['log_id'] ?? 0));

    try {
        if ($logId <= 0) {
            throw new \RuntimeException('شناسه پیامک در Job موجود نیست.');
        }

        $service->sendLog($logId);
        \App\Models\Job::complete((int) $job['id']);
        $sent++;
    } catch (\Throwable $e) {
        \App\Models\Job::fail((int) $job['id'], $e->getMessage());
        $failed++;
    }
}

cron_finish('sms_worker', [
    'sent' => $sent,
    'failed' => $failed,
    'pending_left' => \App\Models\Job::pendingCount('sms'),
]);
