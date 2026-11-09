<?php

declare(strict_types=1);

/**
 * اجرای یک‌باره migrations.sql و seeds.sql و ساخت ادمین با رمز تصادفی.
 * پس از اجرای موفق، این فایل را حتماً حذف کنید.
 * دسترسی: https://your-domain/run_migration.php?token=YOUR_MIGRATION_TOKEN
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Support/Autoloader.php';
App\Support\Autoloader::register(BASE_PATH . '/app');

use App\Support\Db;
use App\Support\Env;
use App\Support\SqlScript;

$envFile = BASE_PATH . '/config/env.php';

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function page(string $title, string $body): void
{
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . h($title) . '</title>'
        . '<style>body{font-family:Tahoma,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:40px 16px;line-height:1.9}'
        . '.box{max-width:820px;margin:auto;background:#1e293b;border:1px solid #334155;border-radius:16px;padding:28px}'
        . 'h1{color:#5eead4;margin-top:0}.ok{color:#6ee7b7}.err{color:#fda4af}code{background:#0f172a;padding:2px 8px;border-radius:6px;direction:ltr;display:inline-block}'
        . 'li{margin-bottom:6px}.warn{background:#78350f;border:1px solid #b45309;padding:14px;border-radius:10px;margin:16px 0}</style></head><body><div class="box">'
        . $body . '</div></body></html>';
    exit;
}

if (!is_file($envFile)) {
    page('نصب نشده', '<h1 class="err">تنظیمات یافت نشد</h1><p>ابتدا <code>config/env.php.example</code> را به <code>config/env.php</code> کپی و مقادیر را تکمیل کنید.</p>');
}

try {
    Env::load($envFile);
} catch (Throwable $e) {
    page('خطا', '<h1 class="err">خطا</h1><p>' . h($e->getMessage()) . '</p>');
}

$expected = (string) Env::get('MIGRATION_TOKEN', '');
$provided = (string) ($_GET['token'] ?? $_POST['token'] ?? '');

if ($expected === '' || strlen($expected) < 16) {
    page('غیرفعال', '<h1 class="err">توکن مهاجرت تنظیم نشده است</h1><p>در <code>config/env.php</code> مقدار <code>MIGRATION_TOKEN</code> را حداقل ۱۶ کاراکتری تنظیم کنید.</p>');
}

if (!hash_equals($expected, $provided)) {
    http_response_code(403);
    page('دسترسی غیرمجاز', '<h1 class="err">دسترسی غیرمجاز</h1><p>توکن نامعتبر است.</p>');
}

$run = isset($_GET['run']) || isset($_POST['run']);

if (!$run) {
    page('اجرای نصب', '<h1>اجرای نصب آوال سیم</h1>'
        . '<p>این عملیات جدول‌ها و داده‌های اولیه را ایجاد می‌کند. اطلاعات دیتابیس از <code>config/env.php</code> خوانده می‌شود.</p>'
        . '<div class="warn">پس از پایان، فایل <code>run_migration.php</code> را حذف کنید.</div>'
        . '<form method="post"><input type="hidden" name="token" value="' . h($provided) . '"><input type="hidden" name="run" value="1">'
        . '<button style="background:#0d9488;color:white;border:0;padding:12px 22px;border-radius:10px;font-size:16px;cursor:pointer">شروع نصب</button></form>');
}

$output = [];
$status = 200;

try {
    Db::configure([
        'host' => (string) Env::get('DB_HOST', 'localhost'),
        'port' => Env::int('DB_PORT', 3306),
        'database' => (string) Env::get('DB_DATABASE', ''),
        'username' => (string) Env::get('DB_USERNAME', ''),
        'password' => (string) Env::get('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ]);

    Db::pdo();
    $output[] = ['ok', 'اتصال به پایگاه داده برقرار شد.'];

    $files = [
        BASE_PATH . '/database/migrations.sql' => 'ساخت جدول‌ها',
        BASE_PATH . '/database/seeds.sql' => 'درج داده‌های اولیه',
    ];

    foreach ($files as $file => $label) {
        if (!is_file($file)) {
            throw new RuntimeException('فایل یافت نشد: ' . $file);
        }

        $statements = SqlScript::split((string) file_get_contents($file));
        $count = 0;

        foreach ($statements as $index => $statement) {
            try {
                Db::pdo()->exec($statement);
                $count++;
            } catch (Throwable $e) {
                throw new RuntimeException($label . ' — خطا در دستور شماره ' . ($index + 1) . ': ' . $e->getMessage());
            }
        }

        $output[] = ['ok', $label . ' (' . fa_count($count) . ' دستور) انجام شد.'];
    }

    $installedAt = Db::scalar("SELECT `value` FROM settings WHERE `key` = 'installed_at'", [], '');
    $adminPassword = null;

    if ((string) $installedAt === '') {
        $admin = Db::one("SELECT id FROM users WHERE username = 'admin' LIMIT 1");

        if ($admin === null) {
            $adminId = Db::insert('users', [
                'username' => 'admin',
                'password_hash' => password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT, ['cost' => 12]),
                'role' => 'super_admin',
                'full_name' => 'مدیر کل فروشگاه',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $adminId = (int) $admin['id'];
        }

        $adminPassword = 'Av' . random_int(10000000, 99999999) . '!' . random_int(100, 999);
        Db::update('users', [
            'password_hash' => password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]),
            'role' => 'super_admin',
            'status' => 'active',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$adminId]);

        Db::query(
            "INSERT INTO settings (`key`, `value`) VALUES ('installed_at', ?)
             ON DUPLICATE KEY UPDATE `value` = ?",
            [date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
        );

        $output[] = ['ok', 'ادمین اولیه ساخته شد.'];
    }

    page('نصب موفق', '<h1>نصب با موفقیت انجام شد</h1><ul>'
        . implode('', array_map(static fn ($row) => '<li class="' . ($row[0] ?? 'ok') . '">' . h($row[1] ?? '') . '</li>', $output))
        . '</ul>'
        . ($adminPassword !== null
            ? '<div class="warn"><strong>اطلاعات ورود ادمین (فقط یک بار نمایش داده می‌شود):</strong><br>'
              . 'نام کاربری: <code>admin</code><br>رمز عبور: <code>' . h($adminPassword) . '</code><br>'
              . 'این رمز را در جای امن ذخیره و سپس تغییر دهید.</div>'
            : '<p>نصب قبلاً انجام شده است؛ رمز ادمین تغییر داده نشد.</p>')
        . '<p class="err"><strong>اکنون فایل <code>public_html/run_migration.php</code> را حذف کنید.</strong></p>'
        . '<p>سپس وارد <a style="color:#5eead4" href="/admin/login">پنل مدیریت</a> شوید.</p>');
} catch (Throwable $e) {
    $status = 500;
    page('خطای نصب', '<h1 class="err">نصب ناموفق بود</h1><p>' . h($e->getMessage()) . '</p>'
        . '<p>اطلاعات اتصال دیتابیس و دسترسی‌ها را بررسی کنید. خطا در فایل لاگ نیز ثبت می‌شود.</p>');
}

function fa_count(int $number): string
{
    return strtr((string) $number, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}
