# آوال سیم (AvalSim)

فروشگاه اینترنتی ایرانی برای خرید **شماره سیم‌کارت**، **مودم** و **کد فعال‌سازی**؛ نوشته‌شده با PHP 8.2 خالص، بدون Composer، SSH، Redis یا symlink — مناسب هاست اشتراکی DirectAdmin.

> طراحی RTL، فونت وزیرمتن، Tailwind CSS (CDN)، Alpine.js، دارک/لایت، اعداد فارسی، تاریخ شمسی و قیمت به تومان.

---

## امکانات

### فروشگاه
- صفحه اصلی با جستجو و فیلتر پیشرفته شماره: اپراتور، بازه قیمت، پیش‌شماره، پسوند، الگو (رند/تکراری/متوالی)، تعداد ارقام
- فیلتر تاریخ تولد: شمسی/میلادی، سال/ماه/روز، تطابق ارقام تاریخ با شماره، «دارای تاریخ تولد»
- صفحه جزئیات شماره و مودم، محصولات مرتبط، شمارنده بازدید
- سبد خرید Session-based، رزرو ۱۵ دقیقه‌ای شماره
- Checkout مهمان و کاربر، درگاه زرین‌پال/آی‌دی‌پی/نکست‌پی/آزمایشی
- پیگیری سفارش با کد رهگیری و دسترسی Session-based
- ثبت‌نام/ورود مشتری، ورود جداگانه نماینده و ادمین
- صفحات درباره ما، قوانین، حریم خصوصی، سوالات متداول و تماس با ما

### پنل ادمین
- داشبورد KPI، نمودار فروش ۱۴ روزه، آخرین سفارش‌ها، بکاپ‌ها
- مدیریت شماره‌ها، مودم‌ها (آپلود تصویر)، قیمت کد فعال‌سازی
- مدیریت سفارش‌ها با انتقال وضعیت کنترل‌شده و یادداشت
- مدیریت نمایندگان و درصد کمیسیون
- گزارش فروش با فیلتر تاریخ شمسی و خروجی CSV
- صف پیامک، Retry، مشاهده پاسخ سرویس
- پیام‌های تماس، تنظیمات سایت/درگاه/پیامک/صفحات ثابت
- 2FA اختیاری (TOTP)، Audit Log، لاگ ورود

### پنل نماینده
- داشبورد فروش/کمیسیون/موجودی
- مدیریت شماره‌ها و مودم‌های اختصاصی
- ایمپورت اکسل/CSV به‌صورت Chunked (۱۰۰ ردیف در هر درخواست) با ادامه خودکار توسط Cron
- گزارش فروش و تسویه با خروجی CSV

### زیرساخت
- PHP 8.2، `declare(strict_types=1)`، MVC سبک و Autoloader داخلی
- MariaDB 10.11 با Migration/Seed و اجرای یک‌باره از مرورگر
- فایل‌کش در `storage/cache`، صف در جدول `jobs`، Cron workers
- درگاه‌ها با `GatewayInterface`، Callback امن و Idempotent، Reconcile خودکار
- بکاپ PHP-only با gzip (بدون `mysqldump`/`exec`)
- امنیت: CSRF، Rate Limit، RBAC، Audit، 2FA، Prepared Statements، escape خروجی، CSP و `.htaccess`

---

## ساختار پروژه

```text
app/                هسته، کنترلرها، مدل‌ها، سرویس‌ها، Middleware
config/             config.php و env.php (env در گیت نیست)
cron/               workers و dispatcher (CLI + وب)
database/           migrations.sql و seeds.sql
docs/               راهنمای دیپلوی، چک‌لیست امنیتی، فایل‌های نمونه
lib/                jdf.php (تاریخ شمسی)
public_html/        تنها وب‌ریشه: index.php, .htaccess, assets, cron/run.php
storage/            cache, logs, sessions, imports, backups (خارج از وب)
tests/              تست واحد، smoke HTTP، ابزار ساخت داده توسعه
views/              قالب‌ها و صفحات فارسی
```

---

## اجرای محلی (اختیاری)

نیازمندی‌ها: PHP 8.2 با `pdo_mysql`, `mbstring`, `zip`, `gd`, `curl` و یک MariaDB.

```bash
cp config/env.php.example config/env.php
# DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_URL را تنظیم کنید

php -S 127.0.0.1:8080 -t public_html dev_router.php
```

سپس:

```text
http://127.0.0.1:8080/run_migration.php?token=MIGRATION_TOKEN
```

پس از نصب، `run_migration.php` را حذف کنید. برای داده نمونه توسعه می‌توانید `php tests/seed_dev_fixtures.php` را اجرا کنید (هرگز روی production اجرا نشود).

---

## تست‌ها

```bash
php tests/run_tests.php                 # تست واحد (بدون DB)
php tests/make_samples.php              # ساخت نمونه CSV/XLSX
php tests/seed_dev_fixtures.php         # داده توسعه (local فقط)
php tests/smoke_http.php http://127.0.0.1:8080   # تست HTTP کامل
```

تست HTTP شامل: خانه/فیلتر تاریخ تولد، سبد، Checkout، درگاه آزمایشی، Callback، Idempotency، CSRF، دسترسی سفارش، پنل ادمین/نماینده و ایمپورت CSV است.

Cron را می‌توانید دستی اجرا کنید:

```bash
php cron/run.php release_reservations
php cron/run.php sms_worker
php cron/run.php reconcile_payments
php cron/run.php import_worker
php cron/run.php backup_db
php cron/run.php cleanup
```

نسخه وب: `/cron/run.php?task=sms_worker&token=CRON_TOKEN`

---

## دیپلوی

راهنمای گام‌به‌گام DirectAdmin:

- [docs/DEPLOY.md](docs/DEPLOY.md)
- [docs/SECURITY-CHECKLIST.md](docs/SECURITY-CHECKLIST.md)
- فایل‌های نمونه ایمپورت: [docs/samples](docs/samples)

خلاصه:

1. دیتابیس و کاربر بسازید.
2. `config/env.php` را از نمونه بسازید و رمزها/کلیدها را وارد کنید.
3. فایل‌ها را آپلود کنید؛ فقط `public_html` وب‌ریشه باشد.
4. Migration را با توکن اجرا و فایل آن را حذف کنید.
5. Cronها را تنظیم کنید.
6. 2FA، درگاه و پیامک را پیکربندی و تست کنید.
7. چک‌لیست امنیتی را کامل کنید.

---

## نکات مهم

- رمز ادمین در Migration به‌صورت تصادفی ساخته و یک‌بار نمایش داده می‌شود؛ همان لحظه ذخیره کنید.
- درگاه `fake` در production غیرفعال است.
- کلیدهای درگاه/پیامک در `config/env.php` می‌مانند و از پنل قابل ویرایش نیستند.
- فایل‌های `dev_router.php`، `tests/seed_dev_fixtures.php` و `tests/smoke_http.php` فقط توسعه‌ای هستند و نباید روی هاست آپلود شوند.
- برای تغییرات دیتابیس، همیشه بکاپ بگیرید و Migration جدید را جداگانه اجرا کنید.

---

## مجوز

این پروژه برای استفاده اختصاصی تهیه شده است. استفاده مجدد، فروش یا انتشار کد بدون اجازه مالک مجاز نیست.
