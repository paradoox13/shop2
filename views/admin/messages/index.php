<?php ?>
<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-lg font-extrabold">پیام‌های تماس</h2>
        <p class="mt-1 text-xs text-slate-500"><?= fa_num($messages['total'] ?? 0) ?> پیام از فرم تماس</p>
    </div>
    <a href="<?= e(url('/contact')) ?>" target="_blank" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[10px] hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900">فرم تماس ↗</a>
</div>

<div class="mt-4 space-y-3">
    <?php if (($messages['items'] ?? []) === []): ?>
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-400 dark:border-slate-700 dark:bg-slate-900">پیامی ثبت نشده است.</div>
    <?php endif; ?>
    <?php foreach ($messages['items'] as $message): ?>
        <article class="rounded-2xl border <?= $message['status'] === 'new' ? 'border-teal-300 bg-teal-50/30 dark:border-teal-800' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' ?> p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold"><?= e($message['subject']) ?></h3>
                    <p class="mt-1 text-[10px] text-slate-500">
                        <?= e($message['name']) ?>
                        <?php if (!empty($message['phone'])): ?> — <span class="ltr"><?= e($message['phone']) ?></span><?php endif; ?>
                        <?php if (!empty($message['email'])): ?> — <span class="ltr"><?= e($message['email']) ?></span><?php endif; ?>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($message['status'] === 'new'): ?><span class="rounded-full bg-teal-600 px-2 py-0.5 text-[9px] font-bold text-white">جدید</span><?php endif; ?>
                    <span class="text-[9px] text-slate-400"><?= fa_num(jalali_datetime((string) $message['created_at'])) ?></span>
                </div>
            </div>
            <p class="mt-3 whitespace-pre-line rounded-xl bg-white p-3 text-xs leading-7 text-slate-600 dark:bg-slate-800/60 dark:text-slate-300"><?= e($message['message']) ?></p>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-[10px]">
                <span class="text-slate-400">IP: <span class="ltr"><?= e($message['ip'] ?? '—') ?></span></span>
                <?php if (!empty($message['phone'])): ?><a href="tel:<?= e(preg_replace('/\D+/', '', (string) $message['phone'])) ?>" class="rounded-lg bg-slate-100 px-2 py-1 hover:bg-teal-100 dark:bg-slate-800">تماس</a><?php endif; ?>
                <?php if (!empty($message['email'])): ?><a href="mailto:<?= e($message['email']) ?>" class="rounded-lg bg-slate-100 px-2 py-1 hover:bg-teal-100 dark:bg-slate-800">ایمیل</a><?php endif; ?>
                <?php if ($message['status'] === 'new'): ?>
                    <form method="post" action="<?= e(url('/admin/messages/' . $message['id'] . '/read')) ?>" class="mr-auto">
                        <?= csrf_field() ?>
                        <button class="rounded-lg bg-teal-600 px-2.5 py-1 font-bold text-white hover:bg-teal-700">خوانده شد</button>
                    </form>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?= \App\Support\View::partial('partials/pagination', ['paginator' => $messages, 'path' => '/admin/messages']) ?>
