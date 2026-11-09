<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$result = [
    'cache_files' => \App\Support\Cache::gc(172800),
    'log_files' => \App\Support\Logger::cleanup(30),
    'login_attempts' => \App\Models\LoginAttempt::cleanup(90),
    'done_jobs' => \App\Models\Job::cleanupDone(7),
    'released_reservations' => \App\Models\Reservation::cleanupReleased(7),
    'stuck_imports_reset' => \App\Models\ImportLog::resetStuck(15),
];

cron_finish('cleanup', $result);
