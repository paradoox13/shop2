<?php $available = $modem['status'] === 'active' && (int) $modem['stock'] > 0; ?>
<div class="mb-4 flex items-center gap-2 text-xs text-slate-500">
    <a href="<?= e(url('/')) ?>" class="hover:text-teal-600">خانه</a>
    <span>/</span>
    <a href="<?= e(url('/#modems')) ?>" class="hover:text-teal-600">مودم</a>
    <span>/</span>
    <span><?= e($modem['brand'] . ' ' . $modem['model']) ?></span>
</div>

<div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <img src="<?= e(upload_url($modem['image_path'])) ?>" alt="<?= e($modem['brand'] . ' ' . $modem['model']) ?>" class="max-h-[420px] w-full bg-slate-50 object-contain dark:bg-slate-800">
        <div class="p-6">
            <h1 class="text-xl font-extrabold"><?= e($modem['brand'] . ' ' . $modem['model']) ?></h1>
            <div class="mt-2 flex flex-wrap gap-2 text-[11px]">
                <span class="rounded-full bg-teal-50 px-2.5 py-1 text-teal-800 dark:bg-teal-950 dark:text-teal-300"><?= $available ? 'موجود' : 'ناموجود' ?></span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">موجودی: <?= fa_num($modem['stock']) ?> عدد</span>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">فروشنده: <?= e($modem['reseller_name'] ?: setting('site_name')) ?></span>
            </div>
            <div class="mt-5 border-t border-dashed border-slate-200 pt-4 text-sm leading-8 text-slate-600 dark:border-slate-700 dark:text-slate-300">
                <?= nl2br(e($modem['description'] ?: 'توضیحی برای این محصول ثبت نشده است. برای جزئیات با پشتیبانی تماس بگیرید.')) ?>
            </div>
        </div>
    </section>

    <aside class="h-fit rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-24 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-xs text-slate-500">قیمت</span>
        <div class="mt-1 text-3xl font-extrabold text-teal-700 dark:text-teal-300"><?= price($modem['price']) ?> <span class="text-sm font-normal text-slate-400">تومان</span></div>

        <?php if ($available): ?>
            <form method="post" action="<?= e(url('/cart/add-modem')) ?>" class="mt-5 space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $modem['id'] ?>">
                <div>
                    <label for="qty" class="mb-1 block text-xs text-slate-500">تعداد</label>
                    <input id="qty" type="number" name="qty" min="1" max="<?= (int) $modem['stock'] ?>" value="1" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
                </div>
                <button type="submit" class="w-full rounded-xl bg-teal-600 py-3 text-sm font-bold text-white hover:bg-teal-700">افزودن به سبد خرید</button>
            </form>
        <?php else: ?>
            <div class="mt-5 rounded-xl bg-rose-50 p-4 text-sm text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">این محصول در حال حاضر موجود نیست. برای اطلاع از موجودی با پشتیبانی تماس بگیرید.</div>
        <?php endif; ?>

        <div class="mt-5 space-y-2 border-t border-dashed border-slate-200 pt-4 text-[11px] text-slate-500 dark:border-slate-700">
            <p>✓ ارسال از فروشنده تأییدشده</p>
            <p>✓ پرداخت آنلاین امن</p>
            <p>✓ پیگیری سفارش با کد رهگیری</p>
        </div>
    </aside>
</div>
