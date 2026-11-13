<?php
$f = $filters ?? [];
$input = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">شماره‌های من</h2>
        <p class="mt-1 text-xs text-slate-500"><?= fa_num($numbers['total'] ?? 0) ?> شماره در پنل شما</p>
    </div>
    <a href="<?= e(url('/reseller/numbers/create')) ?>" class="rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700">+ ثبت شماره</a>
</div>

<form method="get" action="<?= e(url('/reseller/numbers')) ?>" class="mt-4 grid gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-5 dark:border-slate-800 dark:bg-slate-900">
    <input name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="جستجوی شماره" class="<?= $input ?>">
    <select name="operator" class="<?= $input ?>">
        <option value="">همه اپراتورها</option>
        <?php foreach ($operators as $operator): ?>
            <option value="<?= (int) $operator['id'] ?>" <?= (string) ($f['operator'] ?? '') === (string) $operator['id'] ? 'selected' : '' ?>><?= e($operator['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="<?= $input ?>">
        <option value="">همه وضعیت‌ها</option>
        <?php foreach (['available' => 'موجود', 'reserved' => 'رزرو', 'sold' => 'فروخته‌شده', 'hidden' => 'مخفی'] as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($f['status'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input name="pattern" value="<?= e($f['pattern'] ?? '') ?>" placeholder="الگو: round / serial" class="<?= $input ?>">
    <div class="flex gap-1">
        <button class="flex-1 rounded-xl bg-slate-800 px-3 py-2 text-xs font-bold text-white dark:bg-slate-700">فیلتر</button>
        <a href="<?= e(url('/reseller/numbers')) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700">✕</a>
    </div>
</form>

<div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="table-scroll">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                <tr><th class="p-3">شماره</th><th class="p-3">اپراتور</th><th class="p-3">دسته / نوع</th><th class="p-3">قیمت</th><th class="p-3">وضعیت</th><th class="p-3">تاریخ تولد</th><th class="p-3">ثبت</th><th class="p-3">بازدید</th><th class="p-3">عملیات</th></tr>
            </thead>
            <tbody>
            <?php if (($numbers['items'] ?? []) === []): ?>
                <tr><td colspan="9" class="p-8 text-center text-slate-400">شماره‌ای با این فیلترها یافت نشد. <a href="<?= e(url('/reseller/numbers/create')) ?>" class="font-bold text-teal-600">ثبت شماره جدید</a></td></tr>
            <?php endif; ?>
            <?php foreach ($numbers['items'] as $row): ?>
                <tr class="border-t border-slate-100 hover:bg-slate-50/60 dark:border-slate-800 dark:hover:bg-slate-800/30">
                    <td class="ltr p-3 font-bold tracking-wider"><?= e($row['number']) ?></td>
                    <td class="p-3"><span class="inline-flex items-center gap-1.5"><img src="<?= e(operator_logo((string) ($row['operator_slug'] ?? ''))) ?>" alt="" class="h-5 w-5 rounded object-contain"><?= e($row['operator_name']) ?></span></td>
                    <td class="p-3 text-[10px]"><span class="rounded-full px-2 py-0.5 font-bold <?= e(number_type_badge_class((string) ($row['number_type'] ?? 'other'))) ?>"><?= e(number_type_label((string) ($row['number_type'] ?? 'other'))) ?></span><span class="mt-1 block text-slate-400"><?= e(line_type_label((string) ($row['line_type'] ?? 'prepaid'))) ?></span></td>
                    <td class="p-3 font-bold"><?= price($row['price']) ?></td>
                    <td class="p-3"><span class="rounded-full px-2 py-0.5 text-[9px] font-bold <?= e(status_badge_class((string) $row['status'])) ?>"><?= e(number_status_label((string) $row['status'])) ?></span></td>
                    <td class="p-3 text-[10px]"><?= !empty($row['birth_date']) ? fa_num(jalali_date((string) $row['birth_date'])) : '—' ?></td>
                    <td class="p-3 text-[10px] text-slate-400"><?= fa_num(jalali_date((string) $row['created_at'])) ?></td>
                    <td class="p-3 text-[10px]"><?= fa_num($row['views']) ?></td>
                    <td class="p-3">
                        <?php if ($row['status'] === 'available'): ?>
                            <div class="flex gap-1">
                                <a href="<?= e(url('/reseller/numbers/' . $row['id'] . '/edit')) ?>" class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] hover:bg-teal-100 dark:bg-slate-800">ویرایش</a>
                                <form method="post" action="<?= e(url('/reseller/numbers/' . $row['id'] . '/delete')) ?>" onsubmit="return confirm('این شماره حذف شود؟')">
                                    <?= csrf_field() ?>
                                    <button class="rounded-lg bg-rose-50 px-2 py-1 text-[10px] text-rose-600 dark:bg-rose-950/40">حذف</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span class="text-[9px] text-slate-400">قابل ویرایش نیست</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= \App\Support\View::partial('partials/pagination', ['paginator' => $numbers, 'path' => '/reseller/numbers']) ?>
