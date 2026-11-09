<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Db;

final class Job
{
    /** @param array<string, mixed> $payload */
    public static function enqueue(string $type, array $payload, int $delaySeconds = 0): int
    {
        $availableAt = date('Y-m-d H:i:s', time() + max(0, $delaySeconds));

        return Db::insert('jobs', [
            'type' => mb_substr($type, 0, 40),
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => $availableAt,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    public static function claimNext(string $type): ?array
    {
        return Db::transaction(static function () use ($type): ?array {
            $job = Db::one(
                "SELECT * FROM jobs
                 WHERE status = 'pending' AND type = ? AND available_at <= NOW()
                 ORDER BY id ASC LIMIT 1 FOR UPDATE SKIP LOCKED",
                [$type]
            );

            if ($job === null) {
                return null;
            }

            Db::query(
                "UPDATE jobs SET status = 'processing', attempts = attempts + 1, updated_at = NOW() WHERE id = ?",
                [(int) $job['id']]
            );

            $job['attempts'] = (int) $job['attempts'] + 1;
            $job['payload_array'] = json_decode((string) $job['payload'], true) ?: [];

            return $job;
        });
    }

    public static function complete(int $id): void
    {
        Db::query(
            "UPDATE jobs SET status = 'done', processed_at = NOW(), last_error = NULL, updated_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    public static function fail(int $id, string $error, int $maxAttempts = 5): void
    {
        $job = Db::one('SELECT * FROM jobs WHERE id = ?', [$id]);

        if ($job === null) {
            return;
        }

        $attempts = (int) $job['attempts'];

        if ($attempts >= $maxAttempts) {
            Db::query(
                "UPDATE jobs SET status = 'failed', last_error = ?, updated_at = NOW() WHERE id = ?",
                [mb_substr($error, 0, 2000), $id]
            );

            return;
        }

        $delay = min(3600, 60 * (2 ** max(0, $attempts - 1)));

        Db::query(
            "UPDATE jobs SET status = 'pending', last_error = ?, available_at = (NOW() + INTERVAL ? SECOND), updated_at = NOW()
             WHERE id = ?",
            [mb_substr($error, 0, 2000), $delay, $id]
        );
    }

    public static function pendingCount(?string $type = null): int
    {
        if ($type !== null) {
            return (int) Db::scalar("SELECT COUNT(*) FROM jobs WHERE status IN ('pending', 'processing') AND type = ?", [$type], 0);
        }

        return (int) Db::scalar("SELECT COUNT(*) FROM jobs WHERE status IN ('pending', 'processing')", [], 0);
    }

    public static function cleanupDone(int $days = 7): int
    {
        return Db::delete(
            'jobs',
            "status = 'done' AND processed_at < (NOW() - INTERVAL ? DAY)",
            [$days]
        );
    }

    /** @return array<string, int> */
    public static function stats(): array
    {
        $rows = Db::all('SELECT status, COUNT(*) AS c FROM jobs GROUP BY status');
        $stats = [];

        foreach ($rows as $row) {
            $stats[(string) $row['status']] = (int) $row['c'];
        }

        return $stats;
    }
}
