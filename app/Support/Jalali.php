<?php

declare(strict_types=1);

namespace App\Support;

/**
 * تاریخ شمسی: پوشش نیازهای فروشگاه روی lib/jdf.php
 * تبدیل، اعتبارسنجی، قالب‌بندی و پارس ورودی کاربر (شمسی یا میلادی).
 */
final class Jalali
{
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        return gregorian_to_jalali($gy, $gm, $gd);
    }

    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        return jalali_to_gregorian($jy, $jm, $jd);
    }

    /** تاریخ میلادی ISO را به رشته شمسی تبدیل می‌کند. */
    public static function format(?string $datetime, string $format = 'Y/m/d'): string
    {
        if ($datetime === null || $datetime === '' || str_starts_with($datetime, '0000-00-00')) {
            return '—';
        }

        $timestamp = strtotime($datetime);

        if ($timestamp === false) {
            return '—';
        }

        return jdate($format, $timestamp);
    }

    public static function formatDateTime(?string $datetime): string
    {
        return self::format($datetime, 'Y/m/d H:i');
    }

    /**
     * پارس تاریخ ورودی کاربر (شمسی یا میلادی) و بازگرداندن Y-m-d میلادی.
     * نمونه‌های پذیرفته‌شده: 1370/05/12 ، 1370-5-12 ، 1991-08-03 ، 13700512
     */
    public static function parse(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $input = trim(Str::toEnglishDigits($input));
        $input = str_replace(['.', '\\'], '/', $input);

        if ($input === '') {
            return null;
        }

        if (preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})(?:\s+\d{1,2}:\d{2}(?::\d{2})?)?$/', $input, $m) === 1) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];
        } elseif (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $input, $m) === 1) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];
        } else {
            return null;
        }

        if ($year >= 1700) {
            return checkdate($month, $day, $year)
                ? sprintf('%04d-%02d-%02d', $year, $month, $day)
                : null;
        }

        if (!jcheckdate($month, $day, $year)) {
            return null;
        }

        [$gy, $gm, $gd] = jalali_to_gregorian($year, $month, $day);

        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    /** @return array{0:int,1:int,2:int} */
    public static function parts(?string $isoDate): array
    {
        if ($isoDate === null || $isoDate === '') {
            return [0, 0, 0];
        }

        $timestamp = strtotime($isoDate);

        if ($timestamp === false) {
            return [0, 0, 0];
        }

        return gregorian_to_jalali(
            (int) date('Y', $timestamp),
            (int) date('n', $timestamp),
            (int) date('j', $timestamp)
        );
    }

    public static function today(): string
    {
        return date('Y-m-d');
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /** برای نمایش «۲ روز پیش» و مانند آن در پنل‌ها */
    public static function ago(string $datetime): string
    {
        $timestamp = strtotime($datetime);

        if ($timestamp === false) {
            return '—';
        }

        $diff = max(0, time() - $timestamp);

        return match (true) {
            $diff < 60 => 'همین حالا',
            $diff < 3600 => fa_num((int) floor($diff / 60)) . ' دقیقه پیش',
            $diff < 86400 => fa_num((int) floor($diff / 3600)) . ' ساعت پیش',
            $diff < 2592000 => fa_num((int) floor($diff / 86400)) . ' روز پیش',
            default => self::format($datetime),
        };
    }
}
