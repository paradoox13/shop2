<?php $f = $filters ?? []; ?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">لاگ عملیات</h2>
        <p class="mt-1 text-xs text-slate-500">۲۰۰ رویداد آخر — برای پیگیری اقدامات حساس روی سیستم.</p>
    </div>
    <form method="get" action="<?= e(url('/admin/audit')) ?>" class="flex gap-1">
        <input name="action" value="<?= e($f['action'] ?? '') ?>" placeholder="مثلاً order.status_changed" class="w-48 rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900">
        <button class="rounded-xl bg-slate-800 px-3 py-2 text-xs font-bold text-white dark:bg-slate-700">فیلتر</button>
    </form>
</div>

<div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="table-scroll">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                <tr><th class="p-3">#</th><th class="p-3">کاربر</th><th class="p-3">عملیات</th><th class="p-3">موجودیت</th><th class="p-3">جزئیات</th><th class="p-3">IP</th><th class="p-3">زمان</th></tr>
            </thead>
            <tbody>
            <?php if ($logs === []): ?>
                <tr><td colspan="7" class="p-8 text-center text-slate-400">رویدادی ثبت نشده است.</td></tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <tr class="border-t border-slate-100 dark:border-slate-800">
                    <td class="p-3 text-slate-400"><?= fa_num($log['id']) ?></td>
                    <td class="p-3"><?= e($log['username'] ?? 'سیستم') ?></td>
                    <td class="p-3"><code class="ltr rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-700 dark:bg-slate-800 dark:text-slate-300"><?= e($log['action']) ?></code></td>
                    <td class="p-3 text-[10px] text-slate-500"><?= e($log['entity'] ?? '—') ?><?= $log['entity_id'] !== null ? ' #' . fa_num((string) $log['entity_id']) : '' ?></td>
                    <td class="ltr max-w-[15rem] truncate p-3 text-[9px] text-slate-400" title="<?= e($log['meta'] ?? '') ?>"><?= e(truncate((string) ($log['meta'] ?? '—'), 60)) ?></td>
                    <td class="ltr p-3 text-[10px] text-slate-400"><?= e($log['ip'] ?? '—') ?></td>
                    <td class="p-3 text-[10px] text-slate-400"><?= fa_num(jalali_datetime((string) $log['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
