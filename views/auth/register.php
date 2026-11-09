<?php
$input = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100 dark:border-slate-700 dark:bg-slate-900 dark:focus:ring-teal-900';
?>
<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7 dark:border-slate-800 dark:bg-slate-900">
    <h1 class="text-xl font-extrabold">ساخت حساب کاربری</h1>
    <p class="mt-1 text-xs leading-6 text-slate-500">ثبت‌نام اختیاری است؛ برای پیگیری آسان‌تر سفارش‌ها و مشاهده سوابق خرید.</p>

    <form method="post" action="<?= e(url('/register')) ?>" class="mt-5 space-y-4">
        <?= csrf_field() ?>
        <div>
            <label for="full_name" class="text-xs font-medium">نام و نام خانوادگی *</label>
            <input id="full_name" name="full_name" required autocomplete="name" value="<?= e(old('full_name')) ?>" class="<?= $input ?>">
            <?php if (error('full_name')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('full_name')) ?></p><?php endif; ?>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="username" class="text-xs font-medium">نام کاربری *</label>
                <input id="username" name="username" required autocomplete="username" value="<?= e(old('username')) ?>" class="<?= $input ?> ltr" placeholder="letters_numbers">
                <?php if (error('username')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('username')) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="phone" class="text-xs font-medium">شماره موبایل *</label>
                <input id="phone" name="phone" required inputmode="tel" autocomplete="tel" value="<?= e(old('phone')) ?>" class="<?= $input ?> ltr" placeholder="09121234567">
                <?php if (error('phone')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('phone')) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="email" class="text-xs font-medium">ایمیل (اختیاری)</label>
                <input id="email" name="email" type="email" autocomplete="email" value="<?= e(old('email')) ?>" class="<?= $input ?> ltr">
                <?php if (error('email')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('email')) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="password" class="text-xs font-medium">رمز عبور *</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="<?= $input ?> ltr">
                <?php if (error('password')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('password')) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="password_confirmation" class="text-xs font-medium">تکرار رمز *</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="<?= $input ?> ltr">
            </div>
        </div>
        <button type="submit" class="w-full rounded-xl bg-teal-600 py-3 text-sm font-bold text-white hover:bg-teal-700">ثبت‌نام</button>
    </form>
    <p class="mt-4 text-center text-xs text-slate-500">حساب دارید؟ <a href="<?= e(url('/login')) ?>" class="font-bold text-teal-700 dark:text-teal-300">وارد شوید</a></p>
</div>
