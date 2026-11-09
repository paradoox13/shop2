<?php

declare(strict_types=1);

/**
 * تست‌های واحد بدون وابستگی به دیتابیس.
 * اجرا: php tests/run_tests.php
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Support/Autoloader.php';
\App\Support\Autoloader::register(BASE_PATH . '/app');
require BASE_PATH . '/app/Support/helpers.php';
require BASE_PATH . '/lib/jdf.php';

\App\Support\Env::load(BASE_PATH . '/config/env.php');
\App\Support\Config::load(require BASE_PATH . '/config/config.php');
date_default_timezone_set('Asia/Tehran');
\App\Support\Cache::init((string) config('paths.storage') . '/cache');

use App\Enums\OrderStatus;
use App\Services\ImportService;
use App\Services\NumberFilterService;
use App\Support\Cache;
use App\Support\Jalali;
use App\Support\Str;
use App\Support\Totp;
use App\Support\Validator;
use App\Support\XlsxReader;

$passed = 0;
$failed = 0;

function ok(bool $condition, string $label, string $detail = ''): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "PASS  {$label}\n";

        return;
    }

    $failed++;
    echo "FAIL  {$label}" . ($detail !== '' ? " :: {$detail}" : '') . "\n";
}

function eq(mixed $expected, mixed $actual, string $label): void
{
    ok($expected === $actual, $label, 'expected=' . var_export($expected, true) . ' actual=' . var_export($actual, true));
}

function section(string $title): void
{
    echo "\n== {$title} ==\n";
}

// ---------------------------------------------------------------- Jalali
section('تاریخ شمسی');

eq([2026, 11, 8], Jalali::toGregorian(1405, 8, 17), 'تبدیل 1405/08/17 به میلادی');
eq([1405, 8, 17], Jalali::toJalali(2026, 11, 8), 'تبدیل 2026-11-08 به شمسی');
eq('2026-11-08', Jalali::parse('1405/08/17'), 'پارس تاریخ شمسی');
eq('2026-11-08', Jalali::parse('۱۴۰۵/۰۸/۱۷'), 'پارس ارقام فارسی');
eq('1991-08-03', Jalali::parse('1370/05/12'), 'پارس 1370/05/12');
eq('1991-08-03', Jalali::parse('1991-08-03'), 'پارس تاریخ میلادی');
eq('1405/08/17', Jalali::format('2026-11-08'), 'قالب‌بندی شمسی');
eq('—', Jalali::format(null), 'نمایش جای خالی تاریخ');
eq(null, Jalali::parse('1405/13/01'), 'رد ماه ۱۳');
eq(null, Jalali::parse('تاریخ نامعتبر'), 'رد ورودی نامعتبر');
eq(['1405', '08', '17'], [explode('/', Jalali::format('2026-11-08'))[0], explode('/', Jalali::format('2026-11-08'))[1], explode('/', Jalali::format('2026-11-08'))[2]], 'اجزای تاریخ شمسی');

// ---------------------------------------------------------------- TOTP
section('TOTP / 2FA');

$rfcSecret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
eq('287082', Totp::code($rfcSecret, 1), 'بردار تست RFC 6238 (T=1)');
eq('081804', Totp::code($rfcSecret, 37037036), 'بردار تست RFC 6238 (T=37037036)');
ok(Totp::verify($rfcSecret, Totp::code($rfcSecret)), 'تأیید کد جاری');
ok(!Totp::verify($rfcSecret, '000000'), 'رد کد نادرست');
$secret = Totp::generateSecret();
eq(32, strlen($secret), 'طول کلید Base32');
ok(str_contains(Totp::provisioningUri('AvalSim', 'admin', $secret), 'otpauth://totp/'), 'ساخت URI برنامه Authenticator');
ok(str_contains(Totp::provisioningUri('AvalSim', 'admin', $secret), 'secret=' . $secret), 'وجود کلید در URI');

// ---------------------------------------------------------------- Str
section('کار با رشته و موبایل');

eq('09121234567', Str::normalizeMobile('+98 912 123 4567'), 'نرمال‌سازی +98');
eq('09121234567', Str::normalizeMobile('00989121234567'), 'نرمال‌سازی 0098');
eq('09121234567', Str::normalizeMobile('9121234567'), 'نرمال‌سازی بدون صفر');
eq(null, Str::normalizeMobile('12345'), 'رد موبایل کوتاه');
eq('0912', Str::toEnglishDigits('۰۹۱۲'), 'تبدیل ارقام فارسی به انگلیسی');
eq('۱۴۰۵', Str::toPersianDigits('1405'), 'تبدیل ارقام انگلیسی به فارسی');
eq('0912***567', Str::maskMobile('09121234567'), 'ماسک موبایل');
eq('سلام…', Str::limit('سلام دنیا', 4), 'محدود کردن متن');

// ---------------------------------------------------------------- Validator
section('اعتبارسنجی');

$valid = Validator::make([
    'phone' => '+989121234567',
    'birth' => '1405/08/17',
    'email' => 'user@example.com',
    'terms' => '1',
    'password' => 'StrongPass1',
    'password_confirmation' => 'StrongPass1',
], [
    'phone' => 'required|mobile',
    'birth' => 'required|jalali_date',
    'email' => 'required|email',
    'terms' => 'required|in:1',
    'password' => 'required|min:8|confirmed',
]);
ok(!$valid->fails(), 'داده‌های معتبر رد نمی‌شوند', implode(',', array_keys($valid->errors())));

$invalid = Validator::make([
    'phone' => '12345',
    'birth' => '1405/13/40',
    'email' => 'not-an-email',
], [
    'phone' => 'required|mobile',
    'birth' => 'required|jalali_date',
    'email' => 'required|email',
]);
ok($invalid->fails(), 'داده‌های نامعتبر رد می‌شوند');
ok(isset($invalid->errors()['phone']), 'خطای موبایل');
ok(isset($invalid->errors()['birth']), 'خطای تاریخ شمسی');
ok(isset($invalid->errors()['email']), 'خطای ایمیل');

$missing = Validator::make([], ['name' => 'required|min:3']);
ok($missing->fails() && isset($missing->errors()['name']), 'فیلد الزامی');

// ---------------------------------------------------------------- Filter
section('فیلتر پیشرفته شماره');

[$where, $params, $order] = NumberFilterService::build([
    'birth_date' => '1370/05/12',
    'birth_match' => 1,
    'birth_year' => '1370',
    'birth_month' => 5,
    'birth_day' => 12,
    'has_birth' => 1,
    'min_price' => 1000000,
    'max_price' => 5000000,
    'prefix' => '912',
    'suffix' => '888',
    'q' => '0912',
    'pattern' => 'round',
    'sort' => 'price_desc',
]);

ok(str_contains($where, 'p.birth_date = ?'), 'شرط تاریخ تولد دقیق');
ok(str_contains($where, 'p.birth_jy = ?'), 'شرط سال تولد شمسی');
ok(str_contains($where, 'p.birth_jm = ?'), 'شرط ماه تولد');
ok(str_contains($where, 'p.birth_jd = ?'), 'شرط روز تولد');
ok(str_contains($where, 'p.birth_date IS NOT NULL'), 'شرط داشتن تاریخ تولد');
ok(str_contains($where, 'REGEXP'), 'الگوی رند');
ok(in_array('1991-08-03', $params, true), 'پارامتر تاریخ تولد میلادی');
ok(in_array('0912%', $params, true), 'نرمال‌سازی پیش‌شماره 912 به 0912');
ok(in_array('%888', $params, true), 'فیلتر پسوند');
ok(in_array(1000000, $params, true) && in_array(5000000, $params, true), 'بازه قیمت');
ok(in_array('%700512%', $params, true) && in_array('%0512%', $params, true), 'تطابق ارقام تاریخ تولد');
eq('p.price DESC, p.id DESC', $order, 'مرتب‌سازی گران‌ترین');

[$injWhere, $injParams] = NumberFilterService::build(['q' => "' OR 1=1 --"]);
ok(!str_contains($injWhere, 'OR 1=1'), 'جلوگیری از تزریق در جستجو', $injWhere);
ok($injWhere === 'p.number LIKE ?', 'تبدیل ورودی مخرب به جستجوی عددی', $injWhere);
ok(str_contains((string) ($injParams[0] ?? ''), '%'), 'پارامتر جستجو آماده');

[$safeWhere, $safeParams, $safeOrder] = NumberFilterService::build([]);
eq('1 = 1', $safeWhere, 'شرط پیش‌فرض امن');
eq([], $safeParams, 'بدون پارامتر اضافی');
eq('p.created_at DESC, p.id DESC', $safeOrder, 'مرتب‌سازی پیش‌فرض');

// ---------------------------------------------------------------- Cache
section('کش فایل‌سیستم');

Cache::forget('test:unit');
Cache::put('test:unit', ['a' => 1, 'fa' => 'آوال'], 60);
eq(['a' => 1, 'fa' => 'آوال'], Cache::get('test:unit'), 'نوشتن و خواندن کش');
ok(Cache::has('test:unit'), 'وجود کلید کش');
$calls = 0;
$value = Cache::remember('test:remember', 60, static function () use (&$calls): string {
    $calls++;
    return 'value-' . $calls;
});
$value2 = Cache::remember('test:remember', 60, static function () use (&$calls): string {
    $calls++;
    return 'value-' . $calls;
});
eq('value-1', $value, 'مقدار remember اول');
eq($value, $value2, 'استفاده از کش در remember');
eq(1, $calls, 'فراخوانی callback فقط یک‌بار');
Cache::forget('test:remember');
eq(null, Cache::get('test:remember'), 'حذف کلید کش');
Cache::put('test:expire', 'x', 1);
usleep(2_100_000);
eq(null, Cache::get('test:expire'), 'انقضای TTL کش');
Cache::forget('test:unit');
Cache::forget('test:expire');

// ---------------------------------------------------------------- Order status
section('وضعیت سفارش');

eq('پرداخت شده', OrderStatus::Paid->label(), 'برچسب پرداخت شده');
ok(OrderStatus::Pending->canTransitionTo(OrderStatus::Paid), 'pending → paid');
ok(OrderStatus::Pending->canTransitionTo(OrderStatus::Canceled), 'pending → canceled');
ok(!OrderStatus::Pending->canTransitionTo(OrderStatus::Sent), 'pending ↛ sent');
ok(OrderStatus::Paid->canTransitionTo(OrderStatus::Completed), 'paid → completed');
ok(!OrderStatus::Completed->canTransitionTo(OrderStatus::Paid), 'completed ↛ paid');
ok(!OrderStatus::Refunded->isPaid(), 'refunded جزو وضعیت‌های پرداخت‌شده نیست');
ok(OrderStatus::Paid->isPaid(), 'paid وضعیت پرداخت‌شده است');

// ---------------------------------------------------------------- helpers
section('توابع نمایش');

eq('۱,۲۳۴,۵۶۷ تومان', money(1234567), 'قالب مبلغ تومان');
eq('۰', price(0), 'قالب قیمت صفر');
eq('۲.۰ کیلوبایت', format_bytes(2048), 'قالب حجم فایل');
eq('۰۹۱۲ ۱۲۳ ۴۵۶۷', format_phone('09121234567'), 'قالب شماره تماس');
eq('abcde…', truncate('abcdefghij', 5), 'کوتاه‌سازی متن');

// ---------------------------------------------------------------- XLSX
section('خواننده XLSX');

$tmpXlsx = sys_get_temp_dir() . '/aval-xlsx-test-' . bin2hex(random_bytes(4)) . '.xlsx';
$zip = new ZipArchive();

if ($zip->open($tmpXlsx, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>');
    $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="4" uniqueCount="4"><si><t>شماره</t></si><si><t>قیمت</t></si><si><t>09121234567</t></si><si><t>همراه اول</t></si></sst>');
    $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c></row><row r="2"><c r="A2" t="s"><v>2</v></c><c r="B2"><v>1200000</v></c><c r="C2" t="s"><v>3</v></c></row></sheetData></worksheet>');
    $zip->close();

    $rows = iterator_to_array((new XlsxReader($tmpXlsx))->rows());
    eq(['شماره', 'قیمت'], $rows[0] ?? [], 'سرستون XLSX');
    eq('09121234567', $rows[1][0] ?? null, 'متن XLSX');
    eq('1200000', $rows[1][1] ?? null, 'عدد XLSX');
    eq('همراه اول', $rows[1][2] ?? null, 'ستون سوم XLSX');
} else {
    ok(false, 'ساخت فایل XLSX آزمایشی');
}

@unlink($tmpXlsx);

$template = (new ImportService())->csvTemplate('number');
ok(str_contains($template, 'شماره') && str_contains($template, 'قیمت'), 'قالب CSV شماره');
ok(str_contains($template, '09121234567'), 'ردیف نمونه قالب CSV');

// ---------------------------------------------------------------- summary
echo "\n" . str_repeat('-', 40) . "\n";
echo 'passed=' . $passed . ' failed=' . $failed . "\n";
exit($failed === 0 ? 0 : 1);
