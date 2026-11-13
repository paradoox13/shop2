<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Operator;
use App\Support\Jalali;
use App\Support\Str;

/**
 * ساخت شرط‌های امن جستجوی شماره (بدون الحاق مستقیم ورودی به SQL).
 * خروجی: [whereSql, params, orderBy]
 */
final class NumberFilterService
{
    /**
     * @param array<string, mixed> $filters
     * @return array{0:string, 1:array<int, mixed>, 2:string}
     */
    public static function build(array $filters, ?int $resellerId = null, bool $publicOnly = false): array
    {
        $where = [];
        $params = [];

        if ($publicOnly) {
            $where[] = "p.status = 'available'";
        }

        if ($resellerId !== null) {
            $where[] = 'p.reseller_id = ?';
            $params[] = $resellerId;
        }

        // اپراتور با شناسه یا اسلاگ
        $operator = trim((string) ($filters['operator'] ?? ''));
        if ($operator !== '') {
            $operatorId = is_numeric($operator) ? (int) $operator : null;

            if ($operatorId === null) {
                foreach (Operator::all(false) as $row) {
                    if ($row['slug'] === $operator) {
                        $operatorId = (int) $row['id'];
                        break;
                    }
                }
            }

            if ($operatorId !== null) {
                $where[] = 'p.operator_id = ?';
                $params[] = $operatorId;
            }
        }

        // دسته‌بندی شماره و نوع خط
        $numberType = trim((string) ($filters['number_type'] ?? ''));
        if (array_key_exists($numberType, \App\Models\PhoneNumber::TYPES)) {
            $where[] = 'p.number_type = ?';
            $params[] = $numberType;
        }

        $lineType = trim((string) ($filters['line_type'] ?? ''));
        if (array_key_exists($lineType, \App\Models\PhoneNumber::LINE_TYPES)) {
            $where[] = 'p.line_type = ?';
            $params[] = $lineType;
        }

        // وضعیت (پنل نماینده/ادمین)
        if (!$publicOnly && isset($filters['status']) && $filters['status'] !== '') {
            $status = (string) $filters['status'];
            if (in_array($status, ['available', 'reserved', 'sold', 'hidden'], true)) {
                $where[] = 'p.status = ?';
                $params[] = $status;
            }
        }

        // بازه قیمت (تومان)
        if (isset($filters['min_price']) && is_numeric($filters['min_price']) && (int) $filters['min_price'] > 0) {
            $where[] = 'p.price >= ?';
            $params[] = (int) $filters['min_price'];
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price']) && (int) $filters['max_price'] > 0) {
            $where[] = 'p.price <= ?';
            $params[] = (int) $filters['max_price'];
        }

        // پیش‌شماره
        $prefix = preg_replace('/\D+/', '', Str::toEnglishDigits(trim((string) ($filters['prefix'] ?? '')))) ?? '';
        if ($prefix !== '') {
            if (str_starts_with($prefix, '9') && strlen($prefix) <= 4) {
                $prefix = '0' . $prefix;
            }

            if (strlen($prefix) >= 3 && strlen($prefix) <= 6) {
                $where[] = 'p.number LIKE ?';
                $params[] = self::like($prefix) . '%';
            }
        }

        // پسوند
        $suffix = preg_replace('/\D+/', '', Str::toEnglishDigits(trim((string) ($filters['suffix'] ?? '')))) ?? '';
        if ($suffix !== '' && strlen($suffix) <= 6) {
            $where[] = 'p.number LIKE ?';
            $params[] = '%' . self::like($suffix);
        }

        // الگوهای آماده
        $pattern = (string) ($filters['pattern'] ?? '');
        $patternSql = match ($pattern) {
            // چهار رقم یکسان پشت سر هم در هر جای شماره
            'repeat' => "p.number REGEXP '([0-9])\\\\1{3,}'",
            // رند: چهار رقم یکسان در انتها (مثل ...5555)
            'round' => 'p.number REGEXP ' . "'(0000|1111|2222|3333|4444|5555|6666|7777|8888|9999)$'",
            // سه رقم یکسان در انتها
            'tail_repeat' => "p.number REGEXP '([0-9])\\\\1{2}$'",
            // چهار رقم متوالی صعودی یا نزولی
            'serial' => "p.number REGEXP '(0123|1234|2345|3456|4567|6789|9876|8765|7654|6543|5432|4321|3210)'",
            default => null,
        };

        if ($patternSql !== null) {
            $where[] = $patternSql;
        }

        // جستجوی سریع
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $digits = preg_replace('/\D+/', '', Str::toEnglishDigits($q)) ?? '';

            if ($digits !== '' && strlen($digits) >= 2) {
                $where[] = 'p.number LIKE ?';
                $params[] = '%' . $digits . '%';
            } else {
                $where[] = 'p.description LIKE ?';
                $params[] = '%' . self::like($q) . '%';
            }
        }

        $contains = preg_replace('/\D+/', '', Str::toEnglishDigits(trim((string) ($filters['number_contains'] ?? '')))) ?? '';
        if ($contains !== '' && $contains !== $q) {
            $where[] = 'p.number LIKE ?';
            $params[] = '%' . $contains . '%';
        }

        // تعداد ارقام (پیش‌فرض ۱۱)
        if (isset($filters['digits']) && is_numeric($filters['digits'])) {
            $digitsCount = (int) $filters['digits'];
            if ($digitsCount >= 10 && $digitsCount <= 11) {
                $where[] = 'CHAR_LENGTH(p.number) = ?';
                $params[] = $digitsCount;
            }
        }

        // ---------- فیلترهای تاریخ تولد ----------
        $exactBirth = Jalali::parse((string) ($filters['birth_date'] ?? ''));
        if ($exactBirth !== null) {
            $where[] = 'p.birth_date = ?';
            $params[] = $exactBirth;
        }

        $birthFrom = Jalali::parse((string) ($filters['birth_from'] ?? ''));
        if ($birthFrom !== null) {
            $where[] = 'p.birth_date >= ?';
            $params[] = $birthFrom;
        }

        $birthTo = Jalali::parse((string) ($filters['birth_to'] ?? ''));
        if ($birthTo !== null) {
            $where[] = 'p.birth_date <= ?';
            $params[] = $birthTo;
        }

        if (!empty($filters['has_birth'])) {
            $where[] = 'p.birth_date IS NOT NULL';
        }

        if (isset($filters['birth_year']) && trim((string) $filters['birth_year']) !== '') {
            $year = (int) preg_replace('/\D+/', '', Str::toEnglishDigits((string) $filters['birth_year']));

            if ($year >= 1200 && $year <= 1499) {
                $where[] = 'p.birth_jy = ?';
                $params[] = $year;
            } elseif ($year >= 1900 && $year <= 2100) {
                $where[] = 'p.birth_date BETWEEN ? AND ?';
                $params[] = $year . '-01-01';
                $params[] = $year . '-12-31';
            }
        }

        if (isset($filters['birth_month']) && is_numeric($filters['birth_month'])) {
            $month = (int) $filters['birth_month'];
            if ($month >= 1 && $month <= 12) {
                $where[] = 'p.birth_jm = ?';
                $params[] = $month;
            }
        }

        if (isset($filters['birth_day']) && is_numeric($filters['birth_day'])) {
            $day = (int) $filters['birth_day'];
            if ($day >= 1 && $day <= 31) {
                $where[] = 'p.birth_jd = ?';
                $params[] = $day;
            }
        }

        // تطابق ارقام تاریخ تولد با شماره (مثلاً 1370 یا 700512)
        $matchSource = $exactBirth ?? $birthFrom;

        if (!empty($filters['birth_match']) && $matchSource !== null) {
            [$jy, $jm, $jd] = Jalali::parts($matchSource);
            $year = (string) $jy;
            $shortYear = substr($year, -2);
            $monthDay = sprintf('%02d%02d', $jm, $jd);

            $where[] = '(p.number LIKE ? OR p.number LIKE ? OR p.number LIKE ?)';
            $params[] = '%' . $year . '%';
            $params[] = '%' . $shortYear . $monthDay . '%';
            $params[] = '%' . $monthDay . '%';
        }

        $birthDigits = preg_replace('/\D+/', '', Str::toEnglishDigits(trim((string) ($filters['birth_digits'] ?? '')))) ?? '';
        if ($birthDigits !== '' && strlen($birthDigits) >= 2) {
            $where[] = 'p.number LIKE ?';
            $params[] = '%' . $birthDigits . '%';
        }

        // ---------- مرتب‌سازی ----------
        $sort = match ((string) ($filters['sort'] ?? 'newest')) {
            'price_asc', 'cheap' => 'p.price ASC, p.id DESC',
            'price_desc', 'expensive' => 'p.price DESC, p.id DESC',
            'popular' => 'p.views DESC, p.id DESC',
            'oldest' => 'p.id ASC',
            default => 'p.created_at DESC, p.id DESC',
        };

        $whereSql = $where === [] ? '1 = 1' : implode(' AND ', $where);

        return [$whereSql, $params, $sort];
    }

    private static function like(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
    }
}
