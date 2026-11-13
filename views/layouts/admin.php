<?php
$admin = auth_user();
$siteName = setting('site_name', (string) config('app.name'));

$sections = [
    'فروش و محصولات' => [
        ['/admin', 'داشبورد', '▦', true, null],
        ['/admin/numbers', 'شماره‌ها', '📱', false, 'numbers.manage'],
        ['/admin/import', 'ایمپورت اکسل', '📥', false, 'numbers.manage'],
        ['/admin/modems', 'مودم‌ها', '📶', false, 'modems.manage'],
        ['/admin/activation', 'کد فعال‌سازی', '🔑', false, 'activation.manage'],
    ],
    'سفارش‌ها و مشتریان' => [
        ['/admin/orders', 'سفارش‌ها', '🧾', false, 'orders.view'],
        ['/admin/resellers', 'نمایندگان', '👥', false, 'resellers.manage'],
        ['/admin/reports', 'گزارش فروش', '📈', false, 'reports.view'],
        ['/admin/messages', 'پیام‌های تماس', '✉️', false, null],
    ],
    'ارتباطات و سیستم' => [
        ['/admin/sms', 'پیامک‌ها', '💬', false, 'sms.view'],
        ['/admin/settings', 'تنظیمات', '⚙️', false, 'settings.manage'],
        ['/admin/audit', 'لاگ عملیات', '🛡️', false, 'audit.view'],
    ],
];
?>
<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'پنل مدیریت') . ' | ' . $siteName) ?></title>
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
            theme: {
                extend: {
                    fontFamily: { sans: ['Vazirmatn', 'Tahoma', 'system-ui'] },
                    colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a' } }
                }
            }
        };
    </script>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="min-h-screen bg-slate-100/70 text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-100" x-data="{ sidebar: false }">
<div class="flex min-h-screen">
    <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"></div>

    <aside class="fixed inset-y-0 right-0 z-50 w-72 border-l border-slate-200 bg-white transition-transform dark:border-slate-800 dark:bg-slate-900 lg:static lg:z-auto lg:translate-x-0" :class="sidebar ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
        <div class="flex h-16 items-center gap-2 border-b border-slate-100 px-5 dark:border-slate-800">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-emerald-700 text-white">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 18h4"/></svg>
            </span>
            <div class="leading-tight">
                <span class="block text-sm font-extrabold"><?= e($siteName) ?></span>
                <span class="text-[10px] text-slate-400">پنل مدیریت</span>
            </div>
            <button type="button" @click="sidebar = false" class="mr-auto rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800" aria-label="بستن منو">✕</button>
        </div>

        <nav class="h-[calc(100vh-9rem)] overflow-y-auto px-3 py-4" aria-label="منوی مدیریت">
            <?php foreach ($sections as $sectionTitle => $items): ?>
                <?php
                $visible = array_values(array_filter($items, static fn (array $item): bool => $item[4] === null || can((string) $item[4])));
                if ($visible === []) {
                    continue;
                }
                ?>
                <p class="mb-1 mt-4 px-2 text-[10px] font-bold uppercase tracking-wide text-slate-400 first:mt-0"><?= e($sectionTitle) ?></p>
                <ul class="space-y-0.5">
                    <?php foreach ($visible as [$href, $label, $icon, $exact, $permission]): ?>
                        <?php $active = is_active($href, $exact); ?>
                        <li>
                            <a href="<?= e(url($href)) ?>" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm transition <?= $active ? 'bg-teal-600 font-bold text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' ?>">
                                <span class="w-5 text-center text-base" aria-hidden="true"><?= $icon ?></span>
                                <span><?= e($label) ?></span>
                                <?php if ($active): ?><span class="mr-auto h-1.5 w-1.5 rounded-full bg-white/80"></span><?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </nav>

        <div class="absolute inset-x-0 bottom-0 border-t border-slate-100 p-3 dark:border-slate-800">
            <a href="<?= e(url('/')) ?>" target="_blank" class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600 hover:bg-teal-50 dark:bg-slate-800 dark:text-slate-300">
                <span>مشاهده فروشگاه</span><span aria-hidden="true">↗</span>
            </a>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur-nav dark:border-slate-800 dark:bg-slate-900/90">
            <button type="button" @click="sidebar = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800" aria-label="باز کردن منو">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
            <div class="min-w-0">
                <h1 class="truncate text-sm font-extrabold sm:text-base"><?= e($title ?? 'پنل مدیریت') ?></h1>
                <p class="hidden text-[10px] text-slate-400 sm:block"><?= fa_num(jalali_date(date('Y-m-d'))) ?></p>
            </div>
            <div class="mr-auto flex items-center gap-1.5">
                <button type="button" onclick="toggleTheme()" class="rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="تغییر تم"><span data-theme-icon>🌙</span></button>
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl border border-slate-200 px-2.5 py-1.5 text-xs hover:border-teal-400 dark:border-slate-700" :aria-expanded="open">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-teal-100 font-bold text-teal-800 dark:bg-teal-900 dark:text-teal-200"><?= e(mb_substr((string) ($admin['full_name'] ?? $admin['username'] ?? 'A'), 0, 1)) ?></span>
                        <span class="hidden sm:inline"><?= e($admin['full_name'] ?? $admin['username'] ?? '') ?></span>
                        <span class="text-[9px] text-slate-400"><?= e(role_label((string) ($admin['role'] ?? ''))) ?></span>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute left-0 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-xl dark:border-slate-700 dark:bg-slate-900">
                        <a href="<?= e(url('/admin/settings/2fa')) ?>" class="block px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">تأیید دو مرحله‌ای</a>
                        <a href="<?= e(url('/')) ?>" target="_blank" class="block px-4 py-2 hover:bg-slate-50 dark:hover:bg-slate-800">مشاهده سایت</a>
                        <form method="post" action="<?= e(url('/logout')) ?>">
                            <?= csrf_field() ?>
                            <button class="block w-full px-4 py-2 text-right text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30">خروج</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            <?= \App\Support\View::partial('partials/flash') ?>
            <?= $content ?>
        </main>

        <footer class="border-t border-slate-200 px-6 py-3 text-center text-[10px] text-slate-400 dark:border-slate-800">
            <?= e($siteName) ?> — پنل مدیریت نسخه ۱٫۰
        </footer>
    </div>
</div>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
<script defer src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
