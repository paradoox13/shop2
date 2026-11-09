<?php
$f = $filters ?? [];
$input = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">مدیریت مودم‌ها</h2>
        <p class="mt-1 text-xs text-slate-500"><?= fa_num($modems['total'] ?? 0) ?> مودم ثبت شده</p>
    </div>
    <a href="<?= e(url('/admin/modems/create')) ?>" class="rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700">+ افزودن مودم</a>
</div>

<form method="get" action="<?= e(url('/admin/modems')) ?>" class="mt-4 grid gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-5 dark:border-slate-800 dark:bg-slate-900">
    <input name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="جستجوی برند یا مدل" class="<?= $input ?>">
    <select name="brand" class="<?= $input ?>">
        <option value="">همه برندها</option>
        <?php foreach ($brands as $brand): ?>
            <option value="<?= e($brand) ?>" <?= ($f['brand'] ?? '') === $brand ? 'selected' : '' ?>><?= e($brand) ?></option>
        <?php endforeach; ?>
    </select>
    <input name="min_price" value="<?= e($f['min_price'] ?? '') ?>" placeholder="حداقل قیمت" inputmode="numeric" class="<?= $input ?>">
    <input name="max_price" value="<?= e($f['max_price'] ?? '') ?>" placeholder="حداکثر قیمت" inputmode="numeric" class="<?= $input ?>">
    <div class="flex gap-1">
        <button class="flex-1 rounded-xl bg-slate-800 px-3 py-2 text-xs font-bold text-white dark:bg-slate-700">فیلتر</button>
        <a href="<?= e(url('/admin/modems')) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700">✕</a>
    </div>
</form>

<div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
    <?php if (($modems['items'] ?? []) === []): ?>
        <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900">مودمی یافت نشد.</div>
    <?php endif; ?>
    <?php foreach ($modems['items'] as $modem): ?>
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="flex gap-3 p-3">
                <img src="<?= e(upload_url($modem['image_path'])) ?>" alt="" class="h-20 w-20 shrink-0 rounded-xl border border-slate-100 object-cover dark:border-slate-800">
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="truncate text-sm font-bold"><?= e($modem['brand'] . ' ' . $modem['model']) ?></h3>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[9px] font-bold <?= e(status_badge_class((string) $modem['status'])) ?>"><?= $modem['status'] === 'active' ? 'فعال' : 'غیرفعال' ?></span>
                    </div>
                    <p class="mt-1 text-sm font-extrabold text-teal-700 dark:text-teal-300"><?= price($modem['price']) ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></p>
                    <p class="mt-1 text-[10px] <?= (int) $modem['stock'] <= 3 ? 'font-bold text-rose-600' : 'text-slate-400' ?>">موجودی: <?= fa_num($modem['stock']) ?> عدد</p>
                    <p class="mt-0.5 text-[10px] text-slate-400"><?= e($modem['reseller_name'] ?? 'فروش مستقیم') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-1 border-t border-slate-100 px-3 py-2 dark:border-slate-800">
                <a href="<?= e(url('/admin/modems/' . $modem['id'] . '/edit')) ?>" class="rounded-lg bg-slate-100 px-2.5 py-1 text-[10px] hover:bg-teal-100 dark:bg-slate-800">ویرایش</a>
                <a href="<?= e(url('/modems/' . $modem['id'])) ?>" target="_blank" class="rounded-lg bg-slate-100 px-2.5 py-1 text-[10px] hover:bg-teal-100 dark:bg-slate-800">مشاهده</a>
                <form method="post" action="<?= e(url('/admin/modems/' . $modem['id'] . '/delete')) ?>" class="mr-auto" onsubmit="return confirm('این مودم حذف شود؟')">
                    <?= csrf_field() ?>
                    <button class="rounded-lg bg-rose-50 px-2.5 py-1 text-[10px] text-rose-600 hover:bg-rose-100 dark:bg-rose-950/40">حذف</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?= \App\Support\View::partial('partials/pagination', ['paginator' => $modems, 'path' => '/admin/modems']) ?>
