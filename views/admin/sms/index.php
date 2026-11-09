<?php
$f = $filters ?? [];
$statuses = ['queued' => 'در صف', 'sent' => 'ارسال شده', 'failed' => 'ناموفق'];
?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">پیامک‌ها</h2>
        <p class="mt-1 text-xs text-slate-500"><?= fa_num($logs['total'] ?? 0) ?> پیامک ثبت شده — ارسال از طریق صف و Cron انجام می‌شود.</p>
    </div>
    <div class="flex flex-wrap gap-1.5 text-[10px]">
        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">صف: <?= fa_num($stats['queued'] ?? 0) ?></span>
        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">ارسال: <?= fa_num($stats['sent'] ?? 0) ?></span>
        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">ناموفق: <?= fa_num($stats['failed'] ?? 0) ?></span>
    </div>
</div>

<form method="get" action="<?= e(url('/admin/sms')) ?>" class="mt-4 grid gap-2 rounded-2xl border border-slate-200 bg-white p-3 sm:grid-cols-4 dark:border-slate-800 dark:bg-slate-900">
    <input name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="شماره یا متن پیامک" class="rounded-xl border border-slate-200 px-3 py-2 text-xs sm:col-span-2 dark:border-slate-700 dark:bg-slate-900">
    <select name="status" class="rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900">
        <option value="">همه وضعیت‌ها</option>
        <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($f['status'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="flex gap-1">
        <button class="flex-1 rounded-xl bg-slate-800 px-3 py-2 text-xs font-bold text-white dark:bg-slate-700">فیلتر</button>
        <a href="<?= e(url('/admin/sms')) ?>" class="rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700">✕</a>
    </div>
</form>

<div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="table-scroll">
        <table class="w-full text-xs">
            <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                <tr><th class="p-3">شماره</th><th class="p-3">قالب</th><th class="p-3">متن</th><th class="p-3">وضعیت</th><th class="p-3">تلاش</th><th class="p-3">پاسخ سرویس</th><th class="p-3">تاریخ</th><th class="p-3"></th></tr>
            </thead>
            <tbody>
            <?php if (($logs['items'] ?? []) === []): ?>
                <tr><td colspan="8" class="p-8 text-center text-slate-400">پیامکی یافت نشد.</td></tr>
            <?php endif; ?>
            <?php foreach ($logs['items'] as $log): ?>
                <tr class="border-t border-slate-100 dark:border-slate-800">
                    <td class="ltr p-3 font-medium"><?= e($log['phone']) ?></td>
                    <td class="p-3 text-[10px]"><?= e($log['template'] ?? '—') ?></td>
                    <td class="max-w-xs p-3 text-[10px] leading-5"><?= e(truncate((string) $log['message'], 70)) ?></td>
                    <td class="p-3"><span class="rounded-full px-2 py-0.5 text-[9px] font-bold <?= e(status_badge_class((string) $log['status'])) ?>"><?= e($statuses[(string) $log['status']] ?? $log['status']) ?></span></td>
                    <td class="p-3 text-center"><?= fa_num($log['attempts'] ?? 0) ?></td>
                    <td class="ltr max-w-[10rem] truncate p-3 text-[9px] text-slate-400" title="<?= e($log['provider_response'] ?? '') ?>"><?= e(truncate((string) ($log['provider_response'] ?? '—'), 39)) ?></td>
                    <td class="p-3 text-[10px] text-slate-400"><?= fa_num(jalali_datetime((string) $log['created_at'])) ?></td>
                    <td class="p-3">
                        <?php if ($log['status'] !== 'sent'): ?>
                            <form method="post" action="<?= e(url('/admin/sms/' . $log['id'] . '/retry')) ?>">
                                <?= csrf_field() ?>
                                <button class="rounded-lg bg-teal-50 px-2 py-1 text-[10px] font-bold text-teal-700 hover:bg-teal-100 dark:bg-teal-950/40 dark:text-teal-300">Retry</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= \App\Support\View::partial('partials/pagination', ['paginator' => $logs, 'path' => '/admin/sms']) ?>
