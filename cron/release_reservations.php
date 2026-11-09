<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$released = \App\Models\Reservation::expireStale(500);

cron_finish('release_reservations', [
    'released_numbers' => $released,
    'active_reservations_left' => \App\Models\Reservation::activeCount(),
]);
