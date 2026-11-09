<?php
$input = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">قیمت کد فعال‌سازی</h2>
        <p class="mt-1 text-xs text-slate-500">قیمت هر اپراتور را وارد کنید. مقدار صفر یعنی «به‌زودی» و در فروشگاه قابل خرید نیست.</p>
    </div>
</div>

<form method="post" action="<?= e(url('/admin/activation')) ?>" class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <?= csrf_field() ?>
    <div class="table-scroll">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                <tr><th class="p-4">اپراتور</th><th class="p-4">قیمت فعلی (تومان)</th><th class="p-4">قیمت جدید (تومان)</th></tr>
            </thead>
            <tbody>
            <?php foreach ($prices as $price): ?>
                <?php if ($price['slug'] === 'other') { continue; } ?>
                <tr class="border-t border-slate-100 dark:border-slate-800">
                    <td class="p-4 font-bold"><?= e($price['operator_name']) ?></td>
                    <td class="p-4 text-xs text-slate-500"><?= (int) $price['price'] > 0 ? price($price['price']) . ' تومان' : 'تعیین نشده' ?></td>
                    <td class="p-4">
                        <input name="prices[<?= (int) $price['operator_id'] ?>]" value="<?= (int) $price['price'] > 0 ? fa_num((int) $price['price']) : '' ?>" inputmode="numeric" placeholder="مثلاً 450000" class="<?= $input ?> max-w-xs">
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 p-4 dark:border-slate-800">
        <button class="rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-teal-700">ذخیره قیمت‌ها</button>
    </div>
</form>
