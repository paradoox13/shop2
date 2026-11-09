<?php
$input = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100 dark:border-slate-700 dark:bg-slate-900 dark:focus:ring-teal-900';
$label = 'block text-xs font-medium text-slate-600 dark:text-slate-300';
?>
<div class="mb-4 flex items-center gap-2 text-xs text-slate-500">
    <a href="<?= e(url('/cart')) ?>" class="hover:text-teal-600">سبد خرید</a><span>/</span><span>تکمیل خرید</span>
</div>

<h1 class="mb-1 text-xl font-extrabold">تکمیل خرید</h1>
<p class="mb-5 text-sm text-slate-500">مشخصات خریدار را وارد کنید. نیازی به ثبت‌نام نیست؛ کد رهگیری برای پیگیری در همین صفحه نمایش داده می‌شود.</p>

<form method="post" action="<?= e(url('/checkout')) ?>" class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
    <?= csrf_field() ?>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-4 text-sm font-bold">اطلاعات خریدار</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="customer_name" class="<?= $label ?>">نام و نام خانوادگی *</label>
                <input id="customer_name" name="customer_name" required value="<?= e(old('customer_name', $user['full_name'] ?? '')) ?>" class="<?= $input ?>" autocomplete="name">
                <?php if (error('customer_name')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('customer_name')) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="customer_phone" class="<?= $label ?>">شماره موبایل *</label>
                <input id="customer_phone" name="customer_phone" required inputmode="tel" value="<?= e(old('customer_phone', $user['phone'] ?? '')) ?>" placeholder="09121234567" class="<?= $input ?> ltr" autocomplete="tel">
                <?php if (error('customer_phone')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('customer_phone')) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="customer_email" class="<?= $label ?>">ایمیل (اختیاری)</label>
                <input id="customer_email" name="customer_email" type="email" value="<?= e(old('customer_email', $user['email'] ?? '')) ?>" class="<?= $input ?> ltr" autocomplete="email">
                <?php if (error('customer_email')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('customer_email')) ?></p><?php endif; ?>
            </div>
        </div>

        <label class="mt-5 flex items-start gap-2 text-xs leading-6 text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="terms" value="1" required class="mt-1 rounded border-slate-300 text-teal-600 focus:ring-teal-500" <?= old('terms') ? 'checked' : '' ?>>
            <span><a href="<?= e(url('/terms')) ?>" target="_blank" class="font-bold text-teal-700 underline dark:text-teal-300">قوانین و مقررات</a> و <a href="<?= e(url('/privacy')) ?>" target="_blank" class="font-bold text-teal-700 underline dark:text-teal-300">حریم خصوصی</a> را خوانده‌ام و می‌پذیرم.</span>
        </label>
        <?php if (error('terms')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('terms')) ?></p><?php endif; ?>

        <div class="mt-5 rounded-xl bg-teal-50 p-3 text-[11px] leading-6 text-teal-800 dark:bg-teal-950/40 dark:text-teal-200">
            با کلیک روی «پرداخت»، شماره‌های سبد به مدت ۱۵ دقیقه رزرو می‌شوند و به درگاه بانکی منتقل می‌شوید.
        </div>

        <button type="submit" class="mt-5 w-full rounded-xl bg-teal-600 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700 active:scale-[0.99]">پرداخت <?= price($cart['total']) ?> تومان</button>
    </section>

    <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 lg:sticky lg:top-24 dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-3 text-sm font-bold">اقلام سفارش</h2>
        <ul class="space-y-3 text-sm">
            <?php foreach ($cart['numbers'] as $item): ?>
                <li class="flex items-center justify-between gap-2 border-b border-dashed border-slate-100 pb-2 dark:border-slate-800">
                    <span class="ltr digits"><?= e($item['number']) ?></span>
                    <span class="text-xs text-slate-500"><?= e($item['operator_name']) ?></span>
                    <span class="font-bold"><?= price($item['price']) ?></span>
                </li>
            <?php endforeach; ?>
            <?php foreach ($cart['modems'] as $item): ?>
                <li class="flex items-center justify-between gap-2 border-b border-dashed border-slate-100 pb-2 dark:border-slate-800">
                    <span><?= e($item['brand'] . ' ' . $item['model']) ?> ×<?= fa_num($item['qty']) ?></span>
                    <span class="font-bold"><?= price($item['line_total']) ?></span>
                </li>
            <?php endforeach; ?>
            <?php foreach ($cart['activation'] as $item): ?>
                <li class="flex items-center justify-between gap-2 border-b border-dashed border-slate-100 pb-2 dark:border-slate-800">
                    <span>کد فعال‌سازی <?= e($item['operator_name']) ?> ×<?= fa_num($item['qty']) ?></span>
                    <span class="font-bold"><?= price($item['line_total']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="mt-4 flex justify-between border-t border-slate-200 pt-3 text-sm dark:border-slate-700">
            <span class="font-bold">قابل پرداخت</span>
            <span class="font-extrabold text-teal-700 dark:text-teal-300"><?= price($cart['total']) ?> تومان</span>
        </div>
        <a href="<?= e(url('/cart')) ?>" class="mt-4 block text-center text-xs text-slate-500 hover:text-teal-600">ویرایش سبد خرید</a>
    </aside>
</form>
