<?php
$f = $filters ?? [];
$input = 'rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">گزارش فروش و تسویه</h2>
        <p class="mt-1 text-xs text-slate-500">فقط فروش‌های پرداخت‌شده نمایش داده می‌شوند.</p>
    </div>
    <a href="<?= e(query_url('/reseller/sales/export', $f, ['page'])) ?>" class="rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700">خروجی CSV</a>
</div>

<div class="mt-4 grid gap-3 sm:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-[10px] text-slate-500">فروش کل</span>
        <p class="mt-1 text-lg font-extrabold text-teal-700 dark:text-teal-300"><?= price($summary['revenue'] ?? 0) ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></p>
        <span class="text-[10px] text-slate-400"><?= fa_num($summary['items_count'] ?? 0) ?> قلم</span>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-[10px] text-slate-500">کمیسیون (<?= fa_num((float) ($summary['commission_rate'] ?? 0)) ?>٪)</span>
        <p class="mt-1 text-lg font-extrabold"><?= price($summary['commission'] ?? 0) ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-[10px] text-slate-500">تعداد شماره فروخته‌شده</span>
        <p class="mt-1 text-lg font-extrabold"><?= fa_num($summary['numbers_sold'] ?? 0) ?></p>
    </div>
</div>

<form method="get" action="<?= e(url('/reseller/sales')) ?>" class="mt-4 grid gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:grid-cols-4 dark:border-slate-800 dark:bg-slate-900">
    <input name="from" value="<?= e($f['from'] ?? '') ?>" placeholder="از تاریخ 1405/01/01" class="<?= $input ?>">
    <input name="to" value="<?= e($f['to'] ?? '') ?>" placeholder="تا تاریخ 1405/12/29" class="<?= $input ?>">
    <select name="type" class="<?= $input ?>">
        <option value="">همه انواع</option>
        <option value="number" <?= ($f['type'] ?? '') === 'number' ? 'selected' : '' ?>>شماره</option>
        <option value="modem" <?= ($f['type'] ?? '') === 'modem' ? 'selected' : '' ?>>مودم</option>
    </select>
    <div class="flex gap-1">
        <button class="flex-1 rounded-xl bg-slate-800 px-3 py-2 text-xs font-bold text-white dark:bg-slate-700">فیلتر</button>
        <a href="<?= e(url('/reseller/sales')) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700">✕</a>
    </div>
</form>

<div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="table-scroll">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                <tr><th class="p-3">محصول</th><th class="p-3">سفارش</th><th class="p-3">مشتری</th><th class="p-3">قیمت واحد</th><th class="p-3">تعداد</th><th class="p-3">وضعیت</th><th class="p-3">تاریخ فروش</th></tr>
            </thead>
            <tbody>
            <?php if (($sales['items'] ?? []) === []): ?>
                <tr><td colspan="7" class="p-8 text-center text-slate-400">فروشی در این بازه ثبت نشده است.</td></tr>
            <?php endif; ?>
            <?php foreach ($sales['items'] as $sale): ?>
                <?php $snapshot = $sale['snapshot_array'] ?? []; ?>
                <tr class="border-t border-slate-100 hover:bg-slate-50/60 dark:border-slate-800 dark:hover:bg-slate-800/30">
                    <td class="p-3">
                        <span class="font-medium"><?= e(product_type_label((string) $sale['product_type'])) ?></span>
                        <span class="mt-0.5 block text-[10px] text-slate-500">
                            <?php if ($sale['product_type'] === 'number'): ?>
                                <span class="ltr"><?= e($snapshot['number'] ?? '') ?></span> — <?= e($snapshot['operator_name'] ?? '') ?>
                            <?php else: ?>
                                <?= e(($snapshot['brand'] ?? '') . ' ' . ($snapshot['model'] ?? '')) ?>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="ltr p-3 text-[10px]"><?= e($sale['tracking_code']) ?></td>
                    <td class="p-3 text-[10px]">
                        <span class="block"><?= e($sale['customer_name']) ?></span>
                        <span class="ltr block text-slate-400"><?= e($sale['customer_phone']) ?></span>
                    </td>
                    <td class="p-3"><?= price($sale['price']) ?></td>
                    <td class="p-3"><?= fa_num($sale['qty']) ?></td>
                    <td class="p-3"><span class="rounded-full px-2 py-0.5 text-[9px] font-bold <?= e(status_badge_class((string) $sale['order_status'])) ?>"><?= e(order_status_label((string) $sale['order_status'])) ?></span></td>
                    <td class="p-3 text-[10px] text-slate-400"><?= fa_num(jalali_datetime((string) $sale['order_created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-slate-50 dark:bg-slate-800/50">
                <tr><td colspan="6" class="p-3 text-left text-[11px] font-bold">جمع فروش این فیلتر</td><td class="p-3 text-sm font-extrabold text-teal-700 dark:text-teal-300"><?= price($sales['revenue'] ?? 0) ?> تومان</td></tr>
            </tfoot>
        </table>
    </div>
</div>
<?= \App\Support\View::partial('partials/pagination', ['paginator' => $sales, 'path' => '/reseller/sales']) ?>
