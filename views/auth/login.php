<?php
$input = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100 dark:border-slate-700 dark:bg-slate-900 dark:focus:ring-teal-900';
?>
<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7 dark:border-slate-800 dark:bg-slate-900">
    <h1 class="text-xl font-extrabold"><?= e($heading) ?></h1>
    <p class="mt-1 text-xs leading-6 text-slate-500">نام کاربری و رمز عبور خود را وارد کنید.</p>

    <form method="post" action="<?= e(url($action)) ?>" class="mt-5 space-y-4">
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next ?? '') ?>">
        <div>
            <label for="username" class="text-xs font-medium">نام کاربری</label>
            <input id="username" name="username" required autocomplete="username" value="<?= e(old('username')) ?>" class="<?= $input ?> ltr" autofocus>
        </div>
        <div>
            <label for="password" class="text-xs font-medium">رمز عبور</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="<?= $input ?> ltr">
        </div>
        <button type="submit" class="w-full rounded-xl bg-teal-600 py-3 text-sm font-bold text-white hover:bg-teal-700">ورود</button>
    </form>

    <div class="mt-5 space-y-2 border-t border-dashed border-slate-200 pt-4 text-center text-xs dark:border-slate-700">
        <?php if (!empty($registerLink)): ?>
            <p class="text-slate-500">حساب ندارید؟ <a href="<?= e(url('/register')) ?>" class="font-bold text-teal-700 dark:text-teal-300">ثبت‌نام کنید</a></p>
            <p><a href="<?= e(url('/reseller/login')) ?>" class="text-slate-400 hover:text-teal-600">ورود نمایندگان</a> <span class="mx-1 text-slate-300">|</span> <a href="<?= e(url('/admin/login')) ?>" class="text-slate-400 hover:text-teal-600">ورود مدیران</a></p>
        <?php else: ?>
            <p><a href="<?= e(url('/login')) ?>" class="text-slate-500 hover:text-teal-600">ورود مشتریان</a></p>
        <?php endif; ?>
    </div>
</div>
