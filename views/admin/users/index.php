<?php ?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">نمایندگان</h2>
        <p class="mt-1 text-xs text-slate-500"><?= fa_num($resellers['total'] ?? 0) ?> نماینده ثبت شده</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('/admin/resellers/create')) ?>" class="rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700">+ نماینده جدید</a>
    </div>
</div>

<form method="get" action="<?= e(url('/admin/resellers')) ?>" class="mt-4 flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
    <input name="q" value="<?= e($search) ?>" placeholder="جستجوی نام، نام کاربری، موبایل یا نام نمایشی" class="min-w-52 flex-1 rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900">
    <button class="rounded-xl bg-slate-800 px-4 py-2 text-xs font-bold text-white dark:bg-slate-700">جستجو</button>
    <?php if ($search !== ''): ?><a href="<?= e(url('/admin/resellers')) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700">پاک کردن</a><?php endif; ?>
</form>

<div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="table-scroll">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                <tr><th class="p-3">نماینده</th><th class="p-3">نام کاربری</th><th class="p-3">تماس</th><th class="p-3">شماره‌ها</th><th class="p-3">فروش</th><th class="p-3">کمیسیون</th><th class="p-3">وضعیت</th><th class="p-3">عضویت</th><th class="p-3"></th></tr>
            </thead>
            <tbody>
            <?php if (($resellers['items'] ?? []) === []): ?>
                <tr><td colspan="9" class="p-8 text-center text-slate-400">نماینده‌ای یافت نشد.</td></tr>
            <?php endif; ?>
            <?php foreach ($resellers['items'] as $reseller): ?>
                <tr class="border-t border-slate-100 hover:bg-slate-50/60 dark:border-slate-800 dark:hover:bg-slate-800/30">
                    <td class="p-3">
                        <span class="block font-bold"><?= e($reseller['display_name'] ?? $reseller['full_name']) ?></span>
                        <span class="block text-[10px] text-slate-400"><?= e($reseller['full_name']) ?></span>
                    </td>
                    <td class="ltr p-3"><?= e($reseller['username']) ?></td>
                    <td class="p-3">
                        <span class="ltr block text-[10px]"><?= e($reseller['phone']) ?></span>
                        <span class="block text-[9px] text-slate-400"><?= e($reseller['contact'] ?? '') ?></span>
                    </td>
                    <td class="p-3"><?= fa_num($reseller['numbers_count'] ?? 0) ?></td>
                    <td class="p-3 font-bold text-teal-700 dark:text-teal-300"><?= fa_num($reseller['sold_count'] ?? 0) ?></td>
                    <td class="p-3"><?= fa_num((float) ($reseller['commission_rate'] ?? 0)) ?>٪</td>
                    <td class="p-3"><span class="rounded-full px-2 py-0.5 text-[9px] font-bold <?= e(status_badge_class((string) $reseller['status'])) ?>"><?= $reseller['status'] === 'active' ? 'فعال' : 'غیرفعال' ?></span></td>
                    <td class="p-3 text-[10px] text-slate-400"><?= fa_num(jalali_date((string) $reseller['created_at'])) ?></td>
                    <td class="p-3"><a href="<?= e(url('/admin/resellers/' . $reseller['id'] . '/edit')) ?>" class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] hover:bg-teal-100 dark:bg-slate-800">ویرایش</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= \App\Support\View::partial('partials/pagination', ['paginator' => $resellers, 'path' => '/admin/resellers']) ?>
