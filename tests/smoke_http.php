<?php

declare(strict_types=1);

/**
 * تست دودی HTTP (فقط محیط توسعه).
 * اجرا: php tests/smoke_http.php http://127.0.0.1:8080
 */

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$env = require __DIR__ . '/../config/env.php';
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $env['DB_HOST'], (int) $env['DB_PORT'], $env['DB_DATABASE']),
    (string) $env['DB_USERNAME'],
    (string) $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$failures = 0;
$checks = 0;

function check(bool $condition, string $label, string $extra = ''): void
{
    global $failures, $checks;
    $checks++;

    if ($condition) {
        echo "PASS  {$label}\n";
        return;
    }

    $failures++;
    echo "FAIL  {$label}" . ($extra !== '' ? " :: {$extra}" : '') . "\n";
}

/** @return array{status:int, body:string, location:string} */
function http(string $method, string $url, array $data = [], ?string $jar = null, bool $upload = false): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_USERAGENT => 'AvalSim-Smoke/1.0',
    ]);

    if ($jar !== null) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $upload ? $data : http_build_query($data));
    }

    $body = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $location = (string) (curl_getinfo($ch, CURLINFO_REDIRECT_URL) ?: '');
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === '' && $error !== '') {
        $body = 'CURL_ERROR: ' . $error;
    }

    return ['status' => $status, 'body' => $body, 'location' => $location];
}

function token(string $html): string
{
    if (preg_match('/name="_token" value="([^"]+)"/', $html, $m) === 1) {
        return $m[1];
    }

    return '';
}

function clean(string $html): bool
{
    foreach (['Fatal error', 'Uncaught', 'Parse error', 'Warning:', 'Deprecated:', 'Notice:'] as $needle) {
        if (str_contains($html, $needle)) {
            return false;
        }
    }

    return true;
}

function pageOk(array $response, string $label): void
{
    check($response['status'] === 200, $label . ' (200)', 'status=' . $response['status']);
    check(clean($response['body']), $label . ' (بدون خطای PHP)', substr(strip_tags($response['body']), 0, 300));
}

$jarPublic = sys_get_temp_dir() . '/aval-smoke-public.cookies';
$jarAdmin = sys_get_temp_dir() . '/aval-smoke-admin.cookies';
$jarReseller = sys_get_temp_dir() . '/aval-smoke-reseller.cookies';
@unlink($jarPublic);
@unlink($jarAdmin);
@unlink($jarReseller);

// ---------- فروشگاه ----------
$home = http('GET', $base . '/', [], $jarPublic);
pageOk($home, 'صفحه خانه');
check(str_contains($home['body'], 'خرید شماره سیم‌کارت'), 'بخش شماره‌ها در خانه');

$filter = http('GET', $base . '/?operator=' . (int) $pdo->query('SELECT id FROM operators ORDER BY id LIMIT 1')->fetchColumn() . '&pattern=round&birth_date=1370/05/12&birth_match=1&sort=price_asc', [], $jarPublic);
pageOk($filter, 'فیلتر پیشرفته و تاریخ تولد');

$number = $pdo->query("SELECT id, number FROM phone_numbers WHERE description = 'خرید تست HTTP' AND status = 'available' ORDER BY id DESC LIMIT 1")->fetch();
check(is_array($number), 'فیکسچر شماره خرید موجود است');
$numberId = (int) ($number['id'] ?? 0);

$detail = http('GET', $base . '/phones/' . $numberId, [], $jarPublic);
pageOk($detail, 'جزئیات شماره');

$cartToken = token($detail['body']);
check($cartToken !== '', 'استخراج CSRF از صفحه شماره');

$add = http('POST', $base . '/cart/add-number', ['_token' => $cartToken, 'id' => $numberId], $jarPublic);
check($add['status'] === 302, 'افزودن شماره به سبد', 'status=' . $add['status']);

$cart = http('GET', $base . '/cart', [], $jarPublic);
pageOk($cart, 'صفحه سبد خرید');
check(str_contains($cart['body'], (string) ($number['number'] ?? '')), 'شماره در سبد دیده می‌شود');

$checkout = http('GET', $base . '/checkout', [], $jarPublic);
pageOk($checkout, 'صفحه تکمیل خرید');
$checkoutToken = token($checkout['body']);
check($checkoutToken !== '', 'استخراج CSRF چک‌اوت');

$place = http('POST', $base . '/checkout', [
    '_token' => $checkoutToken,
    'customer_name' => 'خریدار آزمایشی',
    'customer_phone' => '09129998877',
    'customer_email' => 'buyer@test.local',
    'terms' => '1',
], $jarPublic);
check($place['status'] === 302, 'ثبت سفارش و هدایت به درگاه', 'status=' . $place['status'] . ' body=' . substr(strip_tags($place['body']), 0, 200));
check(str_contains($place['location'], '/payment/callback/fake'), 'هدایت به کال‌بک درگاه آزمایشی', $place['location']);

$gateway = http('GET', $place['location'], [], $jarPublic);
check($gateway['status'] === 302, 'پردازش کال‌بک درگاه', 'status=' . $gateway['status']);
check(str_contains($gateway['location'], '/order/'), 'هدایت به صفحه سفارش', $gateway['location']);

$orderPage = http('GET', $gateway['location'], [], $jarPublic);
pageOk($orderPage, 'صفحه سفارش پس از پرداخت');
check(str_contains($orderPage['body'], 'پرداخت با موفقیت'), 'پیام موفقیت پرداخت');
check(str_contains($orderPage['body'], (string) ($number['number'] ?? '')), 'شماره در اقلام سفارش');

$paid = $pdo->query("SELECT o.id, o.tracking_code, o.status, p.status AS phone_status
                     FROM orders o
                     INNER JOIN order_items oi ON oi.order_id = o.id
                     INNER JOIN phone_numbers p ON p.id = oi.product_id
                     WHERE p.id = " . (int) ($number['id'] ?? 0) . " ORDER BY o.id DESC LIMIT 1")->fetch();

$repeatCallback = http('GET', $place['location'], [], $jarPublic);
check($repeatCallback['status'] === 302, 'کال‌بک تکراری بدون خطا پردازش می‌شود', 'status=' . $repeatCallback['status']);
$afterRepeat = $pdo->query('SELECT status FROM orders WHERE id = ' . (int) ($paid['id'] ?? 0))->fetchColumn();
check($afterRepeat === 'paid', 'وضعیت سفارش پس از کال‌بک تکراری paid می‌ماند', (string) $afterRepeat);
$paidPayments = (int) $pdo->query('SELECT COUNT(*) FROM payments WHERE order_id = ' . (int) ($paid['id'] ?? 0) . " AND status = 'paid'")->fetchColumn();
check($paidPayments === 1, 'پرداخت تکراری ثبت نمی‌شود (Idempotency)', (string) $paidPayments);

$csrfFail = http('POST', $base . '/cart/add-number', ['id' => $numberId], $jarPublic);
check($csrfFail['status'] === 419, 'رد درخواست بدون CSRF با 419', 'status=' . $csrfFail['status']);
check(($paid['status'] ?? '') === 'paid', 'وضعیت سفارش در DB = paid', (string) ($paid['status'] ?? 'missing'));
check(($paid['phone_status'] ?? '') === 'sold', 'وضعیت شماره در DB = sold', (string) ($paid['phone_status'] ?? 'missing'));
check((int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'paid'")->fetchColumn() >= 1, 'رکورد پرداخت موفق ثبت شده');

$tracking = (string) ($paid['tracking_code'] ?? '');
$publicOrder = http('GET', $base . '/order/' . $tracking, [], $jarPublic);
check($publicOrder['status'] === 200, 'دسترسی خریدار به سفارش خودش');

$guestOrder = http('GET', $base . '/order/' . $tracking, [], sys_get_temp_dir() . '/aval-smoke-guest.cookies');
check($guestOrder['status'] === 404, 'عدم دسترسی مهمان به سفارش دیگران', 'status=' . $guestOrder['status']);

// ---------- ادمین ----------
$adminLogin = http('GET', $base . '/admin/login', [], $jarAdmin);
pageOk($adminLogin, 'صفحه ورود ادمین');
$adminToken = token($adminLogin['body']);
$adminPost = http('POST', $base . '/admin/login', ['_token' => $adminToken, 'username' => 'admin', 'password' => 'Admin@12345'], $jarAdmin);
check($adminPost['status'] === 302 && str_contains($adminPost['location'], '/admin'), 'ورود ادمین', 'status=' . $adminPost['status'] . ' loc=' . $adminPost['location']);

$adminPages = [
    '/admin' => 'داشبورد ادمین',
    '/admin/numbers' => 'مدیریت شماره‌ها',
    '/admin/numbers/create' => 'فرم شماره ادمین',
    '/admin/modems' => 'مدیریت مودم‌ها',
    '/admin/modems/create' => 'فرم مودم ادمین',
    '/admin/activation' => 'قیمت کد فعال‌سازی',
    '/admin/orders' => 'لیست سفارش‌ها',
    '/admin/orders/' . (int) ($paid['id'] ?? 0) => 'جزئیات سفارش ادمین',
    '/admin/resellers' => 'لیست نمایندگان',
    '/admin/resellers/create' => 'فرم نماینده',
    '/admin/reports' => 'گزارش فروش',
    '/admin/sms' => 'پیامک‌ها',
    '/admin/messages' => 'پیام‌های تماس',
    '/admin/settings' => 'تنظیمات',
    '/admin/settings/2fa' => 'تنظیم 2FA',
    '/admin/audit' => 'لاگ عملیات',
];

foreach ($adminPages as $path => $label) {
    pageOk(http('GET', $base . $path, [], $jarAdmin), $label);
}

check(str_contains(http('GET', $base . '/admin/reports', [], $jarAdmin)['body'], 'گزارش فروش'), 'گزارش فروش داده دارد');
check(str_contains(http('GET', $base . '/admin/sms', [], $jarAdmin)['body'], 'پیامک‌ها'), 'صفحه پیامک');

// ---------- نماینده ----------
$resellerLogin = http('GET', $base . '/reseller/login', [], $jarReseller);
pageOk($resellerLogin, 'صفحه ورود نماینده');
$resellerToken = token($resellerLogin['body']);
$resellerPost = http('POST', $base . '/reseller/login', ['_token' => $resellerToken, 'username' => 'reseller1', 'password' => 'Reseller@12345'], $jarReseller);
check($resellerPost['status'] === 302 && str_contains($resellerPost['location'], '/reseller'), 'ورود نماینده', 'status=' . $resellerPost['status'] . ' loc=' . $resellerPost['location']);

foreach ([
    '/reseller' => 'داشبورد نماینده',
    '/reseller/numbers' => 'شماره‌های نماینده',
    '/reseller/numbers/create' => 'فرم شماره نماینده',
    '/reseller/modems' => 'مودم‌های نماینده',
    '/reseller/modems/create' => 'فرم مودم نماینده',
    '/reseller/import' => 'صفحه ایمپورت',
    '/reseller/sales' => 'گزارش فروش نماینده',
] as $path => $label) {
    pageOk(http('GET', $base . $path, [], $jarReseller), $label);
}

$salesPage = http('GET', $base . '/reseller/sales', [], $jarReseller);
check(str_contains($salesPage['body'], (string) ($number['number'] ?? '')), 'فروش نماینده در گزارش دیده می‌شود');

// ---------- ایمپورت CSV ----------
$template = http('GET', $base . '/reseller/import/template/number', [], $jarReseller);
check($template['status'] === 200 && str_contains($template['body'], 'شماره'), 'دریافت قالب CSV شماره');

$csvPath = sys_get_temp_dir() . '/aval-import-test.csv';

do {
    $importNumber = '0935' . str_pad((string) random_int(0, 9_999_999), 7, '0', STR_PAD_LEFT);
    $exists = (int) $pdo->query("SELECT COUNT(*) FROM phone_numbers WHERE number = " . $pdo->quote($importNumber))->fetchColumn();
} while ($exists > 0);

$csv = "\xEF\xBB\xBF" . "شماره,قیمت,اپراتور,تاریخ تولد,توضیحات\n"
    . $importNumber . ",1234000,ایرانسل,1372/02/03,ردیف تستی ایمپورت\n"
    . "09121234567,999000,همراه اول,,ردیف تکراری باید رد شود\n";
file_put_contents($csvPath, $csv);

$importToken = token(http('GET', $base . '/reseller/import', [], $jarReseller)['body']);
check($importToken !== '', 'استخراج CSRF تازه پس از ورود نماینده');

$upload = http('POST', $base . '/reseller/import/upload', [
    '_token' => $importToken,
    'type' => 'number',
    'file' => new CURLFile($csvPath, 'text/csv', 'import-test.csv'),
], $jarReseller, true);
check($upload['status'] === 302, 'آپلود فایل ایمپورت', 'status=' . $upload['status'] . ' body=' . substr(strip_tags($upload['body']), 0, 300));

if (preg_match('#/reseller/import/(\d+)#', $upload['location'], $m) === 1) {
    $importId = (int) $m[1];
    $importPage = http('GET', $base . '/reseller/import/' . $importId, [], $jarReseller);
    pageOk($importPage, 'صفحه پردازش ایمپورت');
    $processToken = token($importPage['body']);
    $process = http('POST', $base . '/reseller/import/' . $importId . '/process', ['_token' => $processToken], $jarReseller);
    $result = json_decode($process['body'], true);
    check($process['status'] === 200 && is_array($result), 'پاسخ JSON پردازش ایمپورت', substr($process['body'], 0, 300));
    check((int) ($result['inserted'] ?? 0) === 1, 'ایمپورت یک ردیف جدید', (string) ($result['inserted'] ?? 'n/a'));
    check((int) ($result['failed'] ?? 0) === 1, 'ردیف تکراری رد شد', (string) ($result['failed'] ?? 'n/a'));
    check(($result['status'] ?? '') === 'completed', 'وضعیت ایمپورت completed', (string) ($result['status'] ?? 'n/a'));
    check((int) $pdo->query("SELECT COUNT(*) FROM phone_numbers WHERE number = " . $pdo->quote($importNumber))->fetchColumn() === 1, 'شماره ایمپورت‌شده در DB');
} else {
    check(false, 'استخراج شناسه ایمپورت از ریدایرکت', $upload['location']);
}

echo "\n---\n";
echo 'checks=' . $checks . ' failures=' . $failures . "\n";
exit($failures === 0 ? 0 : 1);
