<?php

declare(strict_types=1);

use App\Http\Response;
use App\Support\Auth;
use App\Support\Cache;
use App\Support\Config;
use App\Support\Csrf;
use App\Support\Env;
use App\Support\Flash;
use App\Support\Jalali;
use App\Models\Setting as SettingModel;
use App\Support\Str;

/**
 * توابع کمکی سراسری (فقط تعریف؛ هیچ خروجی جانبی ندارد).
 */

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) config('app.url', ''), '/');

        if ($path === '') {
            return $base . '/';
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $relative = ltrim($path, '/');
        $file = (string) config('paths.public', BASE_PATH . '/public_html') . '/' . $relative;
        $version = is_file($file) ? '?v=' . filemtime($file) : '';

        return url('/' . $relative) . $version;
    }
}

if (!function_exists('upload_url')) {
    function upload_url(?string $path): string
    {
        if ($path === null || trim($path) === '') {
            return asset('img/placeholder.svg');
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return url('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('current_path')) {
    function current_path(): string
    {
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');

        return $path !== '/' ? rtrim($path, '/') : '/';
    }
}

if (!function_exists('is_active')) {
    function is_active(string $prefix, bool $exact = false): bool
    {
        $current = current_path();
        $prefix = '/' . trim($prefix, '/');

        if ($prefix === '/') {
            return $current === '/';
        }

        return $exact ? $current === $prefix : str_starts_with($current, $prefix);
    }
}

if (!function_exists('fa_num')) {
    function fa_num(string|int|float|null $value): string
    {
        return Str::toPersianDigits((string) $value);
    }
}

if (!function_exists('en_digits')) {
    function en_digits(string $value): string
    {
        return Str::toEnglishDigits($value);
    }
}

if (!function_exists('money')) {
    function money(int|float|string|null $amount, bool $withCurrency = true): string
    {
        $formatted = fa_num(number_format((float) ($amount ?? 0)));

        return $withCurrency ? $formatted . ' تومان' : $formatted;
    }
}

if (!function_exists('price')) {
    function price(int|float|string|null $amount): string
    {
        return money($amount, false);
    }
}

if (!function_exists('jalali_date')) {
    function jalali_date(?string $datetime, string $format = 'Y/m/d'): string
    {
        return Jalali::format($datetime, $format);
    }
}

if (!function_exists('jalali_datetime')) {
    function jalali_datetime(?string $datetime): string
    {
        return Jalali::formatDateTime($datetime);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return Flash::old($key, $default);
    }
}

if (!function_exists('errors')) {
    /** @return array<string, string> */
    function errors(): array
    {
        return Flash::errors();
    }
}

if (!function_exists('error')) {
    function error(string $field): ?string
    {
        return Flash::error($field);
    }
}

if (!function_exists('flash_messages')) {
    /** @return array<int, array{type:string, message:string}> */
    function flash_messages(): array
    {
        return Flash::messages();
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        Flash::add($type, $message);
    }
}

if (!function_exists('auth_user')) {
    /** @return array<string, mixed>|null */
    function auth_user(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int
    {
        return Auth::id();
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        return Auth::can($permission);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        Response::redirect($path);
    }
}

if (!function_exists('back')) {
    function back(string $fallback = '/'): never
    {
        Response::back($fallback);
    }
}

if (!function_exists('abort')) {
    function abort(int $status, string $message = ''): never
    {
        throw new \App\Exceptions\HttpException($status, $message);
    }
}

if (!function_exists('setting')) {
    function setting(string $key, string $default = ''): string
    {
        return SettingModel::get($key, $default);
    }
}

if (!function_exists('query_with')) {
    /**
     * ساخت Query String با حفظ فیلترهای فعلی و اعمال تغییرات.
     * @param array<string, mixed> $overrides
     * @param array<int, string> $remove
     */
    function query_with(array $overrides = [], array $remove = []): string
    {
        $query = $_GET;

        foreach ($remove as $key) {
            unset($query[$key]);
        }

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }

        return $query !== [] ? '?' . http_build_query($query) : '';
    }
}

if (!function_exists('query_url')) {
    function query_url(string $path, array $overrides = [], array $remove = []): string
    {
        return url($path) . query_with($overrides, $remove);
    }
}

if (!function_exists('number_status_label')) {
    function number_status_label(string $status): string
    {
        return match ($status) {
            'available' => 'موجود',
            'reserved' => 'رزرو شده',
            'sold' => 'فروخته شده',
            'hidden' => 'مخفی',
            default => $status,
        };
    }
}

if (!function_exists('order_status_label')) {
    function order_status_label(string $status): string
    {
        return match ($status) {
            'pending' => 'در انتظار پرداخت',
            'awaiting_payment' => 'در انتظار پرداخت',
            'paid' => 'پرداخت شده',
            'pending_physical_send' => 'در انتظار ارسال فیزیکی',
            'sent' => 'ارسال شده',
            'completed' => 'تکمیل شده',
            'canceled' => 'لغو شده',
            'failed' => 'ناموفق',
            'refunded' => 'بازگشت وجه',
            default => $status,
        };
    }
}

if (!function_exists('product_type_label')) {
    function product_type_label(string $type): string
    {
        return match ($type) {
            'number' => 'شماره سیم‌کارت',
            'modem' => 'مودم',
            'activation_code' => 'کد فعال‌سازی',
            default => $type,
        };
    }
}

if (!function_exists('role_label')) {
    function role_label(string $role): string
    {
        return match ($role) {
            'super_admin' => 'مدیر کل',
            'admin' => 'ادمین',
            'reseller' => 'نماینده',
            'customer' => 'مشتری',
            default => $role,
        };
    }
}

if (!function_exists('status_badge_class')) {
    function status_badge_class(string $status): string
    {
        return match ($status) {
            'available', 'active', 'paid', 'completed', 'sent', 'done', 'sent_ok' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
            'reserved', 'pending', 'awaiting_payment', 'queued', 'processing', 'pending_physical_send' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
            'sold', 'failed', 'canceled', 'suspended', 'inactive', 'refunded' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
    }
}

if (!function_exists('truncate')) {
    function truncate(?string $value, int $limit = 80): string
    {
        return Str::limit($value, $limit);
    }
}

if (!function_exists('cart_count')) {
    function cart_count(): int
    {
        return \App\Services\CartService::count();
    }
}

if (!function_exists('format_phone')) {
    function format_phone(?string $phone): string
    {
        $phone = Str::toEnglishDigits((string) $phone);

        if (strlen($phone) === 11) {
            return fa_num(substr($phone, 0, 4) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7));
        }

        return fa_num($phone);
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes(int $bytes): string
    {
        $units = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت'];
        $index = 0;

        while ($bytes >= 1024 && $index < 3) {
            $bytes /= 1024;
            $index++;
        }

        return fa_num(number_format($bytes, $index > 0 ? 1 : 0)) . ' ' . $units[$index];
    }
}

if (!function_exists('safe_json')) {
    function safe_json(mixed $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
