<?php
$user = auth_user();
$siteName = setting('site_name', (string) config('app.name'));
$supportPhone = setting('support_phone', (string) config('app.support_phone'));
?>
<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? $siteName) ?></title>
    <meta name="description" content="<?= e($metaDescription ?? '') ?>">
    <meta name="theme-color" content="#0d9488">
    <meta property="og:title" content="<?= e($title ?? $siteName) ?>">
    <meta property="og:description" content="<?= e($metaDescription ?? '') ?>">
    <meta property="og:type" content="website">
    <link rel="icon" href="<?= e(asset('img/placeholder.svg')) ?>" type="image/svg+xml">
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

            // مستقل از app.js؛ اگر فایل استاتیک دیر بارگذاری شود دکمه باز هم کار می‌کند.
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
            theme: {
                extend: {
                    fontFamily: { sans: ['Vazirmatn', 'Tahoma', 'system-ui'] },
                    colors: {
                        brand: {
                            50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4',
                            400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0f766e',
                            800: '#115e59', 900: '#134e4a'
                        }
                    }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-100">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:right-2 focus:z-50 focus:rounded-lg focus:bg-teal-600 focus:px-4 focus:py-2 focus:text-white">پرش به محتوا</a>

<div class="bg-brand-900 px-4 py-1.5 text-center text-xs text-brand-50">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-1">
        <span class="flex items-center gap-1.5">
            <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
            تحویل فوری شماره، پشتیبانی واقعی
        </span>
        <span class="flex items-center gap-4">
            <a href="/faq" class="hover:text-white">سوالات متداول</a>
            <?php if ($supportPhone !== ''): ?>
                <a href="tel:<?= e(preg_replace('/\D+/', '', $supportPhone)) ?>" class="ltr font-medium hover:text-white"><?= fa_num($supportPhone) ?></a>
            <?php endif; ?>
        </span>
    </div>
</div>

<header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/85 backdrop-blur-nav dark:border-slate-800 dark:bg-slate-900/85">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4">
        <a href="<?= e(url('/')) ?>" class="flex shrink-0 items-center gap-2" aria-label="<?= e($siteName) ?>">
            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-700 text-white shadow-lg shadow-teal-900/20">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 18h4"/></svg>
            </span>
            <span class="leading-tight">
                <span class="block text-lg font-extrabold text-slate-900 dark:text-white"><?= e($siteName) ?></span>
                <span class="hidden text-[11px] text-slate-500 sm:block dark:text-slate-400"><?= e(setting('site_tagline')) ?></span>
            </span>
        </a>

        <nav class="mr-4 hidden items-center gap-1 lg:flex" aria-label="منوی اصلی">
            <a href="<?= e(url('/')) ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-teal-50 hover:text-teal-700 dark:hover:bg-slate-800">خانه</a>
            <a href="<?= e(url('/#numbers')) ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-teal-50 hover:text-teal-700 dark:hover:bg-slate-800">شماره‌ها</a>
            <a href="<?= e(url('/#activation')) ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-teal-50 hover:text-teal-700 dark:hover:bg-slate-800">کد فعال‌سازی</a>
            <a href="<?= e(url('/#modems')) ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-teal-50 hover:text-teal-700 dark:hover:bg-slate-800">مودم</a>
            <a href="<?= e(url('/about')) ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-teal-50 hover:text-teal-700 dark:hover:bg-slate-800">درباره ما</a>
            <a href="<?= e(url('/contact')) ?>" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-teal-50 hover:text-teal-700 dark:hover:bg-slate-800">تماس</a>
        </nav>

        <form action="<?= e(url('/')) ?>" method="get" class="mr-auto hidden max-w-xs flex-1 md:block" role="search">
            <label for="header-search" class="sr-only">جستجوی شماره</label>
            <div class="relative">
                <input id="header-search" type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="جستجوی شماره یا پسوند…" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pr-9 pl-3 text-sm outline-none transition focus:border-teal-500 focus:bg-white dark:border-slate-700 dark:bg-slate-800 dark:focus:bg-slate-900">
                <svg class="absolute right-3 top-2.5 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </div>
        </form>

        <div class="flex items-center gap-1">
            <button type="button" onclick="toggleTheme()" class="rounded-lg p-2 text-lg hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="تغییر حالت روشن/تیره" title="حالت روشن/تیره">
                <span data-theme-icon aria-hidden="true">🌙</span>
            </button>
            <a href="<?= e(url('/cart')) ?>" class="relative rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="سبد خرید">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.4 12.2a2 2 0 0 0 2 1.8h8.7a2 2 0 0 0 2-1.6L22 7H6"/></svg>
                <?php if (cart_count() > 0): ?>
                    <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"><?= fa_num(cart_count()) ?></span>
                <?php endif; ?>
            </a>

            <?php if ($user !== null): ?>
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex items-center gap-1.5 rounded-xl border border-slate-200 px-2.5 py-1.5 text-sm hover:border-teal-400 dark:border-slate-700" aria-haspopup="true" :aria-expanded="open">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800 dark:bg-teal-900 dark:text-teal-200"><?= e(mb_substr((string) ($user['full_name'] ?? $user['username']), 0, 1)) ?></span>
                        <span class="hidden sm:inline"><?= e(truncate((string) ($user['full_name'] ?? $user['username']), 18)) ?></span>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute left-0 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                        <?php if (\App\Support\Auth::isStaff()): ?>
                            <a href="<?= e(url('/admin')) ?>" class="block px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">پنل مدیریت</a>
                        <?php elseif (\App\Support\Auth::role() === 'reseller'): ?>
                            <a href="<?= e(url('/reseller')) ?>" class="block px-4 py-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800">پنل نماینده</a>
                        <?php endif; ?>
                        <form action="<?= e(url('/logout')) ?>" method="post">
                            <?= csrf_field() ?>
                            <button type="submit" class="block w-full px-4 py-2 text-right text-sm text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30">خروج از حساب</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= e(url('/login')) ?>" class="hidden rounded-xl border border-slate-200 px-3 py-1.5 text-sm font-medium hover:border-teal-400 hover:text-teal-700 sm:block dark:border-slate-700">ورود</a>
                <a href="<?= e(url('/register')) ?>" class="rounded-xl bg-teal-600 px-3 py-1.5 text-sm font-bold text-white shadow-sm hover:bg-teal-700">ثبت‌نام</a>
            <?php endif; ?>

            <button type="button" class="rounded-lg p-2 lg:hidden" x-data @click="$refs.mobileMenu.classList.toggle('hidden')" aria-label="منوی موبایل">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
        </div>
    </div>

    <div x-ref="mobileMenu" class="hidden border-t border-slate-200 bg-white px-4 py-3 lg:hidden dark:border-slate-800 dark:bg-slate-900">
        <form action="<?= e(url('/')) ?>" method="get" class="mb-3">
            <input type="search" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="جستجوی شماره…" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800">
        </form>
        <div class="grid grid-cols-2 gap-1 text-sm">
            <a href="<?= e(url('/')) ?>" class="rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">خانه</a>
            <a href="<?= e(url('/#numbers')) ?>" class="rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">شماره‌ها</a>
            <a href="<?= e(url('/#activation')) ?>" class="rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">کد فعال‌سازی</a>
            <a href="<?= e(url('/#modems')) ?>" class="rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">مودم</a>
            <a href="<?= e(url('/about')) ?>" class="rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">درباره ما</a>
            <a href="<?= e(url('/contact')) ?>" class="rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">تماس</a>
            <?php if ($user === null): ?>
                <a href="<?= e(url('/login')) ?>" class="rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">ورود</a>
                <a href="<?= e(url('/reseller/login')) ?>" class="rounded-lg px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">ورود نمایندگان</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main id="main" class="mx-auto w-full max-w-7xl px-4 py-6">
    <?= \App\Support\View::partial('partials/flash') ?>
    <?= $content ?>
</main>

<footer class="mt-16 border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <div class="mb-3 flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-600 text-white">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 18h4"/></svg>
                </span>
                <span class="text-lg font-extrabold"><?= e($siteName) ?></span>
            </div>
            <p class="text-sm leading-7 text-slate-500 dark:text-slate-400"><?= e(setting('footer_about')) ?></p>
        </div>
        <div>
            <h2 class="mb-3 text-sm font-bold">دسترسی سریع</h2>
            <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                <li><a class="hover:text-teal-600" href="<?= e(url('/')) ?>">خرید سیم‌کارت</a></li>
                <li><a class="hover:text-teal-600" href="<?= e(url('/#activation')) ?>">کد فعال‌سازی</a></li>
                <li><a class="hover:text-teal-600" href="<?= e(url('/#modems')) ?>">مودم</a></li>
                <li><a class="hover:text-teal-600" href="<?= e(url('/faq')) ?>">سوالات متداول</a></li>
            </ul>
        </div>
        <div>
            <h2 class="mb-3 text-sm font-bold">قوانین</h2>
            <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                <li><a class="hover:text-teal-600" href="<?= e(url('/terms')) ?>">قوانین و مقررات</a></li>
                <li><a class="hover:text-teal-600" href="<?= e(url('/privacy')) ?>">حریم خصوصی</a></li>
                <li><a class="hover:text-teal-600" href="<?= e(url('/about')) ?>">درباره ما</a></li>
                <li><a class="hover:text-teal-600" href="<?= e(url('/contact')) ?>">تماس با ما</a></li>
            </ul>
        </div>
        <div>
            <h2 class="mb-3 text-sm font-bold">ارتباط با ما</h2>
            <ul class="space-y-2 text-sm text-slate-500 dark:text-slate-400">
                <?php if (setting('support_phone') !== ''): ?>
                    <li>تلفن: <a class="ltr inline-block hover:text-teal-600" href="tel:<?= e(preg_replace('/\D+/', '', setting('support_phone'))) ?>"><?= fa_num(setting('support_phone')) ?></a></li>
                <?php endif; ?>
                <?php if (setting('support_email') !== ''): ?>
                    <li>ایمیل: <a class="hover:text-teal-600" href="mailto:<?= e(setting('support_email')) ?>"><?= e(setting('support_email')) ?></a></li>
                <?php endif; ?>
                <?php if (setting('address') !== ''): ?>
                    <li><?= e(setting('address')) ?></li>
                <?php endif; ?>
            </ul>
            <div class="mt-4 flex gap-3 text-slate-400">
                <?php if (setting('instagram') !== ''): ?>
                    <a href="<?= e(setting('instagram')) ?>" rel="noopener nofollow" target="_blank" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs hover:border-teal-400 dark:border-slate-700">اینستاگرام</a>
                <?php endif; ?>
                <?php if (setting('telegram') !== ''): ?>
                    <a href="<?= e(setting('telegram')) ?>" rel="noopener nofollow" target="_blank" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs hover:border-teal-400 dark:border-slate-700">تلگرام</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="border-t border-slate-100 py-5 dark:border-slate-800">
        <div class="flex justify-center">
            <a referrerpolicy="origin" target="_blank" rel="noopener noreferrer" href="https://trustseal.enamad.ir/?id=335723&amp;Code=KRTmIooxMU2zusKC1mEj" title="نماد اعتماد الکترونیکی">
                <img referrerpolicy="origin" src="https://trustseal.enamad.ir/logo.aspx?id=335723&amp;Code=KRTmIooxMU2zusKC1mEj" alt="نماد اعتماد الکترونیکی" code="KRTmIooxMU2zusKC1mEj" loading="lazy" class="h-24 w-auto">
            </a>
        </div>
        <p class="mt-2 text-center text-xs text-slate-400">© <?= fa_num(jdate('Y')) ?> <?= e($siteName) ?> — تمام حقوق محفوظ است. طراحی‌شده برای خرید مطمئن.</p>
    </div>
</footer>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
<script defer src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
