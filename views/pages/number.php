<?php
$birth = !empty($number['birth_date']) ? jalali_date((string) $number['birth_date']) : null;
?>
<div class="mb-4 flex items-center gap-2 text-xs text-slate-500">
    <a href="<?= e(url('/')) ?>" class="hover:text-teal-600">خانه</a>
    <span>/</span>
    <span>شماره <?= e($number['number']) ?></span>
</div>

<div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1 text-xs font-bold text-teal-800 dark:bg-teal-950 dark:text-teal-300"><img src="<?= e(operator_logo((string) ($number['operator_slug'] ?? ''))) ?>" alt="" class="h-5 w-5 rounded object-contain"><?= e($number['operator_name']) ?></span>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">موجود</span>
        </div>
        <div class="my-6 rounded-2xl bg-gradient-to-l from-slate-50 to-teal-50/60 py-8 text-center dark:from-slate-800 dark:to-teal-950/40">
            <span class="ltr digits block text-3xl font-extrabold tracking-wider text-slate-900 sm:text-4xl dark:text-white"><?= e($number['number']) ?></span>
            <button type="button" onclick="copyText('<?= e($number['number']) ?>', this)" class="mt-3 rounded-lg border border-slate-200 bg-white px-3 py-1 text-[11px] text-slate-500 hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900">کپی شماره</button>
        </div>
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <div class="rounded-xl border border-slate-100 p-3 dark:border-slate-800">
                <dt class="text-[11px] text-slate-400">اپراتور</dt>
                <dd class="mt-1 font-bold"><?= e($number['operator_name']) ?></dd>
            </div>
            <div class="rounded-xl border border-slate-100 p-3 dark:border-slate-800">
                <dt class="text-[11px] text-slate-400">دسته‌بندی</dt>
                <dd class="mt-1"><span class="rounded-full px-2 py-1 text-[10px] font-bold <?= e(number_type_badge_class((string) ($number['number_type'] ?? 'other'))) ?>"><?= e(number_type_label((string) ($number['number_type'] ?? 'other'))) ?></span></dd>
            </div>
            <div class="rounded-xl border border-slate-100 p-3 dark:border-slate-800">
                <dt class="text-[11px] text-slate-400">نوع خط</dt>
                <dd class="mt-1 font-bold"><?= e(line_type_label((string) ($number['line_type'] ?? 'prepaid'))) ?></dd>
            </div>
            <div class="rounded-xl border border-slate-100 p-3 dark:border-slate-800">
                <dt class="text-[11px] text-slate-400">تاریخ تولد ثبت‌شده</dt>
                <dd class="mt-1 font-bold"><?= $birth !== null ? fa_num($birth) : 'ثبت نشده' ?></dd>
            </div>
            <div class="rounded-xl border border-slate-100 p-3 dark:border-slate-800">
                <dt class="text-[11px] text-slate-400">بازدید</dt>
                <dd class="mt-1 font-bold"><?= fa_num($number['views']) ?></dd>
            </div>
            <div class="rounded-xl border border-slate-100 p-3 dark:border-slate-800">
                <dt class="text-[11px] text-slate-400">وضعیت</dt>
                <dd class="mt-1 font-bold"><?= e(number_status_label((string) $number['status'])) ?></dd>
            </div>
        </dl>
        <?php if (!empty($number['description'])): ?>
            <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm leading-7 text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                <?= nl2br(e($number['description'])) ?>
            </div>
        <?php endif; ?>
    </section>

    <aside class="h-fit rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-24 dark:border-slate-800 dark:bg-slate-900">
        <span class="text-xs text-slate-500">قیمت نهایی</span>
        <div class="mt-1 text-3xl font-extrabold text-teal-700 dark:text-teal-300"><?= price($number['price']) ?> <span class="text-sm font-normal text-slate-400">تومان</span></div>
        <p class="mt-3 text-xs leading-6 text-slate-500 dark:text-slate-400">پس از افزودن به سبد و پرداخت، شماره به نام شما ثبت و از طریق صفحه سفارش و پیامک تحویل داده می‌شود. مبلغ قابل پرداخت در درگاه به ریال محاسبه می‌شود.</p>
        <form method="post" action="<?= e(url('/cart/add-number')) ?>" class="mt-5">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $number['id'] ?>">
            <button type="submit" class="w-full rounded-xl bg-teal-600 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700 active:scale-[0.99]">افزودن به سبد خرید</button>
        </form>
        <a href="<?= e(url('/cart')) ?>" class="mt-3 block w-full rounded-xl border border-slate-200 py-2.5 text-center text-sm font-medium hover:border-teal-400 dark:border-slate-700">مشاهده سبد خرید</a>
        <div class="mt-5 space-y-2 border-t border-dashed border-slate-200 pt-4 text-[11px] text-slate-500 dark:border-slate-700">
            <p>✓ رزرو ۱۵ دقیقه‌ای هنگام پرداخت</p>
            <p>✓ پشتیبانی تلفنی برای استعلام</p>
            <p>✓ پرداخت از طریق درگاه بانکی معتبر</p>
        </div>
        <?php if (setting('support_phone') !== ''): ?>
            <a href="tel:<?= e(preg_replace('/\D+/', '', setting('support_phone'))) ?>" class="mt-4 block text-center text-sm font-bold text-teal-700 dark:text-teal-300">تماس با پشتیبانی: <?= fa_num(setting('support_phone')) ?></a>
        <?php endif; ?>
    </aside>
</div>

<?php if (($related['items'] ?? []) !== []): ?>
    <section class="mt-10">
        <h2 class="mb-4 text-lg font-extrabold">شماره‌های مشابه</h2>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($related['items'] as $item): ?>
                <?= \App\Support\View::partial('partials/number-card', ['item' => $item]) ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
