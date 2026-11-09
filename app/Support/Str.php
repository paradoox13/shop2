<?php

declare(strict_types=1);

namespace App\Support;

final class Str
{
    public static function random(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function token(int $length = 32): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($alphabet) - 1;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $alphabet[random_int(0, $max)];
        }

        return $result;
    }

    public static function digits(string $value): string
    {
        return (string) preg_replace('/\D+/', '', $value);
    }

    /** نرمال‌سازی شماره موبایل ایران به 09xxxxxxxxx */
    public static function normalizeMobile(string $value): ?string
    {
        $value = self::toEnglishDigits($value);
        $value = (string) preg_replace('/[\s\-().]+/', '', $value);

        if (str_starts_with($value, '+98')) {
            $value = '0' . substr($value, 3);
        } elseif (str_starts_with($value, '0098')) {
            $value = '0' . substr($value, 4);
        } elseif (str_starts_with($value, '98') && strlen($value) === 12) {
            $value = '0' . substr($value, 2);
        } elseif (str_starts_with($value, '9') && strlen($value) === 10) {
            $value = '0' . $value;
        }

        return preg_match('/^09\d{9}$/', $value) === 1 ? $value : null;
    }

    public static function toEnglishDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '٫' => '.', '،' => ',',
        ]);
    }

    public static function toPersianDigits(string|int|float $value): string
    {
        return strtr((string) $value, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }

    public static function maskMobile(string $phone): string
    {
        if (strlen($phone) < 7) {
            return $phone;
        }

        return substr($phone, 0, 4) . '***' . substr($phone, -3);
    }

    public static function limit(?string $value, int $limit = 80): string
    {
        $value = trim((string) $value);

        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit, 'UTF-8') . '…';
    }

    public static function slug(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        $value = (string) preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $value);

        return trim($value, '-');
    }
}
