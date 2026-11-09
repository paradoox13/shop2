# راهنمای دیپلوی آوال سیم روی هاست اشتراکی DirectAdmin

این راهنما برای هاست اشتراکی **DirectAdmin + Apache 2.4 + PHP 8.2 + MariaDB 10.11** نوشته شده است. پروژه نه به Composer، نه SSH و نه Git نیاز دارد.

---

## ۱. پیش‌نیازها

در DirectAdmin → **PHP Selector / Select PHP Version**:

- PHP **8.2** (حداقل 8.2)
- افزونه‌ها: `pdo_mysql`، `mbstring`، `json`، `curl`، `zip`، `gd`، `fileinfo`، `openssl`، `xml`، `simplexml`
- `max_execution_time` حداقل **۳۰** ثانیه
- `upload_max_filesize` و `post_max_size` حداقل **۸M** (برای فایل ایمپورت تا ۵M)
- `memory_limit` حداقل **128M**
- `open_basedir` باید مسیر پروژه و `/tmp` را شامل شود

بررسی سریع با یک فایل موقت `phpinfo.php` (بعد از بررسی حذف کنید) یا از صفحه **PHP Info** در DirectAdmin.

---

## ۲. ساخت دیتابیس

1. DirectAdmin → **MySQL Management** → **Create new Database**
2. نام دیتابیس، کاربر و رمز قوی بسازید. نام‌ها معمولاً پیشوند کاربر هاست را می‌گیرند؛ مثلاً `myuser_avalsim`.
3. charset را **utf8mb4** انتخاب کنید.
4. کاربر را با دسترسی کامل (ALL) به همان دیتابیس متصل کنید.
5. اطلاعات را در `config/env.php` وارد کنید.

> کاربر دیتابیس را به کل سرور دسترسی ندهید؛ فقط همان دیتابیس.

---

## ۳. آپلود فایل‌ها

### حالت توصیه‌شده: DocumentRoot مستقل

کل پروژه را در مسیری خارج از `public_html` آپلود کنید:

```text
/home/USER/domains/example.com/avalsim/
├── app/ config/ cron/ database/ lib/ storage/ views/ docs/ tests/
├── public_html/          ← فقط این پوشه باید وب‌ریشه باشد
│   ├── index.php
│   ├── .htaccess
│   ├── assets/
│   └── cron/run.php
├── README.md
└── dev_router.php        ← فقط توسعه، آپلود نکنید
```

سپس در DirectAdmin → **Domain Setup** → DocumentRoot را روی:

```text
/home/USER/domains/example.com/avalsim/public_html
```

قرار دهید.

### اگر DocumentRoot قابل تغییر نیست

در بسیاری از هاست‌های DirectAdmin، DocumentRoot ثابت `public_html` است. در این حالت ساختار را این‌گونه بچینید:

```text
/home/USER/domains/example.com/
├── public_html/          ← محتویات پوشه public_html پروژه
├── app/
├── config/
├── cron/
├── database/
├── lib/
├── storage/
├── views/
└── ...
```

یعنی **محتوای** `public_html` پروژه را داخل `public_html` هاست بریزید و بقیه پوشه‌ها را کنار آن قرار دهید. مسیرها در `public_html/index.php` با `dirname(__DIR__)` ساخته می‌شوند و درست کار می‌کنند.

> پوشه‌های `app`, `config`, `storage`, `database`, `tests`, `docs` هرگز نباید داخل وب‌ریشه باشند. فایل `run_migration.php` و `dev_router.php` هم پس از نصب باید حذف شوند.

### فایل‌هایی که نباید آپلود شوند

- `dev_router.php` (فقط اجرای محلی)
- `tests/seed_dev_fixtures.php` و `tests/smoke_http.php` (داده/تست توسعه)
- `config/env.php` نمونه‌ی محلی؛ فایل خودتان را بسازید
- `storage/logs/*`، `storage/sessions/*`، `storage/backups/*`
- هر فایل `.sql` یا `.md` اضافی در وب‌ریشه

---

## ۴. ساخت `config/env.php`

از `config/env.php.example` کپی بگیرید:

```bash
cp config/env.php.example config/env.php
```

مقادیر را پر کنید. حداقل موارد الزامی:

```php
'APP_ENV' => 'production',
'APP_DEBUG' => false,
'APP_URL' => 'https://example.com',
'APP_KEY' => 'یک-رشته-تصادفی-حداقل-32-کاراکتری',
'DB_HOST' => 'localhost',
'DB_PORT' => 3306,
'DB_DATABASE' => 'myuser_avalsim',
'DB_USERNAME' => 'myuser_avalsim',
'DB_PASSWORD' => 'رمز-دیتابیس',
'MIGRATION_TOKEN' => 'توکن-موقت-تصادفی',
'CRON_TOKEN' => 'توکن-تصادفی-دیگر',
'PAYMENT_DRIVER' => 'zarinpal',
'FAKE_GATEWAY_ENABLED' => false,
'SESSION_SECURE' => true,
```

ساخت `APP_KEY` بدون SSH: یک رشته تصادفی ۶۴ کاراکتری از مدیر رمز عبور یا سایت تولید UUID بردارید. **هرگز کلید نمونه را در production استفاده نکنید.**

> `config/env.php` حاوی رمزهاست. دسترسی فایل را روی `600` بگذارید و از گیت خارج نگه دارید (در `.gitignore` هست).

---

## ۵. مجوز پوشه‌ها

```text
storage/                 775
storage/cache/           775
storage/logs/            775
storage/sessions/        775
storage/backups/         775
storage/imports/         775
public_html/assets/uploads/  775
config/env.php           600
```

در DirectAdmin → **File Manager** → **Permissions**. مالک فایل‌ها باید کاربر هاست باشد، نه `nobody`.

---

## ۶. اجرای Migration

1. `public_html/run_migration.php` را آپلود کنید (اگر آپلود نکرده‌اید).
2. `MIGRATION_TOKEN` را در `config/env.php` تنظیم کنید.
3. آدرس زیر را باز کنید:

```text
https://example.com/run_migration.php?token=TOKEN
```

خروجی باید شامل `migrate: 23 statements` و `seed: 8 statements` و نام کاربری ادمین باشد. **رمز ادمین به‌صورت تصادفی تولید و یک‌بار نمایش داده می‌شود؛ همان لحظه ذخیره کنید.**

4. بلافاصله فایل را حذف کنید:

```text
public_html/run_migration.php → Delete
```

5. اگر migration دوباره اجرا شود، جداول موجود دست نمی‌خورند (`CREATE TABLE IF NOT EXISTS`)، اما seed ممکن است داده‌های تکراری نسازد. با این حال فایل را نگه ندارید.

> در صورت خطای `Access denied`، اطلاعات دیتابیس را بررسی کنید. در صورت خطای `open_basedir`، مسیر `storage` را به open_basedir اضافه کنید.

---

## ۷. ورود اولیه

- پنل مدیریت: `https://example.com/admin/login`
- نام کاربری: `admin`
- رمز: همان رمزی که migration نمایش داد

کارهای فوری پس از ورود:

1. تغییر رمز ادمین (یا ساخت ادمین جدید و حذف/غیرفعال‌سازی قبلی)
2. فعال‌سازی **2FA** از `/admin/settings/2fa`
3. پر کردن **تنظیمات** (نام سایت، تلفن، آدرس، شبکه‌های اجتماعی، متن صفحات)
4. تعیین قیمت **کد فعال‌سازی** برای هر اپراتور
5. افزودن اولین شماره‌ها/مودم‌ها یا ساخت نماینده و ایمپورت اکسل

---

## ۸. تنظیم درگاه پرداخت

در `config/env.php`:

```php
'PAYMENT_DRIVER' => 'zarinpal', // یا idpay / nextpay
```

کلیدها:

- زرین‌پال: `ZARINPAL_MERCHANT_ID`، `ZARINPAL_SANDBOX`
- آیدی‌پی: `IDPAY_API_KEY`، `IDPAY_SANDBOX`
- نکست‌پی: `NEXTPAY_API_KEY`

سپس در پنل مدیریت → تنظیمات، درگاه فعال را هم انتخاب کنید (یا خالی بگذارید تا از env خوانده شود).

آدرس Callback که باید در پنل درگاه ثبت شود:

```text
https://example.com/payment/callback/zarinpal
https://example.com/payment/callback/idpay
https://example.com/payment/callback/nextpay
```

نکات مهم:

- مبلغ ارسالی به درگاه‌های ایرانی **ریال** است؛ کد به‌صورت خودکار تومان × ۱۰ ارسال می‌کند.
- درگاه `fake` فقط در `APP_ENV=local/development` و با `FAKE_GATEWAY_ENABLED=true` کار می‌کند و در production مسدود است.
- پس از تنظیم، یک خرید آزمایشی واقعی با مبلغ کم انجام دهید و صفحه سفارش را بررسی کنید.
- Callback امن و Idempotent است؛ اگر کاربر صفحه را رفرش کند، سفارش دوباره پرداخت نمی‌شود.

---

## ۹. تنظیم پیامک

در `config/env.php`:

```php
'SMS_PROVIDER' => 'kavenegar', // یا melipayamak
'KAVENEGAR_API_KEY' => '...',
'KAVENEGAR_SENDER' => '...',
```

یا برای ملی‌پیامک `MELIPAYAMAK_USERNAME`, `MELIPAYAMAK_PASSWORD`, `MELIPAYAMAK_SENDER`.

- ارسال پیامک در مسیر درخواست وب انجام نمی‌شود؛ در جدول `sms_logs` و `jobs` صف می‌شود و Cron آن را می‌فرستد.
- در پنل مدیریت → پیامک‌ها می‌توانید وضعیت و پاسخ سرویس را ببینید و ناموفق‌ها را Retry کنید.
- برای جلوگیری از هزینه ناخواسته، متن پیامک‌ها را کوتاه نگه دارید و اعتبار پنل پیامک را مانیتور کنید.

---

## ۱۰. Cron

### روش الف: Cron داخلی DirectAdmin (توصیه‌شده)

DirectAdmin → **Advanced Features → Cron Jobs**. مسیر PHP را از **PHP Selector** یا `php -v` بگیرید؛ نمونه:

```cron
# آزادسازی رزروهای منقضی — هر ۵ دقیقه
*/5 * * * * /usr/local/bin/php /home/USER/domains/example.com/avalsim/cron/run.php release_reservations >> /home/USER/domains/example.com/avalsim/storage/logs/cron-cli.log 2>&1

# ارسال پیامک‌های صف — هر دقیقه
* * * * * /usr/local/bin/php /home/USER/domains/example.com/avalsim/cron/run.php sms_worker >> /home/USER/domains/example.com/avalsim/storage/logs/cron-cli.log 2>&1

# بررسی پرداخت‌های معلق — هر ۱۰ دقیقه
*/10 * * * * /usr/local/bin/php /home/USER/domains/example.com/avalsim/cron/run.php reconcile_payments >> /home/USER/domains/example.com/avalsim/storage/logs/cron-cli.log 2>&1

# ادامه ایمپورت‌های اکسل — هر ۲ دقیقه
*/2 * * * * /usr/local/bin/php /home/USER/domains/example.com/avalsim/cron/run.php import_worker >> /home/USER/domains/example.com/avalsim/storage/logs/cron-cli.log 2>&1

# بکاپ روزانه — ساعت ۳ بامداد
0 3 * * * /usr/local/bin/php /home/USER/domains/example.com/avalsim/cron/run.php backup_db >> /home/USER/domains/example.com/avalsim/storage/logs/cron-cli.log 2>&1

# پاکسازی — ساعت ۴ بامداد
0 4 * * * /usr/local/bin/php /home/USER/domains/example.com/avalsim/cron/run.php cleanup >> /home/USER/domains/example.com/avalsim/storage/logs/cron-cli.log 2>&1
```

مسیر PHP را با `which php` یا از DirectAdmin → PHP Selector کپی کنید. اگر مسیر `/usr/local/bin/php` وجود نداشت، از مسیر نسخه 8.2 مثل `/usr/local/php82/bin/php` استفاده کنید.

### روش ب: Cron از طریق URL (بدون SSH)

اگر امکان Cron داخلی ندارید، از سرویس Cron خارجی یا DirectAdmin URL Cron استفاده کنید:

```text
https://example.com/cron/run.php?task=release_reservations&token=CRON_TOKEN
https://example.com/cron/run.php?task=sms_worker&token=CRON_TOKEN
https://example.com/cron/run.php?task=reconcile_payments&token=CRON_TOKEN
https://example.com/cron/run.php?task=import_worker&token=CRON_TOKEN
https://example.com/cron/run.php?task=backup_db&token=CRON_TOKEN
https://example.com/cron/run.php?task=cleanup&token=CRON_TOKEN
```

- `CRON_TOKEN` را قوی و تصادفی بگذارید.
- حتماً HTTPS باشد.
- توکن را در URL عمومی، اسکرین‌شات یا مستندات ننویسید.
- خروجی JSON شامل `ok` و نتیجه است؛ در سرویس Cron لاگ کنید.
- این endpoint فقط با توکن صحیح کار می‌کند (خطای 403 در غیر این صورت).

> فایل `cron/run.php` در ریشه پروژه و `public_html/cron/run.php` پوشش وب آن است. اگر ساختار «DocumentRoot ثابت» را انتخاب کرده‌اید، مطمئن شوید `public_html/cron/run.php` وجود دارد و `cron/run.php` در مسیر والد آن است.

---

## ۱۱. تست پس از دیپلوی

1. صفحه اصلی، جستجو، فیلتر تاریخ تولد و صفحه محصول
2. ثبت‌نام و ورود مشتری
3. سبد خرید → Checkout → درگاه → بازگشت → صفحه سفارش
4. بررسی وضعیت در پنل ادمین (سفارش، شماره فروخته‌شده، لاگ عملیات)
5. ورود نماینده با کاربر ساخته‌شده توسط ادمین
6. آپلود یک فایل CSV کوچک و پردازش ۱۰۰ ردیفی
7. اجرای یک‌باره هر Cron و بررسی `storage/logs/cron.log`
8. فعال‌سازی 2FA و تست ورود
9. ارسال فرم تماس و مشاهده پیام در پنل
10. دانلود بکاپ از `storage/backups` (یا بررسی وجود فایل)

---

## ۱۲. بکاپ و بازیابی

- Cron روزانه با `backup_db.php` یک فایل `backup-<تاریخ شمسی YYYYMMDD>-<ساعت HHMMSS>.sql.gz` در `storage/backups` می‌سازد و فقط ۵ نسخه آخر را نگه می‌دارد.
- این فایل را **خارج از هاست** (Google Drive، Dropbox یا فضای ابری) هم کپی کنید.
- بازیابی با phpMyAdmin: فایل `.gz` را باز کنید، SQL را در دیتابیس import کنید. ترتیب جداول در فایل رعایت شده است.
- قبل از هر بازیابی، از دیتابیس فعلی بکاپ بگیرید.

---

## ۱۳. عیب‌یابی سریع

| مشکل | بررسی |
|---|---|
| 500 در همه صفحات | `storage/logs/app-*.log`، دسترسی `storage`، مقدار `APP_KEY` |
| صفحه سفید | `APP_DEBUG=true` موقتاً، لاگ PHP در DirectAdmin |
| خطای دیتابیس | `config/env.php`، دسترسی کاربر DB، charset `utf8mb4` |
| 419 هنگام ثبت فرم | کوکی/Session، `SESSION_SECURE` در HTTPS، ساعت سرور |
| هدایت به درگاه ناموفق | کلید درگاه، `APP_URL`، Callback ثبت‌شده |
| پیامک ارسال نمی‌شود | `SMS_PROVIDER`، اعتبار پنل، اجرای `sms_worker`، جدول `jobs` |
| ایمپورت ادامه پیدا نمی‌کند | `import_worker` و مجوز `storage/imports` |
| Cron وب 403 | `CRON_TOKEN` در env و URL |
| فایل آپلود نمی‌شود | `upload_max_filesize`، `post_max_size`، مجوز `assets/uploads` |

---

## ۱۴. به‌روزرسانی نسخه

1. از فایل‌ها و دیتابیس بکاپ بگیرید.
2. فایل‌های جدید را جایگزین کنید (به‌جز `config/env.php` و `storage/`).
3. اگر migration جدید وجود دارد، فقط دستورات جدید را در phpMyAdmin اجرا کنید.
4. کش را پاک کنید: پوشه `storage/cache` را خالی کنید (نه پوشه را حذف کنید).
5. تست‌های بخش ۱۱ را انجام دهید.

---

## ۱۵. چک‌لیست نهایی

- [ ] `APP_ENV=production` و `APP_DEBUG=false`
- [ ] `APP_KEY` تصادفی و یکتا
- [ ] HTTPS و `SESSION_SECURE=true`
- [ ] `run_migration.php` حذف شده
- [ ] `dev_router.php` و فایل‌های تست توسعه آپلود نشده‌اند
- [ ] `FAKE_GATEWAY_ENABLED=false`
- [ ] درگاه واقعی تست شده
- [ ] 2FA ادمین فعال
- [ ] Cronها اجرا و لاگ شده‌اند
- [ ] بکاپ گرفته و خارج از هاست کپی شده
- [ ] مجوز `storage` و `config/env.php` درست است
- [ ] هیچ رمزی در گیت یا فایل عمومی نیست

برای جزئیات امنیتی، [SECURITY-CHECKLIST.md](SECURITY-CHECKLIST.md) را ببینید.
