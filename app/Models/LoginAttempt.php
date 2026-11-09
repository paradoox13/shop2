<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Db;

final class LoginAttempt
{
    public static function record(string $ip, ?string $username, bool $success): void
    {
        Db::insert('login_attempts', [
            'ip' => mb_substr($ip, 0, 45),
            'username' => $username !== null ? mb_substr($username, 0, 60) : null,
            'success' => $success ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function cleanup(int $days = 30): int
    {
        return Db::delete('login_attempts', 'created_at < (NOW() - INTERVAL ? DAY)', [$days]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function recentFailures(int $limit = 50): array
    {
        return Db::all(
            'SELECT ip, username, COUNT(*) AS attempts, MAX(created_at) AS last_attempt
             FROM login_attempts WHERE success = 0
             GROUP BY ip, username ORDER BY last_attempt DESC LIMIT ' . max(1, min(200, $limit))
        );
    }
}
