<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Db;
use App\Support\Logger;
use PDO;
use RuntimeException;
use Throwable;

/**
 * بکاپ دیتابیس با PHP خالص (بدون mysqldump/exec).
 * خروجی: gzip SQL در storage/backups؛ نگهداری ۵ نسخه آخر.
 */
final class BackupService
{
    private const KEEP = 5;

    /** @return array{file:string, size:int, tables:int} */
    public function run(): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $dir = rtrim((string) config('paths.backups'), '/');

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('پوشه بکاپ قابل ایجاد نیست.');
        }

        $filename = 'backup-' . jdate('Ymd-His') . '.sql.gz';
        $path = $dir . '/' . $filename;
        $handle = gzopen($path, 'wb6');

        if ($handle === false) {
            throw new RuntimeException('ایجاد فایل بکاپ ناموفق بود.');
        }

        $tableCount = 0;

        try {
            gzwrite($handle, "-- AvalSim Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n");
            gzwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            $tables = Db::all('SHOW TABLES');
            $key = $tables !== [] ? array_key_first($tables[0]) : null;

            if ($key === null) {
                throw new RuntimeException('هیچ جدولی برای بکاپ یافت نشد.');
            }

            foreach ($tables as $tableRow) {
                $table = (string) $tableRow[$key];

                if (preg_match('/^[a-zA-Z0-9_]+$/', $table) !== 1) {
                    continue;
                }

                $create = Db::one('SHOW CREATE TABLE `' . $table . '`');
                $createSql = (string) ($create['Create Table'] ?? '');

                if ($createSql === '') {
                    continue;
                }

                gzwrite($handle, "\n-- Table: {$table}\nDROP TABLE IF EXISTS `{$table}`;\n{$createSql};\n");

                $statement = Db::query('SELECT * FROM `' . $table . '`');
                $buffer = '';
                $rowCount = 0;

                while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
                    $columns = array_map(static fn (string $column): string => '`' . $column . '`', array_keys($row));
                    $values = [];

                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } elseif (is_int($value) || is_float($value)) {
                            $values[] = (string) $value;
                        } else {
                            $values[] = Db::pdo()->quote((string) $value);
                        }
                    }

                    $buffer .= 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
                    $rowCount++;

                    if ($rowCount % 100 === 0) {
                        gzwrite($handle, $buffer);
                        $buffer = '';
                    }
                }

                if ($buffer !== '') {
                    gzwrite($handle, $buffer);
                }

                gzwrite($handle, "\n");
                $tableCount++;
            }

            gzwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        } catch (Throwable $e) {
            gzclose($handle);
            @unlink($path);
            Logger::error('Backup failed', ['error' => $e->getMessage()]);

            throw $e;
        }

        gzclose($handle);
        @chmod($path, 0600);

        $this->rotate($dir);
        Logger::info('Database backup created', ['file' => $filename, 'tables' => $tableCount]);

        return ['file' => $filename, 'size' => (int) (filesize($path) ?: 0), 'tables' => $tableCount];
    }

    /** @return array<int, array{file:string, size:int, modified:int}> */
    public function listBackups(): array
    {
        $dir = rtrim((string) config('paths.backups'), '/');
        $files = glob($dir . '/backup-*.sql.gz') ?: [];
        $result = [];

        foreach ($files as $file) {
            $result[] = [
                'file' => basename($file),
                'size' => (int) (filesize($file) ?: 0),
                'modified' => (int) (filemtime($file) ?: 0),
            ];
        }

        usort($result, static fn (array $a, array $b): int => $b['modified'] <=> $a['modified']);

        return $result;
    }

    private function rotate(string $dir): void
    {
        $files = glob($dir . '/backup-*.sql.gz') ?: [];

        if (count($files) <= self::KEEP) {
            return;
        }

        usort($files, static fn (string $a, string $b): int => (int) filemtime($a) <=> (int) filemtime($b));
        $remove = array_slice($files, 0, count($files) - self::KEEP);

        foreach ($remove as $file) {
            @unlink($file);
        }
    }
}
