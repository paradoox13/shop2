<?php
$displayName = $profile['display_name'] ?? auth_user()['full_name'] ?? '';
$recent = $recentSales['items'] ?? [];
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">سلام <?= e($displayName) ?> 👋</h2>
        <p class="mt-1 text-xs text-slate-500">خلاصه فروش و موجودی شما.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('/reseller/numbers/create')) ?>" class="rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700">+ ثبت شماره</a>
        <a href="<?= e(url('/reseller/import')) ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-medium hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900">ایمپورت اکسل</a>
    </div>
</div>

<div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-[10px] text-slate-500">فروش کل (تومان)</span>
        <p class="mt-1 text-xl font-extrabold text-teal-700 dark:text-teal-300"><?= price($summary['revenue'] ?? 0) ?></p>
        <p class="mt-1 text-[10px] text-slate-400"><?= fa_num($summary['items_count'] ?? 0) ?> قلم فروش‌رفته</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-[10px] text-slate-500">کمیسیون شما</span>
        <p class="mt-1 text-xl font-extrabold"><?= price($summary['commission'] ?? 0) ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></p>
        <p class="mt-1 text-[10px] text-slate-400">نرخ: <?= fa_num((float) ($summary['commission_rate'] ?? 0)) ?>٪</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-[10px] text-slate-500">شماره‌های فروخته‌شده</span>
        <p class="mt-1 text-xl font-extrabold"><?= fa_num($sold['count'] ?? 0) ?></p>
        <p class="mt-1 text-[10px] text-slate-400">ارزش: <?= price($sold['total'] ?? 0) ?> تومان</p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-[10px] text-slate-500">شماره آماده فروش</span>
        <p class="mt-1 text-xl font-extrabold text-emerald-600 dark:text-emerald-400"><?= fa_num($availableNumbers) ?></p>
        <p class="mt-1 text-[10px] text-slate-400">از مجموع <?= fa_num($totalNumbers) ?> شماره</p>
    </div>
</div>

<div class="mt-4 grid gap-4 xl:grid-cols-[1.6fr_1fr]">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800">
            <h3 class="text-sm font-bold">فروش‌های اخیر</h3>
            <a href="<?= e(url('/reseller/sales')) ?>" class="text-[10px] text-teal-700 hover:underline dark:text-teal-300">همه فروش‌ها →</a>
        </div>
        <div class="table-scroll">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                    <tr><th class="p-3">محصول</th><th class="p-3">سفارش</th><th class="p-3">مبلغ</th><th class="p-3">تاریخ</th></tr>
                </thead>
                <tbody>
                <?php if ($recent === []): ?>
                    <tr><td colspan="4" class="p-8 text-center text-slate-400">هنوز فروشی ثبت نشده است.</td></tr>
                <?php endif; ?>
                <?php foreach ($recent as $sale): ?>
                    <?php $snapshot = $sale['snapshot_array'] ?? []; ?>
                    <tr class="border-t border-slate-100 dark:border-slate-800">
                        <td class="p-3">
                            <span class="font-medium"><?= e(product_type_label((string) $sale['product_type'])) ?></span>
                            <span class="mt-0.5 block text-[10px] text-slate-500">
                                <?php if ($sale['product_type'] === 'number'): ?>
                                    <span class="ltr"><?= e($snapshot['number'] ?? '') ?></span>
                                <?php else: ?>
                                    <?= e(($snapshot['brand'] ?? '') . ' ' . ($snapshot['model'] ?? '')) ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="ltr p-3 text-[10px]"><?= e($sale['tracking_code']) ?></td>
                        <td class="p-3 font-bold"><?= price((int) $sale['price'] * (int) $sale['qty']) ?></td>
                        <td class="p-3 text-[10px] text-slate-400"><?= fa_num(jalali_date((string) $sale['order_created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="space-y-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-sm font-bold">اطلاعات نمایندگی</h3>
            <dl class="mt-3 space-y-2 text-xs">
                <div><dt class="text-[10px] text-slate-400">نام نمایشی</dt><dd class="font-medium"><?= e($profile['display_name'] ?? '—') ?></dd></div>
                <div><dt class="text-[10px] text-slate-400">راه ارتباطی</dt><dd class="font-medium"><?= e($profile['contact'] ?? '—') ?></dd></div>
                <div><dt class="text-[10px] text-slate-400">کمیسیون</dt><dd class="font-bold text-teal-700 dark:text-teal-300"><?= fa_num((float) ($profile['commission_rate'] ?? 0)) ?>٪</dd></div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-sm font-bold">خلاصه موجودی</h3>
            <div class="mt-3 grid grid-cols-2 gap-2 text-center">
                <div class="rounded-xl bg-teal-50 p-3 dark:bg-teal-950/30">
                    <span class="block text-lg font-extrabold"><?= fa_num($totalNumbers) ?></span>
                    <span class="text-[10px] text-slate-500">شماره</span>
                </div>
                <div class="rounded-xl bg-sky-50 p-3 dark:bg-sky-950/30">
                    <span class="block text-lg font-extrabold"><?= fa_num($modemsCount) ?></span>
                    <span class="text-[10px] text-slate-500">مودم</span>
                </div>
            </div>
            <div class="mt-3 flex gap-1.5">
                <a href="<?= e(url('/reseller/numbers')) ?>" class="flex-1 rounded-lg bg-slate-100 px-2 py-2 text-center text-[10px] hover:bg-teal-100 dark:bg-slate-800">مدیریت شماره‌ها</a>
                <a href="<?= e(url('/reseller/import')) ?>" class="flex-1 rounded-lg bg-slate-100 px-2 py-2 text-center text-[10px] hover:bg-teal-100 dark:bg-slate-800">ایمپورت گروهی</a>
            </div>
        </section>
    </div>
</div>
