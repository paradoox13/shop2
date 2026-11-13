<?php
$f = $filters ?? [];
$statuses = ['available' => 'موجود', 'reserved' => 'رزرو', 'sold' => 'فروخته‌شده', 'hidden' => 'مخفی'];
$input = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">مدیریت شماره‌ها</h2>
        <p class="mt-1 text-xs text-slate-500"><?= fa_num($numbers['total'] ?? 0) ?> شماره در سامانه</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('/admin/import')) ?>" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900">📥 ایمپورت اکسل</a>
        <a href="<?= e(url('/admin/numbers/create')) ?>" class="rounded-xl bg-teal-600 px-4 py-2 text-xs font-bold text-white hover:bg-teal-700">+ افزودن شماره</a>
    </div>
</div>

<form method="get" action="<?= e(url('/admin/numbers')) ?>" class="mt-4 rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-6">
        <input name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="شماره / توضیحات" class="<?= $input ?>">
        <select name="operator" class="<?= $input ?>">
            <option value="">همه اپراتورها</option>
            <?php foreach ($operators as $operator): ?>
                <option value="<?= (int) $operator['id'] ?>" <?= (string) ($f['operator'] ?? '') === (string) $operator['id'] ? 'selected' : '' ?>><?= e($operator['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="<?= $input ?>">
            <option value="">همه وضعیت‌ها</option>
            <?php foreach ($statuses as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($f['status'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <input name="min_price" value="<?= e($f['min_price'] ?? '') ?>" inputmode="numeric" placeholder="حداقل قیمت" class="<?= $input ?>">
        <input name="max_price" value="<?= e($f['max_price'] ?? '') ?>" inputmode="numeric" placeholder="حداکثر قیمت" class="<?= $input ?>">
        <div class="flex gap-1">
            <button class="flex-1 rounded-xl bg-slate-800 px-3 py-2 text-xs font-bold text-white hover:bg-slate-900 dark:bg-slate-700">فیلتر</button>
            <a href="<?= e(url('/admin/numbers')) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700" title="پاک کردن">✕</a>
        </div>
    </div>
    <div class="mt-2 flex flex-wrap items-center gap-2 text-[10px] text-slate-400">
        <span>الگو:</span>
        <?php foreach (['repeat' => 'تکراری', 'round' => 'رند', 'serial' => 'متوالی'] as $key => $label): ?>
            <a href="<?= e(query_url('/admin/numbers', ['pattern' => ($f['pattern'] ?? '') === $key ? null : $key])) ?>" class="rounded-full px-2 py-1 <?= ($f['pattern'] ?? '') === $key ? 'bg-teal-600 text-white' : 'bg-slate-100 hover:bg-teal-100 dark:bg-slate-800' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </div>
</form>

<div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="table-scroll">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                <tr>
                    <th class="p-3">#</th><th class="p-3">شماره</th><th class="p-3">اپراتور</th><th class="p-3">دسته / نوع</th><th class="p-3">قیمت</th>
                    <th class="p-3">وضعیت</th><th class="p-3">نماینده</th><th class="p-3">تاریخ تولد</th><th class="p-3">ثبت</th><th class="p-3">عملیات</th>
                </tr>
            </thead>
            <tbody>
            <?php if (($numbers['items'] ?? []) === []): ?>
                <tr><td colspan="10" class="p-8 text-center text-slate-400">شماره‌ای با این فیلترها یافت نشد.</td></tr>
            <?php endif; ?>
            <?php foreach ($numbers['items'] as $row): ?>
                <tr class="border-t border-slate-100 hover:bg-slate-50/60 dark:border-slate-800 dark:hover:bg-slate-800/30">
                    <td class="p-3 text-slate-400"><?= fa_num($row['id']) ?></td>
                    <td class="ltr p-3 font-bold tracking-wider"><?= e($row['number']) ?></td>
                    <td class="p-3"><span class="inline-flex items-center gap-1.5"><img src="<?= e(operator_logo((string) ($row['operator_slug'] ?? ''))) ?>" alt="" class="h-5 w-5 rounded object-contain"><?= e($row['operator_name']) ?></span></td>
                    <td class="p-3 text-[10px]"><span class="rounded-full px-2 py-0.5 font-bold <?= e(number_type_badge_class((string) ($row['number_type'] ?? 'other'))) ?>"><?= e(number_type_label((string) ($row['number_type'] ?? 'other'))) ?></span><span class="mt-1 block text-slate-400"><?= e(line_type_label((string) ($row['line_type'] ?? 'prepaid'))) ?></span></td>
                    <td class="p-3 font-bold"><?= price($row['price']) ?></td>
                    <td class="p-3"><span class="rounded-full px-2 py-0.5 text-[9px] font-bold <?= e(status_badge_class((string) $row['status'])) ?>"><?= e(number_status_label((string) $row['status'])) ?></span></td>
                    <td class="p-3 text-[10px]"><?= e($row['reseller_name'] ?? '—') ?></td>
                    <td class="p-3 text-[10px]"><?= !empty($row['birth_date']) ? fa_num(jalali_date((string) $row['birth_date'])) : '—' ?></td>
                    <td class="p-3 text-[10px] text-slate-400"><?= fa_num(jalali_date((string) $row['created_at'])) ?></td>
                    <td class="p-3">
                        <div class="flex items-center gap-1">
                            <a href="<?= e(url('/admin/numbers/' . $row['id'] . '/edit')) ?>" class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] hover:bg-teal-100 dark:bg-slate-800">ویرایش</a>
                            <?php if ($row['status'] !== 'sold'): ?>
                                <form method="post" action="<?= e(url('/admin/numbers/' . $row['id'] . '/delete')) ?>" onsubmit="return confirm('این شماره حذف شود؟')">
                                    <?= csrf_field() ?>
                                    <button class="rounded-lg bg-rose-50 px-2 py-1 text-[10px] text-rose-600 hover:bg-rose-100 dark:bg-rose-950/40">حذف</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= \App\Support\View::partial('partials/pagination', ['paginator' => $numbers, 'path' => '/admin/numbers']) ?>
