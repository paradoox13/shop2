<?php
$f = $filters ?? [];
$statuses = ['pending' => 'در انتظار پرداخت', 'awaiting_payment' => 'در انتظار پرداخت', 'paid' => 'پرداخت شده', 'pending_physical_send' => 'در انتظار ارسال', 'sent' => 'ارسال شده', 'completed' => 'تکمیل شده', 'canceled' => 'لغو شده', 'failed' => 'ناموفق', 'refunded' => 'بازگشت وجه'];
$input = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">سفارش‌ها</h2>
        <p class="mt-1 text-xs text-slate-500"><?= fa_num($orders['total'] ?? 0) ?> سفارش با این فیلتر</p>
    </div>
    <div class="flex flex-wrap gap-1.5 text-[10px]">
        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">پرداخت‌شده: <?= fa_num($stats['by_status']['paid'] ?? 0) ?></span>
        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">در انتظار پرداخت: <?= fa_num($stats['pending_orders'] ?? 0) ?></span>
        <span class="rounded-full bg-sky-50 px-2.5 py-1 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">ارسال فیزیکی: <?= fa_num($stats['physical_pending'] ?? 0) ?></span>
    </div>
</div>

<form method="get" action="<?= e(url('/admin/orders')) ?>" class="mt-4 grid gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-6 dark:border-slate-800 dark:bg-slate-900">
    <input name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="کد رهگیری / نام / موبایل" class="<?= $input ?> lg:col-span-2">
    <select name="status" class="<?= $input ?>">
        <option value="">همه وضعیت‌ها</option>
        <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($f['status'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="type" class="<?= $input ?>">
        <option value="">همه انواع</option>
        <?php foreach (['number' => 'شماره', 'modem' => 'مودم', 'activation_code' => 'کد فعال‌سازی', 'mixed' => 'ترکیبی'] as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($f['type'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input name="from" value="<?= e($f['from'] ?? '') ?>" placeholder="از تاریخ 1405/01/01" class="<?= $input ?>">
    <div class="flex gap-1">
        <input name="to" value="<?= e($f['to'] ?? '') ?>" placeholder="تا تاریخ" class="<?= $input ?>">
        <button class="rounded-xl bg-slate-800 px-3 text-xs font-bold text-white dark:bg-slate-700" aria-label="فیلتر">⌕</button>
    </div>
    <div class="flex gap-1 lg:col-span-6">
        <a href="<?= e(query_url('/admin/orders/export', $f, ['page'])) ?>" class="rounded-xl bg-teal-50 px-4 py-2 text-[11px] font-bold text-teal-700 hover:bg-teal-100 dark:bg-teal-950/40 dark:text-teal-300">خروجی CSV</a>
        <a href="<?= e(url('/admin/orders')) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-[11px] dark:border-slate-700">پاک کردن فیلترها</a>
    </div>
</form>

<div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="table-scroll">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                <tr><th class="p-3">کد رهگیری</th><th class="p-3">مشتری</th><th class="p-3">نوع</th><th class="p-3">مبلغ</th><th class="p-3">وضعیت</th><th class="p-3">تاریخ</th><th class="p-3"></th></tr>
            </thead>
            <tbody>
            <?php if (($orders['items'] ?? []) === []): ?>
                <tr><td colspan="7" class="p-8 text-center text-slate-400">سفارشی با این فیلترها یافت نشد.</td></tr>
            <?php endif; ?>
            <?php foreach ($orders['items'] as $order): ?>
                <tr class="border-t border-slate-100 hover:bg-slate-50/60 dark:border-slate-800 dark:hover:bg-slate-800/30">
                    <td class="p-3"><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>" class="ltr font-bold text-teal-700 hover:underline dark:text-teal-300"><?= e($order['tracking_code']) ?></a></td>
                    <td class="p-3">
                        <span class="block font-medium"><?= e($order['customer_name']) ?></span>
                        <span class="ltr block text-[10px] text-slate-400"><?= e($order['customer_phone']) ?></span>
                    </td>
                    <td class="p-3 text-[10px]"><?= e($order['type'] === 'mixed' ? 'ترکیبی' : product_type_label((string) $order['type'])) ?></td>
                    <td class="p-3 font-bold"><?= price($order['total_price']) ?> <span class="text-[9px] font-normal text-slate-400">تومان</span></td>
                    <td class="p-3"><span class="rounded-full px-2 py-0.5 text-[9px] font-bold <?= e(status_badge_class((string) $order['status'])) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></td>
                    <td class="p-3 text-[10px] text-slate-400"><?= fa_num(jalali_datetime((string) $order['created_at'])) ?></td>
                    <td class="p-3"><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>" class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] hover:bg-teal-100 dark:bg-slate-800">جزئیات</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= \App\Support\View::partial('partials/pagination', ['paginator' => $orders, 'path' => '/admin/orders']) ?>
