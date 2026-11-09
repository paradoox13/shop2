<?php $f = $filters ?? []; ?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">مودم‌های من</h2>
        <p class="mt-1 text-xs text-slate-500"><?= fa_num($modems['total'] ?? 0) ?> مودم ثبت شده</p>
    </div>
    <a href="<?= e(url('/reseller/modems/create')) ?>" class="rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700">+ ثبت مودم</a>
</div>

<form method="get" action="<?= e(url('/reseller/modems')) ?>" class="mt-4 flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
    <input name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="جستجوی برند یا مدل" class="min-w-48 flex-1 rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900">
    <button class="rounded-xl bg-slate-800 px-4 py-2 text-xs font-bold text-white dark:bg-slate-700">جستجو</button>
    <a href="<?= e(url('/reseller/modems')) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700">پاک کردن</a>
</form>

<div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
    <?php if (($modems['items'] ?? []) === []): ?>
        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900">مودمی ثبت نکرده‌اید. <a href="<?= e(url('/reseller/modems/create')) ?>" class="font-bold text-teal-600">اولین مودم را ثبت کنید</a></div>
    <?php endif; ?>
    <?php foreach ($modems['items'] as $modem): ?>
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <img src="<?= e(upload_url($modem['image_path'])) ?>" alt="" class="h-32 w-full object-cover">
            <div class="p-4">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-bold"><?= e($modem['brand'] . ' ' . $modem['model']) ?></h3>
                    <span class="rounded-full px-2 py-0.5 text-[9px] font-bold <?= e(status_badge_class((string) $modem['status'])) ?>"><?= $modem['status'] === 'active' ? 'فعال' : 'غیرفعال' ?></span>
                </div>
                <p class="mt-1 text-sm font-extrabold text-teal-700 dark:text-teal-300"><?= price($modem['price']) ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></p>
                <p class="mt-1 text-[10px] <?= (int) $modem['stock'] <= 3 ? 'font-bold text-rose-600' : 'text-slate-400' ?>">موجودی: <?= fa_num($modem['stock']) ?> عدد</p>
                <div class="mt-3 flex gap-1 border-t border-slate-100 pt-3 dark:border-slate-800">
                    <a href="<?= e(url('/reseller/modems/' . $modem['id'] . '/edit')) ?>" class="rounded-lg bg-slate-100 px-2.5 py-1 text-[10px] hover:bg-teal-100 dark:bg-slate-800">ویرایش</a>
                    <a href="<?= e(url('/modems/' . $modem['id'])) ?>" target="_blank" class="rounded-lg bg-slate-100 px-2.5 py-1 text-[10px] hover:bg-teal-100 dark:bg-slate-800">مشاهده</a>
                    <form method="post" action="<?= e(url('/reseller/modems/' . $modem['id'] . '/delete')) ?>" class="mr-auto" onsubmit="return confirm('این مودم حذف شود؟')">
                        <?= csrf_field() ?>
                        <button class="rounded-lg bg-rose-50 px-2.5 py-1 text-[10px] text-rose-600 dark:bg-rose-950/40">حذف</button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?= \App\Support\View::partial('partials/pagination', ['paginator' => $modems, 'path' => '/reseller/modems']) ?>
