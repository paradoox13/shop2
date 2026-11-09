<?php

declare(strict_types=1);

use App\Support\Env;

$storage = BASE_PATH . '/storage';
$appUrl = rtrim((string) Env::get('APP_URL', 'https://avvalsim.ir'), '/');
$appEnv = (string) Env::get('APP_ENV', 'production');

// در محیط local، آدرس را از Host درخواست می‌سازیم تا پیش‌نمایش روی دامنه موقت کار کند.
// در production هرگز از Host کاربر استفاده نمی‌شود و APP_URL ثابت است.
if (PHP_SAPI !== 'cli' && in_array($appEnv, ['local', 'development'], true)) {
    $requestHost = trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? ''))[0]);
    $safeHost = $requestHost !== '' && strlen($requestHost) <= 255
        && preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?::\d{1,5})?$/i', $requestHost) === 1;

    if ($safeHost) {
        $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        $secureRequest = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
        $appUrl = ($secureRequest ? 'https' : 'http') . '://' . $requestHost;
    }
}

return [
    'app' => [
        'name' => (string) Env::get('APP_NAME', 'آوال سیم'),
        'description' => (string) Env::get('APP_DESCRIPTION', 'خرید مطمئن شماره سیم‌کارت، مودم و کد فعال‌سازی'),
        'url' => $appUrl,
        'env' => $appEnv,
        'debug' => Env::bool('APP_DEBUG', false),
        'key' => (string) Env::get('APP_KEY', ''),
        'timezone' => 'Asia/Tehran',
        'locale' => 'fa',
        'currency' => 'تومان',
        'support_phone' => (string) Env::get('SUPPORT_PHONE', ''),
    ],

    'db' => [
        'host' => (string) Env::get('DB_HOST', 'localhost'),
        'port' => Env::int('DB_PORT', 3306),
        'database' => (string) Env::get('DB_DATABASE', ''),
        'username' => (string) Env::get('DB_USERNAME', ''),
        'password' => (string) Env::get('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],

    'session' => [
        'name' => 'AVSESSID',
        'lifetime' => 7200,
        'path' => $storage . '/sessions',
        'secure' => Env::bool('SESSION_SECURE', str_starts_with($appUrl, 'https://')),
        'samesite' => 'Lax',
    ],

    'security' => [
        'csrf' => true,
        'password_cost' => 12,
        'login_max_attempts' => 5,
        'login_decay' => 600,
        'contact_max_attempts' => 3,
        'contact_decay' => 900,
        'reservation_minutes' => 15,
        'max_upload_mb' => 5,
    ],

    'payment' => [
        'driver' => (string) Env::get('PAYMENT_DRIVER', 'zarinpal'),
        'currency_label' => 'تومان',
        // مبلغ ارسالی به درگاه‌های ایرانی معمولاً ریال است
        'gateway_amount_multiplier' => 10,
        'gateways' => [
            'zarinpal' => [
                'label' => 'زرین‌پال',
                'merchant_id' => (string) Env::get('ZARINPAL_MERCHANT_ID', ''),
                'sandbox' => Env::bool('ZARINPAL_SANDBOX', false),
            ],
            'idpay' => [
                'label' => 'آی‌دی‌پی',
                'api_key' => (string) Env::get('IDPAY_API_KEY', ''),
                'sandbox' => Env::bool('IDPAY_SANDBOX', false),
            ],
            'nextpay' => [
                'label' => 'نکست‌پی',
                'api_key' => (string) Env::get('NEXTPAY_API_KEY', ''),
                'sandbox' => false,
            ],
            'fake' => [
                'label' => 'پرداخت آزمایشی',
                'enabled' => Env::bool('FAKE_GATEWAY_ENABLED', false),
            ],
        ],
    ],

    'sms' => [
        'provider' => (string) Env::get('SMS_PROVIDER', 'kavenegar'),
        'admin_phone' => (string) Env::get('ADMIN_PHONE', ''),
        'kavenegar' => [
            'api_key' => (string) Env::get('KAVENEGAR_API_KEY', ''),
            'sender' => (string) Env::get('KAVENEGAR_SENDER', ''),
        ],
        'melipayamak' => [
            'username' => (string) Env::get('MELIPAYAMAK_USERNAME', ''),
            'password' => (string) Env::get('MELIPAYAMAK_PASSWORD', ''),
            'api_key' => (string) Env::get('MELIPAYAMAK_API_KEY', ''),
            'sender' => (string) Env::get('MELIPAYAMAK_SENDER', ''),
        ],
        'max_attempts' => 5,
    ],

    'cron' => [
        'token' => (string) Env::get('CRON_TOKEN', ''),
    ],

    'pagination' => [
        'site' => 12,
        'admin' => 20,
    ],

    'paths' => [
        'base' => BASE_PATH,
        'public' => BASE_PATH . '/public_html',
        'storage' => $storage,
        'logs' => $storage . '/logs',
        'views' => BASE_PATH . '/views',
        'uploads' => BASE_PATH . '/public_html/assets/uploads',
        'imports' => $storage . '/imports',
        'backups' => $storage . '/backups',
        'database' => BASE_PATH . '/database',
    ],
];
