<?php
$maxSale = max(1, ...array_map(static fn (array $row): int => (int) $row['total'], $chart ?: [['total' => 0]]));
$totalSale14 = array_sum(array_map(static fn (array $row): int => (int) $row['total'], $chart));
$sms = $smsStats ?? [];
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">سلام <?= e($admin['full_name'] ?? $admin['username'] ?? '') ?> 👋</h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">خلاصه وضعیت فروشگاه در یک نگاه.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('/admin/numbers/create')) ?>" class="rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700">+ شماره جدید</a>
        <a href="<?= e(url('/admin/orders')) ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-medium hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900">سفارش‌ها</a>
    </div>
</div>

<div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between">
            <span class="text-[11px] text-slate-500">فروش کل (تومان)</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-sm dark:bg-emerald-950/50">💰</span>
        </div>
        <p class="mt-2 text-xl font-extrabold text-slate-900 dark:text-white"><?= price($orderStats['total_sales'] ?? 0) ?></p>
        <p class="mt-1 text-[10px] text-slate-400"><?= fa_num($orderStats['orders_count'] ?? 0) ?> سفارش پرداخت‌شده</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between">
            <span class="text-[11px] text-slate-500">فروش امروز</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-sky-50 text-sm dark:bg-sky-950/50">📅</span>
        </div>
        <p class="mt-2 text-xl font-extrabold text-slate-900 dark:text-white"><?= price($orderStats['today_sales'] ?? 0) ?></p>
        <p class="mt-1 text-[10px] text-slate-400"><?= fa_num($orderStats['today_orders'] ?? 0) ?> سفارش امروز</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between">
            <span class="text-[11px] text-slate-500">در انتظار ارسال فیزیکی</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-sm dark:bg-amber-950/50">📦</span>
        </div>
        <p class="mt-2 text-xl font-extrabold text-slate-900 dark:text-white"><?= fa_num($orderStats['physical_pending'] ?? 0) ?></p>
        <a href="<?= e(query_url('/admin/orders', ['status' => 'pending_physical_send'])) ?>" class="mt-1 inline-block text-[10px] text-teal-700 hover:underline dark:text-teal-300">مشاهده سفارش‌ها</a>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between">
            <span class="text-[11px] text-slate-500">شماره‌های موجود</span>
            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-teal-50 text-sm dark:bg-teal-950/50">📱</span>
        </div>
        <p class="mt-2 text-xl font-extrabold text-slate-900 dark:text-white"><?= fa_num($availableNumbers) ?></p>
        <p class="mt-1 text-[10px] text-slate-400">آماده فروش در فروشگاه</p>
    </div>
</div>

<div class="mt-4 grid gap-4 xl:grid-cols-[1.6fr_1fr]">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold">نمودار فروش ۱۴ روز اخیر</h3>
                <p class="mt-0.5 text-[10px] text-slate-400">مجموع: <?= price($totalSale14) ?> تومان</p>
            </div>
            <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] text-teal-700 dark:bg-teal-950/50 dark:text-teal-300">تومان</span>
        </div>
        <div class="mt-5 flex h-44 items-end gap-1.5 overflow-x-auto pb-1" dir="ltr">
            <?php foreach ($chart as $row): ?>
                <?php $height = max(3, (int) round(((int) $row['total'] / $maxSale) * 100)); ?>
                <div class="group flex min-w-[26px] flex-1 flex-col items-center justify-end gap-1" title="<?= e($row['label']) ?>: <?= price($row['total']) ?> تومان (<?= fa_num($row['count']) ?> سفارش)">
                    <span class="text-[8px] text-slate-400 opacity-0 transition group-hover:opacity-100"><?= price($row['total']) ?></span>
                    <div class="w-full max-w-8 rounded-t-md bg-gradient-to-t from-teal-600 to-emerald-400 transition group-hover:from-teal-700" style="height: <?= $height ?>%"></div>
                    <span class="whitespace-nowrap text-[8px] text-slate-400"><?= fa_num($row['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-sm font-bold">وضعیت سیستم</h3>
        <dl class="mt-4 space-y-3 text-xs">
            <div class="flex items-center justify-between"><dt class="text-slate-500">نمایندگان فعال</dt><dd class="font-bold"><?= fa_num($resellersCount) ?> نفر</dd></div>
            <div class="flex items-center justify-between"><dt class="text-slate-500">مودم‌های کم‌موجود</dt><dd class="font-bold <?= $lowStockModems > 0 ? 'text-rose-600' : '' ?>"><?= fa_num($lowStockModems) ?> مدل</dd></div>
            <div class="flex items-center justify-between"><dt class="text-slate-500">پیامک در صف / ناموفق</dt><dd class="font-bold"><?= fa_num($sms['queued'] ?? 0) ?> / <span class="text-rose-600"><?= fa_num($sms['failed'] ?? 0) ?></span></dd></div>
            <div class="flex items-center justify-between"><dt class="text-slate-500">Job در انتظار</dt><dd class="font-bold"><?= fa_num($pendingJobs) ?></dd></div>
            <div class="flex items-center justify-between"><dt class="text-slate-500">پیام جدید تماس</dt><dd class="font-bold"><?= fa_num($newMessages) ?></dd></div>
            <div class="flex items-center justify-between"><dt class="text-slate-500">پرداخت‌های در انتظار</dt><dd class="font-bold"><?= fa_num($orderStats['pending_orders'] ?? 0) ?></dd></div>
        </dl>
        <div class="mt-4 flex flex-wrap gap-1.5">
            <a href="<?= e(url('/admin/reports')) ?>" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[10px] hover:bg-teal-100 dark:bg-slate-800">گزارش فروش</a>
            <a href="<?= e(url('/admin/messages')) ?>" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[10px] hover:bg-teal-100 dark:bg-slate-800">پیام‌ها</a>
            <a href="<?= e(url('/admin/sms')) ?>" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[10px] hover:bg-teal-100 dark:bg-slate-800">پیامک‌ها</a>
            <a href="<?= e(url('/admin/settings')) ?>" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[10px] hover:bg-teal-100 dark:bg-slate-800">تنظیمات</a>
        </div>
    </section>
</div>

<div class="mt-4 grid gap-4 xl:grid-cols-[1.6fr_1fr]">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800">
            <h3 class="text-sm font-bold">آخرین سفارش‌ها</h3>
            <a href="<?= e(url('/admin/orders')) ?>" class="text-[10px] text-teal-700 hover:underline dark:text-teal-300">همه سفارش‌ها →</a>
        </div>
        <div class="table-scroll">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                    <tr><th class="p-3">کد رهگیری</th><th class="p-3">مشتری</th><th class="p-3">مبلغ</th><th class="p-3">وضعیت</th><th class="p-3">تاریخ</th></tr>
                </thead>
                <tbody>
                <?php if ($recentOrders === []): ?>
                    <tr><td colspan="5" class="p-6 text-center text-slate-400">هنوز سفارشی ثبت نشده است.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentOrders as $order): ?>
                    <tr class="border-t border-slate-100 hover:bg-slate-50/60 dark:border-slate-800 dark:hover:bg-slate-800/30">
                        <td class="p-3"><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>" class="ltr font-bold text-teal-700 hover:underline dark:text-teal-300"><?= e($order['tracking_code']) ?></a></td>
                        <td class="p-3"><?= e($order['customer_name']) ?></td>
                        <td class="p-3 font-bold"><?= price($order['total_price']) ?></td>
                        <td class="p-3"><span class="rounded-full px-2 py-0.5 text-[9px] font-bold <?= e(status_badge_class((string) $order['status'])) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></td>
                        <td class="p-3 text-[10px] text-slate-400"><?= fa_num(jalali_datetime((string) $order['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold">آخرین بکاپ‌ها</h3>
            <span class="text-[10px] text-slate-400">حداکثر ۵ نسخه</span>
        </div>
        <?php if ($backups === []): ?>
            <p class="mt-4 rounded-xl bg-amber-50 p-3 text-[11px] leading-6 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">هنوز بکاپی ساخته نشده است. Cron روزانه `backup_db.php` را فعال کنید.</p>
        <?php else: ?>
            <ul class="mt-3 space-y-2">
                <?php foreach ($backups as $backup): ?>
                    <li class="flex items-center justify-between rounded-xl border border-slate-100 p-2.5 text-[11px] dark:border-slate-800">
                        <div class="min-w-0">
                            <p class="ltr truncate font-medium"><?= e($backup['file']) ?></p>
                            <p class="mt-0.5 text-[9px] text-slate-400"><?= fa_num(jalali_datetime(date('Y-m-d H:i:s', (int) $backup['modified']))) ?></p>
                        </div>
                        <span class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[9px] dark:bg-slate-800"><?= e(format_bytes((int) $backup['size'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
