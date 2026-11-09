<?php
/** @var array<string, mixed> $item */
$birth = !empty($item['birth_date']) ? jalali_date((string) $item['birth_date']) : null;
?>
<article class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-lg hover:shadow-teal-900/5 dark:border-slate-800 dark:bg-slate-900">
    <div class="mb-3 flex items-center justify-between gap-2">
        <span class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-1 text-[11px] font-bold text-teal-800 dark:bg-teal-950 dark:text-teal-300"><?= e($item['operator_name']) ?></span>
        <?php if ($birth !== null): ?>
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-[10px] font-medium text-amber-800 dark:bg-amber-950/60 dark:text-amber-300" title="تاریخ تولد ثبت‌شده">
                🎂 <?= fa_num($birth) ?>
            </span>
        <?php endif; ?>
    </div>

    <a href="<?= e(url('/phones/' . $item['id'])) ?>" class="block rounded-xl bg-slate-50 py-3 text-center transition group-hover:bg-teal-50/60 dark:bg-slate-800/60 dark:group-hover:bg-teal-950/30">
        <span class="ltr digits block text-xl font-extrabold tracking-wider text-slate-900 dark:text-white"><?= e($item['number']) ?></span>
        <span class="mt-1 block text-[10px] text-slate-400">مشاهده جزئیات</span>
    </a>

    <?php if (!empty($item['description'])): ?>
        <p class="mt-2 line-clamp-1 min-h-5 text-xs text-slate-500 dark:text-slate-400"><?= e($item['description']) ?></p>
    <?php else: ?>
        <p class="mt-2 min-h-5 text-xs text-slate-400">شماره سالم و آماده واگذاری</p>
    <?php endif; ?>

    <div class="mt-3 flex items-end justify-between gap-2 border-t border-dashed border-slate-100 pt-3 dark:border-slate-800">
        <div>
            <span class="block text-[10px] text-slate-400">قیمت</span>
            <span class="text-base font-extrabold text-teal-700 dark:text-teal-300"><?= price($item['price']) ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></span>
        </div>
        <form method="post" action="<?= e(url('/cart/add-number')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
            <button type="submit" class="rounded-xl bg-teal-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-teal-700 active:scale-95">افزودن</button>
        </form>
    </div>
</article>
