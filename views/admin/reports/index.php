<?php
$max = max(1, ...array_map(static fn (array $row): int => (int) $row['total'], $overview['daily'] ?: [['total' => 0]]));
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">گزارش فروش</h2>
        <p class="mt-1 text-xs text-slate-500">بازه <?= fa_num($from) ?> تا <?= fa_num($to) ?> (شمسی)</p>
    </div>
    <a href="<?= e(query_url('/admin/reports/export', ['from' => $from, 'to' => $to])) ?>" class="rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700">خروجی CSV</a>
</div>

<form method="get" action="<?= e(url('/admin/reports')) ?>" class="mt-4 grid gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:grid-cols-4 dark:border-slate-800 dark:bg-slate-900">
    <div>
        <label for="from" class="mb-1 block text-[10px] text-slate-500">از تاریخ (شمسی)</label>
        <input id="from" name="from" value="<?= e($from) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900">
    </div>
    <div>
        <label for="to" class="mb-1 block text-[10px] text-slate-500">تا تاریخ (شمسی)</label>
        <input id="to" name="to" value="<?= e($to) ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900">
    </div>
    <div class="flex items-end"><button class="w-full rounded-xl bg-slate-800 px-4 py-2 text-xs font-bold text-white dark:bg-slate-700">اعمال بازه</button></div>
    <div class="flex items-end"><a href="<?= e(url('/admin/reports')) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2 text-center text-xs dark:border-slate-700">ماه جاری</a></div>
</form>

<div class="mt-4 grid gap-3 sm:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-[10px] text-slate-500">تعداد سفارش موفق</span>
        <p class="mt-1 text-xl font-extrabold"><?= fa_num($overview['orders_count']) ?></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-[10px] text-slate-500">درآمد (تومان)</span>
        <p class="mt-1 text-xl font-extrabold text-teal-700 dark:text-teal-300"><?= price($overview['revenue']) ?></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-[10px] text-slate-500">میانگین سفارش</span>
        <p class="mt-1 text-xl font-extrabold"><?= $overview['orders_count'] > 0 ? price((int) round($overview['revenue'] / $overview['orders_count'])) : '۰' ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></p>
    </div>
</div>

<div class="mt-4 grid gap-4 xl:grid-cols-2">
    <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-sm font-bold">فروش بر اساس نوع محصول</h3>
        <div class="mt-3 space-y-2">
            <?php if ($overview['by_type'] === []): ?><p class="text-[11px] text-slate-400">داده‌ای در این بازه نیست.</p><?php endif; ?>
            <?php foreach ($overview['by_type'] as $row): ?>
                <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 text-xs dark:border-slate-800">
                    <span><?= e(product_type_label((string) $row['product_type'])) ?> <span class="text-[10px] text-slate-400">(<?= fa_num($row['items']) ?> قلم)</span></span>
                    <span class="font-bold"><?= price($row['total']) ?> تومان</span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-sm font-bold">پرفروش‌ترین اپراتورها</h3>
        <div class="mt-3 space-y-2">
            <?php if ($overview['top_operators'] === []): ?><p class="text-[11px] text-slate-400">داده‌ای در این بازه نیست.</p><?php endif; ?>
            <?php foreach ($overview['top_operators'] as $row): ?>
                <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2 text-xs dark:border-slate-800">
                    <span><?= e($row['operator_name'] ?? '—') ?> <span class="text-[10px] text-slate-400">(<?= fa_num($row['numbers_count']) ?> شماره)</span></span>
                    <span class="font-bold"><?= price($row['total']) ?> تومان</span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<section class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
    <h3 class="text-sm font-bold">نمودار روزانه درآمد</h3>
    <?php if ($overview['daily'] === []): ?>
        <p class="mt-3 text-[11px] text-slate-400">در این بازه فروشی ثبت نشده است.</p>
    <?php else: ?>
        <div class="mt-4 flex h-40 items-end gap-1 overflow-x-auto pb-1" dir="ltr">
            <?php foreach ($overview['daily'] as $row): ?>
                <?php $height = max(3, (int) round(((int) $row['total'] / $max) * 100)); ?>
                <div class="group flex min-w-[18px] flex-1 flex-col items-center justify-end gap-1" title="<?= e((string) $row['day']) ?>: <?= price($row['total']) ?> تومان">
                    <div class="w-full max-w-6 rounded-t bg-teal-500 transition group-hover:bg-teal-700" style="height: <?= $height ?>%"></div>
                    <span class="whitespace-nowrap text-[8px] text-slate-400"><?= fa_num(substr((string) $row['day'], 5)) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
