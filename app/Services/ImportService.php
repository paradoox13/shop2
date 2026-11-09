<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ImportLog;
use App\Models\Modem;
use App\Models\Operator;
use App\Models\PhoneNumber;
use App\Support\Config;
use App\Support\Jalali;
use App\Support\Logger;
use App\Support\Str;
use App\Support\XlsxReader;
use RuntimeException;
use Throwable;

/**
 * ایمپورت Chunked (۱۰۰ ردیف در هر درخواست) بدون Queue سرور.
 * فایل آپلودی به NDJSON تبدیل می‌شود تا خواندن هر بخش سریع و کم‌حافظه باشد.
 */
final class ImportService
{
    private const CHUNK_SIZE = 100;

    private const MAX_ROWS = 5000;

    /** @var array<int, string> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $file
     * @return array{id:int, total:int}
     */
    public function upload(int $resellerId, string $type, array $file): array
    {
        if (!in_array($type, ['number', 'modem'], true)) {
            throw new RuntimeException('نوع ایمپورت نامعتبر است.');
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->uploadErrorMessage((int) ($file['error'] ?? 0)));
        }

        $maxBytes = (int) config('security.max_upload_mb', 5) * 1024 * 1024;

        if ((int) ($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('حجم فایل بیش از ' . fa_num((int) config('security.max_upload_mb', 5)) . ' مگابایت است.');
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, ['csv', 'xlsx'], true)) {
            throw new RuntimeException('فقط فایل CSV یا XLSX مجاز است.');
        }

        $dir = rtrim((string) config('paths.imports'), '/');

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('پوشه ایمپورت قابل ایجاد نیست.');
        }

        $tmpPath = $dir . '/upload-' . bin2hex(random_bytes(8)) . '.' . $extension;

        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $tmpPath)) {
            throw new RuntimeException('ذخیره فایل آپلودی ناموفق بود.');
        }

        try {
            $rows = $extension === 'xlsx' ? $this->readXlsx($tmpPath) : $this->readCsv($tmpPath);

            if ($rows === []) {
                throw new RuntimeException('فایل خالی است.');
            }

            $headers = array_shift($rows) ?? [];
            $map = $this->headerMap($type, $headers);
            $hasHeader = $map !== null;
            $dataRows = $rows;

            if ($map === null) {
                // بدون هدر: ستون‌ها بر اساس ترتیب استاندارد
                $dataRows = array_merge([$headers], $rows);
                $map = $this->positionalMap($type);
            }

            if (count($dataRows) > self::MAX_ROWS) {
                throw new RuntimeException('حداکثر ' . fa_num(self::MAX_ROWS) . ' ردیف در هر فایل مجاز است. فایل را تقسیم کنید.');
            }

            $ndjson = $dir . '/' . $resellerId . '-' . $type . '-' . bin2hex(random_bytes(6)) . '.ndjson';
            $handle = fopen($ndjson, 'wb');

            if ($handle === false) {
                throw new RuntimeException('ایجاد فایل پردازش ناموفق بود.');
            }

            $total = 0;
            $rowNumber = $hasHeader ? 1 : 0;

            foreach ($dataRows as $row) {
                $rowNumber++;
                $values = array_map(static fn ($value): string => trim((string) ($value ?? '')), $row);

                if (implode('', $values) === '') {
                    continue;
                }

                $normalized = $this->normalizeRow($type, $values, $map, $rowNumber);
                fwrite($handle, json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
                $total++;
            }

            fclose($handle);

            if ($total === 0) {
                @unlink($ndjson);

                throw new RuntimeException('هیچ ردیف معتبری در فایل یافت نشد.');
            }

            $importId = ImportLog::create($resellerId, $type, basename($ndjson), $headers, $total);

            return ['id' => $importId, 'total' => $total];
        } finally {
            @unlink($tmpPath);
        }
    }

    /**
     * پردازش یک بخش (حداکثر ۱۰۰ ردیف). روی فایل قفل انحصاری می‌گیرد
     * تا وب و Cron هم‌زمان یک ردیف را دوبار پردازش نکنند.
     *
     * @return array{status:string, processed:int, total:int, inserted:int, failed:int, percent:int, message:string}
     */
    public function processChunk(int $importId, int $limit = self::CHUNK_SIZE, ?int $resellerId = null): array
    {
        $log = ImportLog::find($importId, $resellerId);

        if ($log === null) {
            throw new RuntimeException('ایمپورت یافت نشد.');
        }

        if ($log['status'] === 'completed' || $log['status'] === 'failed') {
            return $this->progress($log, 'ایمپورت قبلاً پردازش شده است.');
        }

        $path = rtrim((string) config('paths.imports'), '/') . '/' . basename((string) $log['file_path']);

        if (!is_file($path)) {
            ImportLog::markFailed($importId, 'فایل ایمپورت یافت نشد.');

            return $this->progress(ImportLog::find($importId) ?? $log, 'فایل ایمپورت یافت نشد.');
        }

        $handle = fopen($path, 'r+');

        if ($handle === false) {
            throw new RuntimeException('باز کردن فایل ایمپورت ناموفق بود.');
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);

            return $this->progress($log, 'این ایمپورت همین حالا در حال پردازش است.');
        }

        $processed = (int) $log['processed_rows'];
        $inserted = (int) $log['inserted_rows'];
        $failed = (int) $log['failed_rows'];
        $cursor = (int) $log['cursor_offset'];
        $total = (int) $log['total_rows'];

        fseek($handle, $cursor);
        $chunkProcessed = 0;

        while ($chunkProcessed < max(1, min(200, $limit)) && !feof($handle)) {
            $line = fgets($handle);

            if ($line === false) {
                break;
            }

            $chunkProcessed++;
            $cursor = (int) ftell($handle);
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $row = json_decode($line, true);

            if (!is_array($row)) {
                $failed++;
                $this->errors[] = 'ردیف نامعتبر (JSON).';
                continue;
            }

            try {
                $this->importRow((string) $log['type'], $row, $resellerId ?? (int) $log['reseller_id']);
                $inserted++;
            } catch (Throwable $e) {
                $failed++;
                $this->errors[] = 'ردیف ' . fa_num((string) ($row['_row'] ?? '?')) . ': ' . $e->getMessage();
            }
        }

        $processed += $chunkProcessed;
        $done = feof($handle) || $processed >= $total;
        $status = $done ? 'completed' : 'processing';
        $errorLog = trim((string) ($log['error_log'] ?? ''));

        if ($this->errors !== []) {
            $errorLog = mb_substr($errorLog . "\n" . implode("\n", $this->errors), 0, 60000);
        }

        ImportLog::updateProgress($importId, $processed, $inserted, $failed, $cursor, $status, $errorLog);
        flock($handle, LOCK_UN);
        fclose($handle);

        $updated = ImportLog::find($importId) ?? $log;

        return $this->progress($updated, $done ? 'ایمپورت کامل شد.' : 'بخش بعدی آماده پردازش است.');
    }

    /** @return array{status:string, processed:int, total:int, inserted:int, failed:int, percent:int, message:string} */
    public function progress(array $log, string $message = ''): array
    {
        $total = max(0, (int) ($log['total_rows'] ?? 0));
        $processed = min($total, (int) ($log['processed_rows'] ?? 0));
        $percent = $total > 0 ? (int) floor(($processed / $total) * 100) : 0;

        return [
            'status' => (string) ($log['status'] ?? 'pending'),
            'processed' => $processed,
            'total' => $total,
            'inserted' => (int) ($log['inserted_rows'] ?? 0),
            'failed' => (int) ($log['failed_rows'] ?? 0),
            'percent' => min(100, $percent),
            'message' => $message,
        ];
    }

    public function csvTemplate(string $type): string
    {
        $headers = $type === 'modem'
            ? ['برند', 'مدل', 'قیمت', 'موجودی', 'توضیحات']
            : ['شماره', 'قیمت', 'اپراتور', 'تاریخ تولد', 'توضیحات'];

        $sample = $type === 'modem'
            ? ['TP-Link', 'Archer MR600', '2500000', '5', 'مودم LTE با پشتیبانی سیم‌کارت']
            : ['09121234567', '1800000', 'همراه اول', '1370/05/12', 'شماره رند'];

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers, ',', '"', '\\');
        fputcsv($handle, $sample, ',', '"', '\\');
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @param array<int, string|null> $row
     * @param array<string, int> $map
     * @return array<string, mixed>
     */
    private function normalizeRow(string $type, array $row, array $map, int $rowNumber = 0): array
    {
        $value = static fn (string $key): string => isset($map[$key]) ? (string) ($row[$map[$key]] ?? '') : '';

        if ($type === 'number') {
            return [
                '_row' => $rowNumber,
                'number' => $value('number'),
                'price' => $value('price'),
                'operator' => $value('operator'),
                'birth_date' => $value('birth_date'),
                'description' => $value('description'),
            ];
        }

        return [
            '_row' => $rowNumber,
            'brand' => $value('brand'),
            'model' => $value('model'),
            'price' => $value('price'),
            'stock' => $value('stock'),
            'description' => $value('description'),
        ];
    }

    /** @param array<string, mixed> $row */
    private function importRow(string $type, array $row, int $resellerId): void
    {
        if ($type === 'number') {
            $number = PhoneNumber::normalizeNumber((string) ($row['number'] ?? ''));

            if ($number === null) {
                throw new RuntimeException('شماره معتبر نیست.');
            }

            if (PhoneNumber::exists($number)) {
                throw new RuntimeException('شماره ' . $number . ' تکراری است.');
            }

            $price = (int) preg_replace('/\D+/', '', Str::toEnglishDigits((string) ($row['price'] ?? ''))) ;
            if ($price <= 0) {
                throw new RuntimeException('قیمت معتبر نیست.');
            }

            $operatorId = null;
            $operatorInput = trim((string) ($row['operator'] ?? ''));

            if ($operatorInput !== '') {
                foreach (Operator::all(false) as $operator) {
                    if ($operator['name'] === $operatorInput || $operator['slug'] === mb_strtolower($operatorInput)) {
                        $operatorId = (int) $operator['id'];
                        break;
                    }
                }
            }

            if ($operatorId === null) {
                $detected = (new OperatorDetector())->detect($number);

                if ($detected === null) {
                    throw new RuntimeException('اپراتور قابل تشخیص نیست؛ ستون اپراتور را کامل کنید.');
                }

                $operatorId = (int) $detected['id'];
            }

            $birthInput = trim((string) ($row['birth_date'] ?? ''));
            $birthDate = $birthInput !== '' ? Jalali::parse($birthInput) : null;

            if ($birthInput !== '' && $birthDate === null) {
                throw new RuntimeException('تاریخ تولد نامعتبر است.');
            }

            PhoneNumber::create([
                'operator_id' => $operatorId,
                'number' => $number,
                'price' => $price,
                'birth_date' => $birthDate,
                'description' => (string) ($row['description'] ?? ''),
                'reseller_id' => $resellerId,
            ]);

            return;
        }

        $brand = trim((string) ($row['brand'] ?? ''));
        $model = trim((string) ($row['model'] ?? ''));
        $price = (int) preg_replace('/\D+/', '', Str::toEnglishDigits((string) ($row['price'] ?? '')));

        if ($brand === '' || $model === '' || $price <= 0) {
            throw new RuntimeException('برند، مدل و قیمت الزامی است.');
        }

        Modem::create([
            'brand' => $brand,
            'model' => $model,
            'price' => $price,
            'stock' => max(0, (int) preg_replace('/\D+/', '', Str::toEnglishDigits((string) ($row['stock'] ?? '0')))),
            'description' => (string) ($row['description'] ?? ''),
            'reseller_id' => $resellerId,
            'status' => 'active',
        ]);
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function readCsv(string $path): array
    {
        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        if (!mb_check_encoding($content, 'UTF-8')) {
            $converted = @iconv('Windows-1256', 'UTF-8//IGNORE', $content);

            if ($converted !== false) {
                $content = $converted;
            }
        }

        $firstLine = strtok($content, "\n") ?: '';
        $delimiters = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($delimiters);
        $delimiter = (string) array_key_first($delimiters);

        if (($delimiters[$delimiter] ?? 0) === 0) {
            $delimiter = ',';
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $rows[] = array_map(static fn ($cell): ?string => $cell === null ? null : trim((string) $cell), $row);

            if (count($rows) > self::MAX_ROWS + 2) {
                break;
            }
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function readXlsx(string $path): array
    {
        $rows = [];
        $reader = new XlsxReader($path);

        foreach ($reader->rows() as $row) {
            $rows[] = array_map(static fn ($cell): ?string => $cell === null ? null : trim((string) $cell), $row);

            if (count($rows) > self::MAX_ROWS + 2) {
                throw new RuntimeException('حداکثر ' . fa_num(self::MAX_ROWS) . ' ردیف در هر فایل مجاز است. فایل را تقسیم کنید.');
            }
        }

        return $rows;
    }

    /**
     * @param array<int, string|null> $headers
     * @return array<string, int>|null
     */
    private function headerMap(string $type, array $headers): ?array
    {
        $aliases = $type === 'number'
            ? [
                'number' => ['شماره', 'شماره سیم کارت', 'شماره سیمکارت', 'number', 'phone', 'mobile'],
                'price' => ['قیمت', 'مبلغ', 'price', 'amount'],
                'operator' => ['اپراتور', 'عملگر', 'operator', 'network'],
                'birth_date' => ['تاریخ تولد', 'تولد', 'birth date', 'birthdate', 'birth_date'],
                'description' => ['توضیحات', 'شرح', 'description', 'note'],
            ]
            : [
                'brand' => ['برند', 'brand', 'سازنده'],
                'model' => ['مدل', 'model', 'model name'],
                'price' => ['قیمت', 'مبلغ', 'price', 'amount'],
                'stock' => ['موجودی', 'تعداد', 'stock', 'quantity', 'qty'],
                'description' => ['توضیحات', 'شرح', 'description', 'note'],
            ];

        $normalizedHeaders = array_map(fn (string $header): string => $this->normalizeHeader($header), array_map('strval', $headers));
        $map = [];
        $matched = 0;

        foreach ($aliases as $key => $names) {
            foreach ($normalizedHeaders as $index => $header) {
                if ($header !== '' && in_array($header, array_map(fn (string $name): string => $this->normalizeHeader($name), $names), true)) {
                    $map[$key] = $index;
                    $matched++;
                    break;
                }
            }
        }

        if ($matched < 2) {
            return null;
        }

        $required = $type === 'number' ? ['number', 'price'] : ['brand', 'model', 'price'];

        foreach ($required as $key) {
            if (!isset($map[$key])) {
                throw new RuntimeException('ستون «' . $key . '» در فایل یافت نشد. قالب نمونه را دانلود کنید.');
            }
        }

        return $map;
    }

    /** @return array<string, int> */
    private function positionalMap(string $type): array
    {
        return $type === 'number'
            ? ['number' => 0, 'price' => 1, 'operator' => 2, 'birth_date' => 3, 'description' => 4]
            : ['brand' => 0, 'model' => 1, 'price' => 2, 'stock' => 3, 'description' => 4];
    }

    private function normalizeHeader(string $header): string
    {
        $header = str_replace(["\xEF\xBB\xBF", "\u{200c}"], '', trim($header));
        $header = str_replace(['ي', 'ك'], ['ی', 'ک'], $header);
        $header = mb_strtolower($header, 'UTF-8');
        $header = preg_replace('/[\s_\-]+/u', '', $header) ?? $header;

        return $header;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'حجم فایل بیش از حد مجاز است.',
            UPLOAD_ERR_PARTIAL => 'فایل ناقص آپلود شد.',
            UPLOAD_ERR_NO_FILE => 'فایلی انتخاب نشده است.',
            UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت سرور در دسترس نیست.',
            UPLOAD_ERR_CANT_WRITE => 'نوشتن فایل روی سرور ناموفق بود.',
            default => 'خطای آپلود فایل.',
        };
    }
}
