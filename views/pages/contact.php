<?php
$input = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100 dark:border-slate-700 dark:bg-slate-900 dark:focus:ring-teal-900';
?>
<h1 class="mb-1 text-xl font-extrabold">تماس با ما</h1>
<p class="mb-5 text-sm text-slate-500">برای استعلام شماره، خرید عمده یا همکاری به عنوان نماینده با ما در ارتباط باشید.</p>

<div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
    <form method="post" action="<?= e(url('/contact')) ?>" class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <?= csrf_field() ?>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="text-xs font-medium">نام و نام خانوادگی *</label>
                <input id="name" name="name" required value="<?= e(old('name')) ?>" class="<?= $input ?>">
                <?php if (error('name')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('name')) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="phone" class="text-xs font-medium">شماره موبایل</label>
                <input id="phone" name="phone" inputmode="tel" value="<?= e(old('phone')) ?>" placeholder="09121234567" class="<?= $input ?> ltr">
                <?php if (error('phone')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('phone')) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="email" class="text-xs font-medium">ایمیل</label>
                <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" class="<?= $input ?> ltr">
                <?php if (error('email')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('email')) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="subject" class="text-xs font-medium">موضوع *</label>
                <input id="subject" name="subject" required value="<?= e(old('subject')) ?>" class="<?= $input ?>">
                <?php if (error('subject')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('subject')) ?></p><?php endif; ?>
            </div>
            <div class="sm:col-span-2">
                <label for="message" class="text-xs font-medium">پیام *</label>
                <textarea id="message" name="message" required rows="6" class="<?= $input ?>"><?= e(old('message')) ?></textarea>
                <?php if (error('message')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('message')) ?></p><?php endif; ?>
            </div>
        </div>
        <button type="submit" class="mt-5 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-teal-700">ارسال پیام</button>
        <p class="mt-3 text-[11px] text-slate-400">پاسخ‌گویی در ساعات کاری؛ برای موارد فوری از تلفن استفاده کنید.</p>
    </form>

    <aside class="space-y-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-bold">اطلاعات تماس</h2>
            <ul class="mt-3 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                <?php if (setting('support_phone') !== ''): ?>
                    <li><span class="block text-[11px] text-slate-400">تلفن</span><a class="ltr font-bold text-teal-700 dark:text-teal-300" href="tel:<?= e(preg_replace('/\D+/', '', setting('support_phone'))) ?>"><?= fa_num(setting('support_phone')) ?></a></li>
                <?php endif; ?>
                <?php if (setting('support_email') !== ''): ?>
                    <li><span class="block text-[11px] text-slate-400">ایمیل</span><a class="font-bold text-teal-700 dark:text-teal-300" href="mailto:<?= e(setting('support_email')) ?>"><?= e(setting('support_email')) ?></a></li>
                <?php endif; ?>
                <?php if (setting('address') !== ''): ?>
                    <li><span class="block text-[11px] text-slate-400">آدرس</span><?= e(setting('address')) ?></li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="rounded-2xl bg-gradient-to-bl from-teal-700 to-emerald-800 p-5 text-white">
            <h2 class="text-sm font-bold">خرید عمده یا نمایندگی</h2>
            <p class="mt-2 text-xs leading-6 text-teal-50">اگر فروشگاه یا کسب‌وکار دارید، با ما تماس بگیرید تا شرایط همکاری و پنل نمایندگی را برایتان فعال کنیم.</p>
            <a href="<?= e(url('/reseller/login')) ?>" class="mt-3 inline-block rounded-lg bg-white/15 px-3 py-1.5 text-xs font-bold">ورود نمایندگان</a>
        </div>
    </aside>
</div>
