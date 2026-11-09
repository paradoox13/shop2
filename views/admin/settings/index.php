<?php
$s = $settings ?? [];
$input = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
$label = 'block text-xs font-medium text-slate-600 dark:text-slate-300';
$value = static fn (string $key, string $default = ''): string => (string) old($key, $s[$key] ?? $default);
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">تنظیمات فروشگاه</h2>
        <p class="mt-1 text-xs text-slate-500">تنظیمات عمومی، درگاه، پیامک و صفحات ثابت. کلیدهای محرمانه در فایل env نگهداری می‌شوند.</p>
    </div>
    <a href="<?= e(url('/admin/settings/2fa')) ?>" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[10px] font-bold hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900">🔐 تأیید دو مرحله‌ای: <?= $twoFactorEnabled ? 'فعال' : 'غیرفعال' ?></a>
</div>

<form method="post" action="<?= e(url('/admin/settings')) ?>" class="mt-4 space-y-4">
    <?= csrf_field() ?>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-sm font-bold">هویت فروشگاه</h3>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div>
                <label for="site_name" class="<?= $label ?>">نام سایت *</label>
                <input id="site_name" name="site_name" required value="<?= e($value('site_name')) ?>" class="<?= $input ?>">
            </div>
            <div>
                <label for="site_tagline" class="<?= $label ?>">شعار / تیتر اصلی *</label>
                <input id="site_tagline" name="site_tagline" required value="<?= e($value('site_tagline')) ?>" class="<?= $input ?>">
            </div>
            <div class="sm:col-span-2">
                <label for="site_description" class="<?= $label ?>">توضیحات متا (SEO)</label>
                <input id="site_description" name="site_description" value="<?= e($value('site_description')) ?>" class="<?= $input ?>">
            </div>
            <div class="sm:col-span-2">
                <label for="footer_about" class="<?= $label ?>">معرفی کوتاه در فوتر</label>
                <textarea id="footer_about" name="footer_about" rows="2" class="<?= $input ?>"><?= e($value('footer_about')) ?></textarea>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-sm font-bold">ارتباط و شبکه‌های اجتماعی</h3>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div>
                <label for="support_phone" class="<?= $label ?>">تلفن پشتیبانی</label>
                <input id="support_phone" name="support_phone" value="<?= e($value('support_phone')) ?>" class="<?= $input ?> ltr" placeholder="02112345678">
            </div>
            <div>
                <label for="support_email" class="<?= $label ?>">ایمیل پشتیبانی</label>
                <input id="support_email" name="support_email" type="email" value="<?= e($value('support_email')) ?>" class="<?= $input ?> ltr">
            </div>
            <div class="sm:col-span-2">
                <label for="address" class="<?= $label ?>">آدرس</label>
                <input id="address" name="address" value="<?= e($value('address')) ?>" class="<?= $input ?>">
            </div>
            <div>
                <label for="instagram" class="<?= $label ?>">اینستاگرام (URL)</label>
                <input id="instagram" name="instagram" value="<?= e($value('instagram')) ?>" class="<?= $input ?> ltr" placeholder="https://instagram.com/...">
            </div>
            <div>
                <label for="telegram" class="<?= $label ?>">تلگرام (URL)</label>
                <input id="telegram" name="telegram" value="<?= e($value('telegram')) ?>" class="<?= $input ?> ltr">
            </div>
            <div>
                <label for="whatsapp" class="<?= $label ?>">واتساپ (URL)</label>
                <input id="whatsapp" name="whatsapp" value="<?= e($value('whatsapp')) ?>" class="<?= $input ?> ltr">
            </div>
            <div>
                <label for="notify_phone" class="<?= $label ?>">موبایل اعلان ادمین</label>
                <input id="notify_phone" name="notify_phone" value="<?= e($value('notify_phone')) ?>" class="<?= $input ?> ltr" placeholder="0912...">
            </div>
            <div class="sm:col-span-2">
                <label for="order_note" class="<?= $label ?>">یادداشت صفحه سفارش</label>
                <textarea id="order_note" name="order_note" rows="2" class="<?= $input ?>"><?= e($value('order_note')) ?></textarea>
            </div>
            <div>
                <label for="commission_default" class="<?= $label ?>">کمیسیون پیش‌فرض نماینده (٪)</label>
                <input id="commission_default" name="commission_default" value="<?= e($value('commission_default')) ?>" class="<?= $input ?>">
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-sm font-bold">درگاه پرداخت و پیامک</h3>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div>
                <label for="payment_driver" class="<?= $label ?>">درگاه فعال</label>
                <select id="payment_driver" name="payment_driver" class="<?= $input ?>">
                    <option value="">طبق فایل env (پیش‌فرض)</option>
                    <?php foreach ($gateways as $key => $gatewayLabel): ?>
                        <option value="<?= e($key) ?>" <?= $value('payment_driver') === $key ? 'selected' : '' ?>><?= e($gatewayLabel) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-[10px] text-slate-400">درگاه فعال فعلی: <span class="font-bold"><?= e($gateways[$activeGateway] ?? 'درگاه آزمایشی (محیط توسعه)') ?></span></p>
            </div>
            <div>
                <label for="sms_provider" class="<?= $label ?>">سرویس پیامک</label>
                <select id="sms_provider" name="sms_provider" class="<?= $input ?>">
                    <option value="">طبق فایل env</option>
                    <?php foreach ($smsProviders as $key => $providerLabel): ?>
                        <option value="<?= e($key) ?>" <?= $value('sms_provider') === $key ? 'selected' : '' ?>><?= e($providerLabel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm:col-span-2 rounded-xl bg-slate-50 p-3 text-[10px] leading-6 text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
                کلید API پیامک، Merchant ID درگاه و رمزها در <code class="ltr rounded bg-white px-1 dark:bg-slate-900">config/env.php</code> تنظیم می‌شوند و از پنل قابل ویرایش نیستند (برای امنیت بیشتر). پس از تغییر env، یک تراکنش آزمایشی انجام دهید.
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-sm font-bold">صفحات ثابت</h3>
        <p class="mt-1 text-[10px] text-slate-400">این محتوا به‌صورت HTML در صفحات درباره ما، قوانین، حریم خصوصی و سوالات متداول نمایش داده می‌شود.</p>
        <div class="mt-3 space-y-4">
            <?php foreach ([
                ['about', 'درباره ما'],
                ['terms', 'قوانین و مقررات'],
                ['privacy', 'حریم خصوصی'],
                ['faq', 'سوالات متداول'],
            ] as [$slug, $title]): ?>
                <div class="rounded-xl border border-slate-100 p-3 dark:border-slate-800">
                    <div class="grid gap-2 sm:grid-cols-[240px_1fr]">
                        <div>
                            <label for="<?= $slug ?>_title" class="<?= $label ?>">عنوان <?= e($title) ?></label>
                            <input id="<?= $slug ?>_title" name="<?= $slug ?>_title" value="<?= e($value($slug . '_title', $title)) ?>" class="<?= $input ?>">
                        </div>
                        <div>
                            <label for="page_<?= $slug ?>" class="<?= $label ?>">محتوا (HTML مجاز)</label>
                            <textarea id="page_<?= $slug ?>" name="page_<?= $slug ?>" rows="3" class="<?= $input ?> font-mono text-xs"><?= e($value('page_' . $slug)) ?></textarea>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="sticky bottom-3 z-10 flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur dark:border-slate-700 dark:bg-slate-900/95">
        <button class="rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-teal-700">ذخیره تنظیمات</button>
        <span class="text-[10px] text-slate-400">تغییرات بلافاصله در سایت اعمال می‌شود.</span>
    </div>
</form>

<section class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold">بکاپ‌های دیتابیس</h3>
        <span class="text-[10px] text-slate-400">ذخیره در storage/backups — حداکثر ۵ نسخه</span>
    </div>
    <?php if ($backups === []): ?>
        <p class="mt-3 rounded-xl bg-amber-50 p-3 text-[11px] leading-6 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">هنوز بکاپی وجود ندارد. Cron روزانه `php cron/backup_db.php` را فعال کنید.</p>
    <?php else: ?>
        <div class="table-scroll mt-3">
            <table class="w-full text-[11px]">
                <thead class="text-right text-[10px] text-slate-500"><tr><th class="p-2">فایل</th><th class="p-2">حجم</th><th class="p-2">تاریخ</th></tr></thead>
                <tbody>
                <?php foreach ($backups as $backup): ?>
                    <tr class="border-t border-slate-100 dark:border-slate-800">
                        <td class="ltr p-2"><?= e($backup['file']) ?></td>
                        <td class="p-2"><?= e(format_bytes((int) $backup['size'])) ?></td>
                        <td class="p-2 text-slate-400"><?= fa_num(jalali_datetime(date('Y-m-d H:i:s', (int) $backup['modified']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
