<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;

/**
 * لایه نازک روی PDO MySQL.
 * - ATTR_EMULATE_PREPARES=false (Prepared واقعی سمت سرور)
 * - خطاها به Exception تبدیل می‌شوند
 * - Helperهایامن برای insert/update و تراکنش‌های تودرتو
 */
final class Db
{
    private static ?PDO $pdo = null;

    private static ?array $config = null;

    /** @param array<string, mixed> $config */
    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$pdo = null;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = self::$config ?? (array) config('db', []);

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'] ?? 'localhost',
            (int) ($config['port'] ?? 3306),
            $config['database'] ?? '',
            $config['charset'] ?? 'utf8mb4'
        );

        self::$pdo = new PDO($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_TIMEOUT => 10,
        ]);

        self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        self::$pdo->exec("SET time_zone = '+03:30'");
        self::$pdo->exec("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");

        return self::$pdo;
    }

    /** @param array<string|int, mixed> $params */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $statement = self::pdo()->prepare($sql);
            $statement->execute($params);

            return $statement;
        } catch (PDOException $e) {
            Logger::error('Database query failed', [
                'sql' => $sql,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw $e;
        }
    }

    /** @param array<string|int, mixed> $params */
    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** @param array<string|int, mixed> $params */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /** @param array<string|int, mixed> $params */
    public static function scalar(string $sql, array $params = [], mixed $default = null): mixed
    {
        $value = self::query($sql, $params)->fetchColumn();

        return $value === false ? $default : $value;
    }

    /** @param array<string, mixed> $data */
    public static function insert(string $table, array $data): int
    {
        self::assertIdentifier($table);

        if ($data === []) {
            throw new RuntimeException('داده‌ای برای درج وجود ندارد.');
        }

        $columns = array_keys($data);
        foreach ($columns as $column) {
            self::assertIdentifier($column);
        }

        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );

        self::query($sql, array_values($data));

        return (int) self::pdo()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string|int, mixed> $params
     */
    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        self::assertIdentifier($table);

        if ($data === []) {
            return 0;
        }

        $set = [];
        foreach (array_keys($data) as $column) {
            self::assertIdentifier($column);
            $set[] = sprintf('`%s` = ?', $column);
        }

        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $set), $where);

        return self::query($sql, array_merge(array_values($data), $params))->rowCount();
    }

    /** @param array<string|int, mixed> $params */
    public static function delete(string $table, string $where, array $params = []): int
    {
        self::assertIdentifier($table);

        return self::query(sprintf('DELETE FROM `%s` WHERE %s', $table, $where), $params)->rowCount();
    }

    public static function inTransaction(): bool
    {
        return self::pdo()->inTransaction();
    }

    /**
     * اجرای یک بلوک در تراکنش. فراخوانی تودرتو از همان تراکنش استفاده می‌کند.
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();

        if ($pdo->inTransaction()) {
            return $callback();
        }

        $pdo->beginTransaction();

        try {
            $result = $callback();
            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            Logger::error('Transaction rolled back', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /** فقط برای ابزار نصب/مهاجرت */
    public static function executeScript(string $sql): void
    {
        self::pdo()->exec($sql);
    }

    public static function close(): void
    {
        self::$pdo = null;
    }

    private static function assertIdentifier(string $identifier): void
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier) !== 1) {
            throw new RuntimeException('شناسه نامعتبر پایگاه داده: ' . $identifier);
        }
    }
}
