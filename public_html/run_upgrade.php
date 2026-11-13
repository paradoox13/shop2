<?php

declare(strict_types=1);

/**
 * ارتقای یک‌بارهٔ دیتابیس نصب‌شده به نسخهٔ دسته‌بندی شماره‌ها.
 * دسترسی: https://YOUR-DOMAIN/run_upgrade.php?token=YOUR_MIGRATION_TOKEN
 * پس از اجرای موفق این فایل را حذف کنید.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Support/Autoloader.php';
App\Support\Autoloader::register(BASE_PATH . '/app');

use App\Support\Db;
use App\Support\Env;

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function page(string $title, string $body, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . h($title) . '</title>'
        . '<style>body{font-family:Tahoma,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:40px 16px;line-height:1.9}'
        . '.box{max-width:820px;margin:auto;background:#1e293b;border:1px solid #334155;border-radius:16px;padding:28px}'
        . 'h1{color:#5eead4;margin-top:0}.ok{color:#6ee7b7}.err{color:#fda4af}code{background:#0f172a;padding:2px 8px;border-radius:6px;direction:ltr;display:inline-block}'
        . 'li{margin-bottom:6px}.warn{background:#78350f;border:1px solid #b45309;padding:14px;border-radius:10px;margin:16px 0}'
        . 'button{background:#0d9488;color:white;border:0;padding:12px 22px;border-radius:10px;font-size:16px;cursor:pointer}</style></head>'
        . '<body><div class="box">' . $body . '</div></body></html>';
    exit;
}

$envFile = BASE_PATH . '/config/env.php';

if (!is_file($envFile)) {
    page('نصب نشده', '<h1 class="err">تنظیمات یافت نشد</h1><p>ابتدا <code>config/env.php</code> را بسازید.</p>');
}

try {
    Env::load($envFile);
} catch (Throwable $e) {
    page('خطا', '<h1 class="err">خطا در تنظیمات</h1><p>' . h($e->getMessage()) . '</p>', 500);
}

$expected = (string) Env::get('MIGRATION_TOKEN', '');
$provided = (string) ($_GET['token'] ?? $_POST['token'] ?? '');

if ($expected === '' || strlen($expected) < 16) {
    page('غیرفعال', '<h1 class="err">توکن ارتقا تنظیم نشده است</h1><p>مقدار <code>MIGRATION_TOKEN</code> را در <code>config/env.php</code> تنظیم کنید.</p>');
}

if (!hash_equals($expected, $provided)) {
    page('دسترسی غیرمجاز', '<h1 class="err">دسترسی غیرمجاز</h1><p>توکن نامعتبر است.</p>', 403);
}

$run = isset($_GET['run']) || isset($_POST['run']);

if (!$run) {
    page('ارتقای دیتابیس', '<h1>ارتقای آوال سیم</h1>'
        . '<p>این عملیات فقط ستون‌های جدید دسته‌بندی شماره، نوع خط و ایمپورت مدیر را اضافه می‌کند. داده‌های موجود حفظ می‌شوند.</p>'
        . '<div class="warn">قبل از اجرا از دیتابیس بکاپ بگیرید. پس از پایان، این فایل را حذف کنید.</div>'
        . '<form method="post"><input type="hidden" name="token" value="' . h($provided) . '">'
        . '<input type="hidden" name="run" value="1"><button>اجرای ارتقا</button></form>');
}

try {
    Db::configure([
        'host' => (string) Env::get('DB_HOST', 'localhost'),
        'port' => Env::int('DB_PORT', 3306),
        'database' => (string) Env::get('DB_DATABASE', ''),
        'username' => (string) Env::get('DB_USERNAME', ''),
        'password' => (string) Env::get('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ]);

    $pdo = Db::pdo();
    $done = [];

    $columnExists = static function (string $table, string $column) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);

        return (int) $stmt->fetchColumn() > 0;
    };

    $indexExists = static function (string $table, string $index) use ($pdo): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $stmt->execute([$table, $index]);

        return (int) $stmt->fetchColumn() > 0;
    };

    if (!$columnExists('phone_numbers', 'number_type')) {
        $pdo->exec("ALTER TABLE `phone_numbers` ADD COLUMN `number_type` VARCHAR(20) NOT NULL DEFAULT 'other' AFTER `birth_jd`");
        $done[] = 'ستون دسته‌بندی شماره اضافه شد.';
    } else {
        $done[] = 'ستون دسته‌بندی شماره از قبل وجود داشت.';
    }

    if (!$columnExists('phone_numbers', 'line_type')) {
        $pdo->exec("ALTER TABLE `phone_numbers` ADD COLUMN `line_type` VARCHAR(20) NOT NULL DEFAULT 'prepaid' AFTER `number_type`");
        $done[] = 'ستون نوع خط (دائمی/اعتباری) اضافه شد.';
    } else {
        $done[] = 'ستون نوع خط از قبل وجود داشت.';
    }

    if (!$indexExists('phone_numbers', 'idx_phone_type')) {
        $pdo->exec('ALTER TABLE `phone_numbers` ADD INDEX `idx_phone_type` (`number_type`, `line_type`, `status`)');
        $done[] = 'ایندکس دسته‌بندی شماره اضافه شد.';
    }

    $resellerNullable = $pdo->query(
        "SELECT IS_NULLABLE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'import_logs' AND COLUMN_NAME = 'reseller_id'"
    )->fetchColumn();

    if (strtoupper((string) $resellerNullable) === 'NO') {
        $fk = $pdo->query(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'import_logs'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = 'fk_import_reseller' LIMIT 1"
        )->fetchColumn();

        if ($fk !== false && $fk !== null) {
            $pdo->exec('ALTER TABLE `import_logs` DROP FOREIGN KEY `fk_import_reseller`');
        }

        $pdo->exec('ALTER TABLE `import_logs` MODIFY `reseller_id` BIGINT UNSIGNED NULL');

        $pdo->exec(
            'ALTER TABLE `import_logs` ADD CONSTRAINT `fk_import_reseller`
             FOREIGN KEY (`reseller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE'
        );
        $done[] = 'ایمپورت مدیر (بدون نماینده) فعال شد.';
    } else {
        $done[] = 'ایمپورت بدون نماینده از قبل فعال بود.';
    }

    // دسته‌بندی خودکار ردیف‌های قدیمی که هنوز «سایر» هستند.
    $pdo->exec(
        "UPDATE `phone_numbers` SET `number_type` = 'birthday'
         WHERE `number_type` = 'other' AND `birth_date` IS NOT NULL"
    );
    $pdo->exec(
        "UPDATE `phone_numbers` SET `number_type` = 'serial'
         WHERE `number_type` = 'other'
           AND `number` REGEXP '(0123|1234|2345|3456|4567|6789|9876|8765|7654|6543|5432|4321|3210)'"
    );
    $pdo->exec(
        "UPDATE `phone_numbers` SET `number_type` = 'round'
         WHERE `number_type` = 'other'
           AND `number` REGEXP '(0000|1111|2222|3333|4444|5555|6666|7777|8888|9999)$'"
    );
    $done[] = 'دسته‌بندی شماره‌های قدیمی انجام شد.';

    $about = '<p>آوال سیم یک وب‌سایت فعال و معتبر در زمینه فروش انواع سیم‌کارت اعتباری و دائمی در اپراتورهای مختلف و همچنین فروش انواع مودم و کارتخوان با قیمت مناسب و کیفیت بالا بوده که همواره توانسته رضایت مشتریان خود را داشته باشد.</p>'
        . '<p>در آوال سیم می‌توانید شماره‌های رند، خانوادگی، فوق رند، سریالی و شماره‌های دارای تاریخ تولد را در اپراتورهای همراه اول، ایرانسل و رایتل بررسی و خریداری کنید. تمام شماره‌ها پیش از انتشار بررسی می‌شوند و وضعیت آن‌ها در لحظه به‌روزرسانی می‌شود.</p>'
        . '<p>هدف ما ارائه قیمت شفاف، تحویل سریع و پشتیبانی واقعی است. برای خرید عمده یا همکاری به‌عنوان نماینده با ما تماس بگیرید.</p>';

    Db::query(
        "INSERT INTO settings (`key`, `value`) VALUES ('page_about', ?)
         ON DUPLICATE KEY UPDATE `value` = IF(
             `value` IS NULL OR TRIM(`value`) = '' OR `value` LIKE '%آوال سیم فروشگاه تخصصی شماره سیم‌کارت%',
             VALUES(`value`), `value`
         )",
        [$about]
    );
    $done[] = 'محتوای نمونه صفحه درباره ما بررسی شد.';

    Db::query(
        'INSERT IGNORE INTO activation_code_prices (operator_id, price)
         SELECT id, 0 FROM operators WHERE is_active = 1'
    );
    $done[] = 'قیمت کد فعال‌سازی برای اپراتورهای جدید بررسی شد.';

    page('ارتقا موفق', '<h1>ارتقا با موفقیت انجام شد</h1><ul>'
        . implode('', array_map(static fn (string $item): string => '<li class="ok">✓ ' . h($item) . '</li>', $done))
        . '</ul><div class="warn"><strong>اکنون فایل <code>public_html/run_upgrade.php</code> را حذف کنید.</strong></div>'
        . '<p><a style="color:#5eead4" href="/">بازگشت به فروشگاه</a></p>');
} catch (Throwable $e) {
    page('خطای ارتقا', '<h1 class="err">ارتقا ناموفق بود</h1><p>' . h($e->getMessage()) . '</p>'
        . '<p>از دیتابیس بکاپ بگیرید و خطا را بررسی کنید. اگر ستون‌ها قبلاً اضافه شده‌اند، اجرای دوباره بی‌خطر است.</p>', 500);
}
