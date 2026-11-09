<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

\App\Models\ImportLog::resetStuck(15);

$logs = \App\Models\ImportLog::pendingForWorker(5);
$service = new \App\Services\ImportService();
$chunks = 0;
$imports = 0;
$started = time();

foreach ($logs as $log) {
    $imports++;

    for ($i = 0; $i < 10; $i++) {
        if ((time() - $started) >= 20) {
            break 2;
        }

        $result = $service->processChunk((int) $log['id'], 100);
        $chunks++;

        if (!in_array($result['status'], ['pending', 'processing'], true)) {
            break;
        }
    }
}

cron_finish('import_worker', [
    'imports' => $imports,
    'chunks' => $chunks,
    'seconds' => time() - $started,
]);
