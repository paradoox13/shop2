<?php
$reseller = auth_user();
$profile = $profile ?? \App\Models\User::resellerProfile((int) ($reseller['id'] ?? 0));
$siteName = setting('site_name', (string) config('app.name'));
$displayName = $profile['display_name'] ?? $reseller['full_name'] ?? $reseller['username'] ?? '';

$nav = [
    ['/reseller', 'داشبورد', '▦', true],
    ['/reseller/numbers', 'شماره‌های من', '📱', false],
    ['/reseller/modems', 'مودم‌های من', '📶', false],
    ['/reseller/import', 'ایمپورت اکسل', '⬆️', false],
    ['/reseller/sales', 'فروش و تسویه', '📈', false],
];
?>
<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'پنل نماینده') . ' | ' . $siteName) ?></title>
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
<body class="min-h-screen bg-slate-100/70 text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-100" x-data="{ sidebar: false }">
<div class="flex min-h-screen">
    <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"></div>

    <aside class="fixed inset-y-0 right-0 z-50 w-72 border-l border-slate-200 bg-gradient-to-b from-white to-teal-50/40 transition-transform dark:border-slate-800 dark:from-slate-900 dark:to-teal-950/20 lg:static lg:translate-x-0" :class="sidebar ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
        <div class="flex h-16 items-center gap-2 border-b border-slate-100 px-5 dark:border-slate-800">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-emerald-700 text-white">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 18h4"/></svg>
            </span>
            <div class="leading-tight">
                <span class="block text-sm font-extrabold"><?= e($siteName) ?></span>
                <span class="text-[10px] text-teal-600 dark:text-teal-400">پنل نمایندگی</span>
            </div>
            <button type="button" @click="sidebar = false" class="mr-auto rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800" aria-label="بستن">✕</button>
        </div>

        <div class="mx-3 mt-4 rounded-xl border border-teal-100 bg-white/80 p-3 dark:border-teal-900 dark:bg-slate-900/60">
            <span class="block truncate text-sm font-bold"><?= e($displayName) ?></span>
            <span class="ltr mt-0.5 block text-[10px] text-slate-400">@<?= e($reseller['username'] ?? '') ?></span>
            <span class="mt-2 inline-block rounded-full bg-teal-50 px-2 py-0.5 text-[9px] text-teal-700 dark:bg-teal-950/50 dark:text-teal-300">کمیسیون <?= fa_num((float) ($profile['commission_rate'] ?? 0)) ?>٪</span>
        </div>

        <nav class="px-3 py-4" aria-label="منوی نماینده">
            <ul class="space-y-0.5">
                <?php foreach ($nav as [$href, $label, $icon, $exact]): ?>
                    <?php $active = is_active($href, $exact); ?>
                    <li>
                        <a href="<?= e(url($href)) ?>" class="flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm transition <?= $active ? 'bg-teal-600 font-bold text-white shadow-sm' : 'text-slate-600 hover:bg-white/80 hover:text-teal-700 dark:text-slate-300 dark:hover:bg-slate-800' ?>">
                            <span class="w-5 text-center" aria-hidden="true"><?= $icon ?></span>
                            <span><?= e($label) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="absolute inset-x-0 bottom-0 space-y-1 border-t border-slate-100 p-3 dark:border-slate-800">
            <a href="<?= e(url('/')) ?>" target="_blank" class="flex items-center justify-between rounded-xl bg-white/70 px-3 py-2 text-[11px] text-slate-600 hover:bg-white dark:bg-slate-800/60 dark:text-slate-300">
                <span>مشاهده فروشگاه</span><span aria-hidden="true">↗</span>
            </a>
            <form method="post" action="<?= e(url('/logout')) ?>">
                <?= csrf_field() ?>
                <button class="w-full rounded-xl px-3 py-2 text-right text-[11px] text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30">خروج از حساب</button>
            </form>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur-nav dark:border-slate-800 dark:bg-slate-900/90">
            <button type="button" @click="sidebar = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800" aria-label="منو">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
            <div>
                <h1 class="text-sm font-extrabold sm:text-base"><?= e($title ?? 'پنل نماینده') ?></h1>
                <p class="hidden text-[10px] text-slate-400 sm:block"><?= e($displayName) ?></p>
            </div>
            <div class="mr-auto flex items-center gap-1.5">
                <button type="button" onclick="toggleTheme()" class="rounded-lg p-2 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="تغییر تم"><span data-theme-icon>🌙</span></button>
                <a href="<?= e(url('/reseller/numbers/create')) ?>" class="rounded-xl bg-teal-600 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-teal-700">+ شماره</a>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            <?= \App\Support\View::partial('partials/flash') ?>
            <?= $content ?>
        </main>

        <footer class="border-t border-slate-200 px-6 py-3 text-center text-[10px] text-slate-400 dark:border-slate-800">
            <?= e($siteName) ?> — پنل نمایندگی
        </footer>
    </div>
</div>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
<script defer src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
