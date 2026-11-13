<?php $siteName = setting('site_name', (string) config('app.name')); ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'ورود') ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
            document.documentElement.style.colorScheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';

            function syncIcons() {
                var dark = document.documentElement.classList.contains('dark');
                document.querySelectorAll('[data-theme-icon]').forEach(function (el) {
                    el.textContent = dark ? '☀️' : '🌙';
                });
            }

            window.toggleTheme = function () {
                var html = document.documentElement;
                html.classList.toggle('dark');
                var dark = html.classList.contains('dark');
                html.style.colorScheme = dark ? 'dark' : 'light';
                try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
                syncIcons();
            };

            document.addEventListener('DOMContentLoaded', syncIcons);
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { fontFamily: { sans: ['Vazirmatn', 'Tahoma', 'system-ui'] } } }
        };
    </script>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-100">
<div class="grid min-h-screen lg:grid-cols-2">
    <main class="flex items-center justify-center p-5 sm:p-8">
        <div class="w-full max-w-md">
            <div class="mb-6 flex items-center justify-between">
                <a href="<?= e(url('/')) ?>" class="flex items-center gap-2">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-700 text-white">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 18h4"/></svg>
                    </span>
                    <span class="text-lg font-extrabold text-slate-900 dark:text-white"><?= e($siteName) ?></span>
                </a>
                <button type="button" onclick="toggleTheme()" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm dark:border-slate-700" aria-label="تغییر تم"><span data-theme-icon>🌙</span></button>
            </div>

            <?= \App\Support\View::partial('partials/flash') ?>
            <?= $content ?>

            <p class="mt-7 text-center text-[11px] text-slate-400">
                <a href="<?= e(url('/')) ?>" class="hover:text-teal-600">بازگشت به فروشگاه</a>
                <span class="mx-2">|</span>
                <a href="<?= e(url('/terms')) ?>" class="hover:text-teal-600">قوانین</a>
                <span class="mx-2">|</span>
                <a href="<?= e(url('/privacy')) ?>" class="hover:text-teal-600">حریم خصوصی</a>
            </p>
        </div>
    </main>
    <aside class="relative hidden overflow-hidden bg-gradient-to-bl from-teal-900 via-teal-800 to-emerald-700 p-12 text-white lg:flex lg:flex-col lg:justify-center">
        <div class="absolute -left-20 -top-20 h-72 w-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -bottom-24 right-0 h-80 w-80 rounded-full bg-emerald-400/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative">
            <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs">فروشگاه تخصصی سیم‌کارت و مودم</span>
            <h1 class="mt-5 text-3xl font-extrabold leading-tight">خرید مطمئن، تحویل سریع.</h1>
            <p class="mt-3 max-w-md text-sm leading-8 text-teal-50/90">شماره‌های رند و تاریخ تولد، مودم‌های LTE و کد فعال‌سازی؛ با پیگیری سفارش و پشتیبانی واقعی.</p>
            <ul class="mt-6 space-y-3 text-sm text-teal-50/90">
                <li>✓ پرداخت از طریق درگاه بانکی معتبر</li>
                <li>✓ رزرو خودکار شماره هنگام پرداخت</li>
                <li>✓ پنل اختصاصی نمایندگان و گزارش فروش</li>
            </ul>
        </div>
    </aside>
</div>
<script defer src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
