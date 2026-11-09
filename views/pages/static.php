<?php ?>
<div class="mx-auto max-w-4xl">
    <nav class="mb-4 flex items-center gap-2 text-xs text-slate-500">
        <a href="<?= e(url('/')) ?>" class="hover:text-teal-600">خانه</a><span>/</span><span><?= e($pageTitle) ?></span>
    </nav>
    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-slate-800 dark:bg-slate-900">
        <h1 class="text-xl font-extrabold sm:text-2xl"><?= e($pageTitle) ?></h1>
        <div class="prose prose-sm mt-5 max-w-none leading-8 text-slate-700 dark:text-slate-300">
            <?= $pageContent !== '' ? $pageContent : '<p class="text-slate-500">محتوای این صفحه هنوز تنظیم نشده است.</p>' ?>
        </div>
        <div class="mt-8 flex flex-wrap gap-2 border-t border-dashed border-slate-200 pt-5 text-sm dark:border-slate-700">
            <a href="<?= e(url('/contact')) ?>" class="rounded-xl bg-teal-600 px-4 py-2 font-bold text-white">تماس با پشتیبانی</a>
            <a href="<?= e(url('/')) ?>" class="rounded-xl border border-slate-200 px-4 py-2 hover:border-teal-400 dark:border-slate-700">بازگشت به فروشگاه</a>
        </div>
    </article>
</div>
