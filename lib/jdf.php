<?php

declare(strict_types=1);

/**
 * jdf سبک و بدون وابستگی (نسخه فروشگاه آوال سیم)
 * -----------------------------------------------
 * پیاده‌سازی الگوریتم عمومی تبدیل تقویم جلالی/میلادی (R. Pournader) به همراه
 * توابعی سازگار با کاربردهای رایج jdf.php. این فایل هیچ وابستگی ندارد و با
 * PHP 8.2 اجرا می‌شود. توابع با function_exists محافظت شده‌اند تا در صورت
 * وجود نسخه اصلی jdf.php تداخل نداشته باشند.
 *
 * منبع الگوریتم: الگوریتم متداول تبدیل تقویم (public domain)
 * جایگزین: استخراج نسخه رسمی jdf.php از https://jdf.scr.ir و قرار دادن در lib/
 */

if (!function_exists('gregorian_to_jalali')) {
    /**
     * @return array{0:int, 1:int, 2:int} [سال، ماه، روز] شمسی
     */
    function gregorian_to_jalali(int $gy, int $gm, int $gd): array
    {
        $gDaysInMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400) + $gd + $gDaysInMonth[$gm - 1];

        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }
}

if (!function_exists('jalali_to_gregorian')) {
    /**
     * @return array{0:int, 1:int, 2:int} [سال، ماه، روز] میلادی
     */
    function jalali_to_gregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4) + $jd
            + ($jm < 7 ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $gy += 100 * intdiv(--$days, 36524);
            $days %= 36524;

            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $isLeap = ($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0;
        $monthDays = [0, 31, $isLeap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 0;

        while ($gm < 13 && $gd > $monthDays[$gm]) {
            $gd -= $monthDays[$gm];
            $gm++;
        }

        return [$gy, $gm, $gd];
    }
}

if (!function_exists('jcheckdate')) {
    /**
     * اعتبار تاریخ شمسی با تبدیل رفت‌وبرگشت (دقیق برای بازه‌های رایج).
     */
    function jcheckdate(int $jm, int $jd, int $jy): bool
    {
        if ($jy < 1 || $jy > 9999 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > 31) {
            return false;
        }

        [$gy, $gm, $gd] = jalali_to_gregorian($jy, $jm, $jd);

        if (!checkdate($gm, $gd, $gy)) {
            return false;
        }

        [$ry, $rm, $rd] = gregorian_to_jalali($gy, $gm, $gd);

        return $ry === $jy && $rm === $jm && $rd === $jd;
    }
}

if (!function_exists('tr_num')) {
    /**
     * تبدیل ارقام: mod='fa' به فارسی، mod='en' به انگلیسی.
     */
    function tr_num(string $string, string $mod = 'en'): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        if ($mod === 'fa') {
            return str_replace($english, $persian, $string);
        }

        return str_replace(array_merge($persian, $arabic), array_merge($english, $english), $string);
    }
}

if (!function_exists('jdate')) {
    /**
     * قالب‌بندی تاریخ شمسی. پشتیبانی: Y y m n d j H i s w l F t
     */
    function jdate(string $format, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $timezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Tehran');

        $gy = (int) date('Y', $timestamp);
        $gm = (int) date('n', $timestamp);
        $gd = (int) date('j', $timestamp);
        [$jy, $jm, $jd] = gregorian_to_jalali($gy, $gm, $gd);

        $months = [
            1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
            'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
        ];
        $weekdays = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];

        $isLeapJalali = jcheckdate(12, 30, $jy);
        $daysInMonth = $jm <= 6 ? 31 : ($jm <= 11 ? 30 : ($isLeapJalali ? 30 : 29));
        $w = (int) date('w', $timestamp);

        $result = '';
        $length = strlen($format);

        for ($i = 0; $i < $length; $i++) {
            $char = $format[$i];

            if ($char === '\\') {
                $i++;
                $result .= $format[$i] ?? '';
                continue;
            }

            $result .= match ($char) {
                'Y' => (string) $jy,
                'y' => substr((string) $jy, -2),
                'm' => str_pad((string) $jm, 2, '0', STR_PAD_LEFT),
                'n' => (string) $jm,
                'd' => str_pad((string) $jd, 2, '0', STR_PAD_LEFT),
                'j' => (string) $jd,
                'H' => date('H', $timestamp),
                'i' => date('i', $timestamp),
                's' => date('s', $timestamp),
                'w' => (string) $w,
                'l' => $weekdays[$w],
                'F' => $months[$jm],
                't' => (string) $daysInMonth,
                default => $char,
            };
        }

        date_default_timezone_set($timezone);

        return $result;
    }
}
