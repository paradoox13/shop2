<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$result = (new \App\Services\BackupService())->run();

cron_finish('backup_db', $result);
