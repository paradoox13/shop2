<?php
$editing = $number !== null;
$birthValue = '';
if ($editing && !empty($number['birth_date'])) {
    $birthValue = jalali_date((string) $number['birth_date']);
}
$input = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
$label = 'block text-xs font-medium text-slate-600 dark:text-slate-300';
?>
<div class="mb-4 flex items-center gap-2 text-[11px] text-slate-400">
    <a href="<?= e(url('/admin/numbers')) ?>" class="hover:text-teal-600">شماره‌ها</a><span>/</span><span><?= $editing ? 'ویرایش' : 'افزودن' ?></span>
</div>

<div class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
    <h2 class="text-lg font-extrabold"><?= $editing ? 'ویرایش شماره ' . e($number['number']) : 'افزودن شماره جدید' ?></h2>
    <p class="mt-1 text-xs text-slate-500">اپراتور به‌صورت خودکار از پیش‌شماره تشخیص داده می‌شود اما قابل تغییر است.</p>

    <form method="post" action="<?= e($action) ?>" class="mt-5 grid gap-4 sm:grid-cols-2">
        <?= csrf_field() ?>
        <div>
            <label for="number" class="<?= $label ?>">شماره سیم‌کارت *</label>
            <input id="number" name="number" required inputmode="tel" value="<?= e(old('number', $number['number'] ?? '')) ?>" placeholder="09121234567" class="<?= $input ?> ltr">
            <?php if (error('number')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('number')) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="operator_id" class="<?= $label ?>">اپراتور *</label>
            <select id="operator_id" name="operator_id" required class="<?= $input ?>">
                <option value="">انتخاب کنید…</option>
                <?php foreach ($operators as $operator): ?>
                    <option value="<?= (int) $operator['id'] ?>" <?= (string) old('operator_id', $number['operator_id'] ?? '') === (string) $operator['id'] ? 'selected' : '' ?>><?= e($operator['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (error('operator_id')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('operator_id')) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="price" class="<?= $label ?>">قیمت (تومان) *</label>
            <input id="price" name="price" required inputmode="numeric" value="<?= e(old('price', $number['price'] ?? '')) ?>" placeholder="1200000" class="<?= $input ?>">
            <?php if (error('price')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('price')) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="status" class="<?= $label ?>">وضعیت *</label>
            <select id="status" name="status" class="<?= $input ?>">
                <?php foreach (['available' => 'موجود', 'reserved' => 'رزرو شده', 'sold' => 'فروخته‌شده', 'hidden' => 'مخفی'] as $key => $statusLabel): ?>
                    <option value="<?= $key ?>" <?= (string) old('status', $number['status'] ?? 'available') === $key ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="reseller_id" class="<?= $label ?>">نماینده (اختیاری)</label>
            <select id="reseller_id" name="reseller_id" class="<?= $input ?>">
                <option value="">بدون نماینده / فروش مستقیم</option>
                <?php foreach ($resellers as $reseller): ?>
                    <option value="<?= (int) $reseller['id'] ?>" <?= (string) old('reseller_id', $number['reseller_id'] ?? '') === (string) $reseller['id'] ? 'selected' : '' ?>><?= e($reseller['display_name'] ?? $reseller['username']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="birth_date" class="<?= $label ?>">تاریخ تولد (شمسی)</label>
            <input id="birth_date" name="birth_date" value="<?= e(old('birth_date', $birthValue)) ?>" placeholder="1370/05/12" class="<?= $input ?> ltr">
            <p class="mt-1 text-[10px] text-slate-400">مثال: 1370/05/12 — برای فیلتر تاریخ تولد استفاده می‌شود.</p>
            <?php if (error('birth_date')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('birth_date')) ?></p><?php endif; ?>
        </div>
        <div class="sm:col-span-2">
            <label for="description" class="<?= $label ?>">توضیحات</label>
            <textarea id="description" name="description" rows="3" class="<?= $input ?>" placeholder="مثلاً شماره رند، مناسب کسب‌وکار…"><?= e(old('description', $number['description'] ?? '')) ?></textarea>
        </div>
        <div class="flex gap-2 sm:col-span-2">
            <button type="submit" class="rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-teal-700"><?= $editing ? 'ذخیره تغییرات' : 'ثبت شماره' ?></button>
            <a href="<?= e(url('/admin/numbers')) ?>" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm dark:border-slate-700">انصراف</a>
        </div>
    </form>
</div>
