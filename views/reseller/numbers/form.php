<?php
$editing = $number !== null;
$birthValue = $editing && !empty($number['birth_date']) ? jalali_date((string) $number['birth_date']) : '';
$input = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
$label = 'block text-xs font-medium text-slate-600 dark:text-slate-300';
$typeValue = (string) old('number_type', $number['number_type'] ?? 'round');
$lineValue = (string) old('line_type', $number['line_type'] ?? 'prepaid');
?>
<div class="mb-4 flex items-center gap-2 text-[11px] text-slate-400">
    <a href="<?= e(url('/reseller/numbers')) ?>" class="hover:text-teal-600">شماره‌های من</a><span>/</span><span><?= $editing ? 'ویرایش' : 'ثبت جدید' ?></span>
</div>

<div class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
    <h2 class="text-lg font-extrabold"><?= $editing ? 'ویرایش شماره' : 'ثبت شماره جدید' ?></h2>
    <p class="mt-1 text-xs text-slate-500">شماره باید ۱۱ رقم و با 09 شروع شود و قبلاً در سامانه ثبت نشده باشد.</p>

    <form method="post" action="<?= e($action) ?>" class="mt-5 grid gap-4 sm:grid-cols-2">
        <?= csrf_field() ?>
        <div class="sm:col-span-2">
            <label for="number" class="<?= $label ?>">شماره سیم‌کارت *</label>
            <input id="number" name="number" required inputmode="tel" value="<?= e(old('number', $number['number'] ?? '')) ?>" placeholder="09121234567" class="<?= $input ?> ltr text-lg tracking-wider">
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
            <label for="price" class="<?= $label ?>">قیمت فروش (تومان) *</label>
            <input id="price" name="price" required inputmode="numeric" value="<?= e(old('price', $number['price'] ?? '')) ?>" placeholder="1500000" class="<?= $input ?>">
            <?php if (error('price')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('price')) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="number_type" class="<?= $label ?>">دسته‌بندی شماره *</label>
            <select id="number_type" name="number_type" required class="<?= $input ?>">
                <?php foreach (\App\Models\PhoneNumber::TYPES as $typeKey => $typeLabel): ?>
                    <option value="<?= $typeKey ?>" <?= $typeValue === $typeKey ? 'selected' : '' ?>><?= e($typeLabel) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (error('number_type')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('number_type')) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="line_type" class="<?= $label ?>">نوع خط *</label>
            <select id="line_type" name="line_type" required class="<?= $input ?>">
                <?php foreach (\App\Models\PhoneNumber::LINE_TYPES as $lineKey => $lineLabel): ?>
                    <option value="<?= $lineKey ?>" <?= $lineValue === $lineKey ? 'selected' : '' ?>><?= e($lineLabel) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (error('line_type')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('line_type')) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="birth_date" class="<?= $label ?>">تاریخ تولد (شمسی، اختیاری)</label>
            <input id="birth_date" name="birth_date" value="<?= e(old('birth_date', $birthValue)) ?>" placeholder="1370/05/12" class="<?= $input ?> ltr">
            <p class="mt-1 text-[10px] text-slate-400">برای دسته «تاریخ تولد» الزامی است.</p>
            <?php if (error('birth_date')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('birth_date')) ?></p><?php endif; ?>
        </div>
        <div class="sm:col-span-2">
            <label for="description" class="<?= $label ?>">توضیحات</label>
            <textarea id="description" name="description" rows="3" class="<?= $input ?>" placeholder="مثلاً شماره رند، مناسب کسب‌وکار…"><?= e(old('description', $number['description'] ?? '')) ?></textarea>
        </div>
        <div class="flex gap-2 sm:col-span-2">
            <button class="rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-teal-700"><?= $editing ? 'ذخیره تغییرات' : 'ثبت شماره' ?></button>
            <a href="<?= e(url('/reseller/numbers')) ?>" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm dark:border-slate-700">انصراف</a>
        </div>
    </form>
</div>
