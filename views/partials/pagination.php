<?php
$paginator = $paginator ?? ['page' => 1, 'pages' => 1];
$path = $path ?? current_path();
$pages = (int) ($paginator['pages'] ?? 1);
$page = (int) ($paginator['page'] ?? 1);

if ($pages <= 1) {
    return;
}

$start = max(1, $page - 2);
$end = min($pages, $page + 2);
?>
<nav class="mt-8 flex flex-wrap items-center justify-center gap-1.5 text-sm" aria-label="صفحه‌بندی">
    <?php if ($page > 1): ?>
        <a href="<?= e(query_url($path, ['page' => $page - 1])) ?>" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:border-teal-400 hover:text-teal-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200" rel="prev">قبلی</a>
    <?php endif; ?>

    <?php if ($start > 1): ?>
        <a href="<?= e(query_url($path, ['page' => 1])) ?>" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">۱</a>
        <?php if ($start > 2): ?><span class="px-1 text-slate-400">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
        <?php if ($i === $page): ?>
            <span aria-current="page" class="rounded-lg bg-teal-600 px-3.5 py-2 font-bold text-white shadow-sm"><?= fa_num($i) ?></span>
        <?php else: ?>
            <a href="<?= e(query_url($path, ['page' => $i])) ?>" class="rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-slate-700 hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><?= fa_num($i) ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($end < $pages): ?>
        <?php if ($end < $pages - 1): ?><span class="px-1 text-slate-400">…</span><?php endif; ?>
        <a href="<?= e(query_url($path, ['page' => $pages])) ?>" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><?= fa_num($pages) ?></a>
    <?php endif; ?>

    <?php if ($page < $pages): ?>
        <a href="<?= e(query_url($path, ['page' => $page + 1])) ?>" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-slate-700 hover:border-teal-400 hover:text-teal-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200" rel="next">بعدی</a>
    <?php endif; ?>
</nav>
