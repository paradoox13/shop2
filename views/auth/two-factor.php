<?php $input = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-3 text-center text-xl tracking-[0.4em] text-slate-900 outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100'; ?>
<div class="rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-50 text-2xl dark:bg-teal-950" aria-hidden="true">🔐</span>
    <h1 class="mt-3 text-lg font-extrabold">تأیید دو مرحله‌ای</h1>
    <p class="mt-1 text-xs leading-6 text-slate-500">کد ۶ رقمی برنامه Authenticator خود را وارد کنید.</p>
    <form method="post" action="<?= e(url('/login/2fa')) ?>" class="mt-5 space-y-3">
        <?= csrf_field() ?>
        <label for="code" class="sr-only">کد تأیید</label>
        <input id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus class="<?= $input ?> ltr" placeholder="••••••">
        <button type="submit" class="w-full rounded-xl bg-teal-600 py-3 text-sm font-bold text-white hover:bg-teal-700">تأیید و ورود</button>
    </form>
    <a href="<?= e(url('/login')) ?>" class="mt-4 inline-block text-xs text-slate-400 hover:text-teal-600">بازگشت به صفحه ورود</a>
</div>
