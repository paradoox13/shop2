<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$stats = (new \App\Services\ReconcileService())->run();

cron_finish('reconcile_payments', $stats);
