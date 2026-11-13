<?php
$statusLabels = ['pending' => 'در انتظار', 'processing' => 'در حال پردازش', 'completed' => 'تکمیل شده', 'failed' => 'ناموفق'];
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">ایمپورت گروهی اکسل / CSV</h2>
        <p class="mt-1 text-xs text-slate-500">شماره‌ها یا مودم‌ها را با فایل Excel یا CSV وارد کنید. پردازش به‌صورت ۱۰۰ ردیفی انجام می‌شود.</p>
    </div>
    <div class="flex gap-1.5 text-[10px]">
        <a href="<?= e(url('/admin/import/template/number')) ?>" class="rounded-xl border border-slate-200 bg-white px-3 py-2 hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900">قالب CSV شماره</a>
        <a href="<?= e(url('/admin/import/template/modem')) ?>" class="rounded-xl border border-slate-200 bg-white px-3 py-2 hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900">قالب CSV مودم</a>
    </div>
</div>

<div class="mt-4 grid gap-4 xl:grid-cols-[1fr_1.4fr]">
    <section class="h-fit rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <h3 class="text-sm font-bold">آپلود فایل جدید</h3>
        <form method="post" action="<?= e(url('/admin/import/upload')) ?>" enctype="multipart/form-data" class="mt-4 space-y-3">
            <?= csrf_field() ?>
            <div>
                <label class="mb-1 block text-xs text-slate-500">نوع داده</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-2.5 text-xs has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50 dark:border-slate-700 dark:has-[:checked]:bg-teal-950/30">
                        <input type="radio" name="type" value="number" checked class="text-teal-600">
                        شماره سیم‌کارت
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-2.5 text-xs has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50 dark:border-slate-700 dark:has-[:checked]:bg-teal-950/30">
                        <input type="radio" name="type" value="modem" class="text-teal-600">
                        مودم
                    </label>
                </div>
            </div>
            <div>
                <label for="file" class="mb-1 block text-xs text-slate-500">فایل CSV یا XLSX (حداکثر ۵ مگابایت / ۵۰۰۰ ردیف)</label>
                <input id="file" name="file" type="file" required accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-[10px] file:ml-2 file:rounded-lg file:border-0 file:bg-teal-600 file:px-3 file:py-1.5 file:text-white dark:border-slate-700 dark:bg-slate-800/50">
            </div>
            <button class="w-full rounded-xl bg-teal-600 py-2.5 text-sm font-bold text-white hover:bg-teal-700">آپلود و شروع</button>
        </form>

        <div class="mt-4 rounded-xl bg-slate-50 p-3 text-[10px] leading-6 text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
            <p class="font-bold text-slate-600 dark:text-slate-300">ستون‌های شماره:</p>
            <p class="ltr">number, price, operator, category, line_type, birth_date, description</p>
            <p class="mt-2 font-bold text-slate-600 dark:text-slate-300">دسته‌بندی‌ها:</p>
            <p class="ltr">round, family, super_round, serial, birthday, other</p>
            <p class="ltr">line_type: prepaid / permanent</p>
            <p class="mt-2">ردیف اول باید سرستون باشد. شماره تکراری رد می‌شود و بقیه ادامه پیدا می‌کنند.</p>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800">
            <h3 class="text-sm font-bold">تاریخچه ایمپورت‌ها</h3>
            <span class="text-[10px] text-slate-400"><?= fa_num($logs['total'] ?? 0) ?> فایل</span>
        </div>
        <div class="table-scroll">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                    <tr><th class="p-3">#</th><th class="p-3">نوع</th><th class="p-3">ثبت‌کننده</th><th class="p-3">پیشرفت</th><th class="p-3">موفق/ناموفق</th><th class="p-3">وضعیت</th><th class="p-3">تاریخ</th><th class="p-3"></th></tr>
                </thead>
                <tbody>
                <?php if (($logs['items'] ?? []) === []): ?>
                    <tr><td colspan="8" class="p-8 text-center text-slate-400">ایمپورتی انجام نشده است.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs['items'] as $log): ?>
                    <?php
                    $totalRows = max(0, (int) $log['total_rows']);
                    $processed = min($totalRows, (int) $log['processed_rows']);
                    $percent = $totalRows > 0 ? (int) floor($processed / $totalRows * 100) : 0;
                    ?>
                    <tr class="border-t border-slate-100 dark:border-slate-800">
                        <td class="p-3 text-slate-400"><?= fa_num($log['id']) ?></td>
                        <td class="p-3"><?= $log['type'] === 'modem' ? 'مودم' : 'شماره' ?></td>
                        <td class="p-3 text-[10px]"><?= e($log['reseller_name'] ?? 'مدیر') ?></td>
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                    <div class="h-full rounded-full <?= $log['status'] === 'failed' ? 'bg-rose-500' : 'bg-teal-500' ?>" style="width: <?= $percent ?>%"></div>
                                </div>
                                <span class="text-[9px] text-slate-400"><?= fa_num($processed) ?>/<?= fa_num($totalRows) ?></span>
                            </div>
                        </td>
                        <td class="p-3 text-[10px]"><span class="text-emerald-600"><?= fa_num($log['inserted_rows']) ?></span> / <span class="text-rose-600"><?= fa_num($log['failed_rows']) ?></span></td>
                        <td class="p-3"><span class="rounded-full px-2 py-0.5 text-[9px] font-bold <?= e(status_badge_class((string) $log['status'])) ?>"><?= e($statusLabels[(string) $log['status']] ?? $log['status']) ?></span></td>
                        <td class="p-3 text-[10px] text-slate-400"><?= fa_num(jalali_datetime((string) $log['created_at'])) ?></td>
                        <td class="p-3"><a href="<?= e(url('/admin/import/' . $log['id'])) ?>" class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] hover:bg-teal-100 dark:bg-slate-800">جزئیات</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= \App\Support\View::partial('partials/pagination', ['paginator' => $logs, 'path' => '/admin/import']) ?>
    </section>
</div>
