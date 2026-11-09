<?php $input = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900'; ?>
<div class="mb-4 flex items-center gap-2 text-[11px] text-slate-400">
    <a href="<?= e(url('/admin/settings')) ?>" class="hover:text-teal-600">تنظیمات</a><span>/</span><span>تأیید دو مرحله‌ای</span>
</div>

<div class="mx-auto max-w-2xl">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold">تأیید دو مرحله‌ای (TOTP)</h2>
                <p class="mt-1 text-xs leading-6 text-slate-500">ورود ادمین با کد ۶ رقمی برنامه Authenticator امن‌تر می‌شود.</p>
            </div>
            <span class="rounded-full px-3 py-1 text-[10px] font-bold <?= $enabled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' ?>"><?= $enabled ? 'فعال' : 'غیرفعال' ?></span>
        </div>

        <?php if ($enabled): ?>
            <div class="mt-4 rounded-xl bg-emerald-50 p-3 text-[11px] leading-6 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                تأیید دو مرحله‌ای برای حساب شما فعال است. برای ورود، پس از رمز عبور کد ۶ رقمی برنامه Authenticator را وارد می‌کنید.
            </div>
            <form method="post" action="<?= e(url('/admin/settings/2fa')) ?>" class="mt-5 space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="disable">
                <div>
                    <label for="code-disable" class="mb-1 block text-xs text-slate-500">برای غیرفعال‌سازی، کد فعلی را وارد کنید</label>
                    <input id="code-disable" name="code" inputmode="numeric" maxlength="6" required class="<?= $input ?> ltr text-center tracking-[0.3em]">
                </div>
                <button class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-rose-700">غیرفعال کردن 2FA</button>
            </form>
        <?php else: ?>
            <ol class="mt-4 list-decimal space-y-2 pr-5 text-xs leading-7 text-slate-600 dark:text-slate-300">
                <li>برنامه Google Authenticator یا Microsoft Authenticator را نصب کنید.</li>
                <li>کلید زیر را به‌صورت دستی در برنامه وارد کنید (یا URI را با پشتیبانی برنامه اسکن کنید).</li>
                <li>کد ۶ رقمی تولیدشده را برای فعال‌سازی وارد کنید.</li>
            </ol>

            <div class="mt-4 rounded-xl bg-slate-50 p-3 dark:bg-slate-800/50">
                <span class="block text-[10px] text-slate-400">کلید مخفی (Base32)</span>
                <div class="mt-1 flex items-center gap-2">
                    <code class="ltr min-w-0 flex-1 break-all rounded-lg bg-white px-2 py-1.5 text-xs tracking-wider dark:bg-slate-900"><?= e($secret) ?></code>
                    <button type="button" onclick="copyText('<?= e($secret) ?>', this)" class="shrink-0 rounded-lg bg-teal-600 px-2.5 py-1.5 text-[10px] font-bold text-white">کپی</button>
                </div>
                <span class="mt-3 block text-[10px] text-slate-400">URI استاندارد (otpauth)</span>
                <div class="mt-1 flex items-center gap-2">
                    <code class="ltr min-w-0 flex-1 truncate rounded-lg bg-white px-2 py-1.5 text-[9px] dark:bg-slate-900" title="<?= e($uri) ?>"><?= e($uri) ?></code>
                    <button type="button" onclick="copyText('<?= e($uri) ?>', this)" class="shrink-0 rounded-lg bg-slate-700 px-2.5 py-1.5 text-[10px] font-bold text-white">کپی</button>
                </div>
            </div>

            <form method="post" action="<?= e(url('/admin/settings/2fa')) ?>" class="mt-5 space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="enable">
                <div>
                    <label for="code-enable" class="mb-1 block text-xs text-slate-500">کد ۶ رقمی برنامه Authenticator</label>
                    <input id="code-enable" name="code" inputmode="numeric" maxlength="6" required autofocus class="<?= $input ?> ltr text-center text-xl tracking-[0.4em]">
                </div>
                <button class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-teal-700">فعال‌سازی 2FA</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-[11px] leading-6 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
        هشدار: کلید مخفی را در جای امن نگه دارید. اگر دسترسی به برنامه Authenticator را از دست بدهید، برای ورود باید از طریق دیتابیس (ستون totp_secret) توسط مدیر سرور اقدام کنید. ساعت سرور و گوشی باید همگام باشد.
    </div>
</div>
