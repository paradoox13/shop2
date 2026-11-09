<?php
$editing = $modem !== null;
$input = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
$label = 'block text-xs font-medium text-slate-600 dark:text-slate-300';
?>
<div class="mb-4 flex items-center gap-2 text-[11px] text-slate-400">
    <a href="<?= e(url('/reseller/modems')) ?>" class="hover:text-teal-600">مودم‌های من</a><span>/</span><span><?= $editing ? 'ویرایش' : 'ثبت جدید' ?></span>
</div>

<div class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
    <h2 class="text-lg font-extrabold"><?= $editing ? 'ویرایش مودم' : 'ثبت مودم جدید' ?></h2>

    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="mt-5 grid gap-4 sm:grid-cols-2">
        <?= csrf_field() ?>
        <div>
            <label for="brand" class="<?= $label ?>">برند *</label>
            <input id="brand" name="brand" required value="<?= e(old('brand', $modem['brand'] ?? '')) ?>" placeholder="TP-Link" class="<?= $input ?>">
            <?php if (error('brand')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('brand')) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="model" class="<?= $label ?>">مدل *</label>
            <input id="model" name="model" required value="<?= e(old('model', $modem['model'] ?? '')) ?>" placeholder="Archer MR600" class="<?= $input ?>">
            <?php if (error('model')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('model')) ?></p><?php endif; ?>
        </div>
        <div>
            <label for="price" class="<?= $label ?>">قیمت (تومان) *</label>
            <input id="price" name="price" required inputmode="numeric" value="<?= e(old('price', $modem['price'] ?? '')) ?>" class="<?= $input ?>">
        </div>
        <div>
            <label for="stock" class="<?= $label ?>">موجودی *</label>
            <input id="stock" name="stock" required inputmode="numeric" value="<?= e(old('stock', $modem['stock'] ?? 0)) ?>" class="<?= $input ?>">
        </div>
        <div>
            <label for="status" class="<?= $label ?>">وضعیت نمایش *</label>
            <select id="status" name="status" class="<?= $input ?>">
                <option value="active" <?= (string) old('status', $modem['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>فعال</option>
                <option value="inactive" <?= (string) old('status', $modem['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
            </select>
        </div>
        <div>
            <label for="image" class="<?= $label ?>">تصویر (اختیاری)</label>
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-2 py-1.5 text-[10px] file:ml-1 file:rounded-lg file:border-0 file:bg-teal-600 file:px-2 file:py-1 file:text-white dark:border-slate-700 dark:bg-slate-800/50">
            <?php if ($editing && !empty($modem['image_path'])): ?>
                <img src="<?= e(upload_url((string) $modem['image_path'])) ?>" alt="" class="mt-2 h-14 w-14 rounded-lg object-cover">
            <?php endif; ?>
        </div>
        <div class="sm:col-span-2">
            <label for="description" class="<?= $label ?>">توضیحات</label>
            <textarea id="description" name="description" rows="4" class="<?= $input ?>"><?= e(old('description', $modem['description'] ?? '')) ?></textarea>
        </div>
        <div class="flex gap-2 sm:col-span-2">
            <button class="rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-teal-700"><?= $editing ? 'ذخیره تغییرات' : 'ثبت مودم' ?></button>
            <a href="<?= e(url('/reseller/modems')) ?>" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm dark:border-slate-700">انصراف</a>
        </div>
    </form>
</div>
