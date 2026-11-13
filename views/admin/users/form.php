<?php
$editing = $reseller !== null;
$input = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
$label = 'block text-xs font-medium text-slate-600 dark:text-slate-300';
?>
<div class="mb-4 flex items-center gap-2 text-[11px] text-slate-400">
    <a href="<?= e(url('/admin/resellers')) ?>" class="hover:text-teal-600">نمایندگان</a><span>/</span><span><?= $editing ? 'ویرایش' : 'افزودن' ?></span>
</div>

<div class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
    <h2 class="text-lg font-extrabold"><?= $editing ? 'ویرایش نماینده' : 'افزودن نماینده جدید' ?></h2>
    <p class="mt-1 text-xs text-slate-500">نماینده فقط از طریق صفحه ورود نمایندگان می‌تواند وارد پنل شود.</p>

    <form method="post" action="<?= e($action) ?>" class="mt-5 grid gap-4 sm:grid-cols-2">
        <?= csrf_field() ?>
        <?php if ($editing): ?>
            <div class="sm:col-span-2 rounded-xl bg-slate-50 p-3 text-xs dark:bg-slate-800/50">
                نام کاربری: <span class="ltr font-bold"><?= e($reseller['username']) ?></span>
                <span class="mx-2 text-slate-300">|</span>
                شناسه: #<?= fa_num($reseller['id']) ?>
                <span class="mx-2 text-slate-300">|</span>
                عضویت: <?= fa_num(jalali_date((string) $reseller['created_at'])) ?>
            </div>
        <?php else: ?>
            <div>
                <label for="username" class="<?= $label ?>">نام کاربری *</label>
                <input id="username" name="username" required value="<?= e(old('username')) ?>" class="<?= $input ?> ltr" placeholder="reseller_name">
                <?php if (error('username')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('username')) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="password" class="<?= $label ?>">رمز عبور *</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="<?= $input ?> ltr">
                <?php if (error('password')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('password')) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="password_confirmation" class="<?= $label ?>">تکرار رمز عبور *</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="<?= $input ?> ltr">
            </div>
        <?php endif; ?>

        <div>
            <label for="full_name" class="<?= $label ?>">نام و نام خانوادگی *</label>
            <input id="full_name" name="full_name" required value="<?= e(old('full_name', $reseller['full_name'] ?? '')) ?>" class="<?= $input ?>">
            <?php if (error('full_name')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('full_name')) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="phone" class="<?= $label ?>">شماره موبایل *</label>
            <input id="phone" name="phone" required inputmode="tel" value="<?= e(old('phone', $reseller['phone'] ?? '')) ?>" class="<?= $input ?> ltr">
            <?php if (error('phone')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('phone')) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="email" class="<?= $label ?>">ایمیل</label>
            <input id="email" name="email" type="email" value="<?= e(old('email', $reseller['email'] ?? '')) ?>" class="<?= $input ?> ltr">
        </div>
        <div>
            <label for="display_name" class="<?= $label ?>">نام نمایشی فروشگاه *</label>
            <input id="display_name" name="display_name" required value="<?= e(old('display_name', $profile['display_name'] ?? '')) ?>" class="<?= $input ?>">
            <?php if (error('display_name')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('display_name')) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="contact" class="<?= $label ?>">راه ارتباطی / آدرس</label>
            <input id="contact" name="contact" value="<?= e(old('contact', $profile['contact'] ?? '')) ?>" class="<?= $input ?>" placeholder="تلگرام، واتساپ یا آدرس">
        </div>
        <div>
            <label for="commission_rate" class="<?= $label ?>">درصد کمیسیون *</label>
            <input id="commission_rate" name="commission_rate" required inputmode="decimal" value="<?= e(old('commission_rate', $profile['commission_rate'] ?? 0)) ?>" class="<?= $input ?>">
            <p class="mt-1 text-[10px] text-slate-400">در گزارش تسویه نماینده استفاده می‌شود.</p>
        </div>
        <?php if ($editing): ?>
            <div>
                <label for="status" class="<?= $label ?>">وضعیت حساب *</label>
                <select id="status" name="status" class="<?= $input ?>">
                    <option value="active" <?= (string) old('status', $reseller['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>فعال</option>
                    <option value="suspended" <?= (string) old('status', $reseller['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>غیرفعال</option>
                </select>
            </div>
            <div>
                <label for="password" class="<?= $label ?>">رمز عبور جدید (اختیاری)</label>
                <input id="password" name="password" type="password" autocomplete="new-password" class="<?= $input ?> ltr" placeholder="برای عدم تغییر خالی بگذارید">
                <?php if (error('password')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('password')) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="password_confirmation" class="<?= $label ?>">تکرار رمز جدید</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="<?= $input ?> ltr">
            </div>
        <?php endif; ?>

        <div class="flex gap-2 sm:col-span-2">
            <button class="rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-teal-700"><?= $editing ? 'ذخیره تغییرات' : 'ساخت نماینده' ?></button>
            <a href="<?= e(url('/admin/resellers')) ?>" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm dark:border-slate-700">انصراف</a>
        </div>
    </form>
</div>
