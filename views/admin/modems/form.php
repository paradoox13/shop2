<?php
$editing = $modem !== null;
$input = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none focus:border-teal-500 dark:border-slate-700 dark:bg-slate-900';
$label = 'block text-xs font-medium text-slate-600 dark:text-slate-300';
?>
<div class="mb-4 flex items-center gap-2 text-[11px] text-slate-400">
    <a href="<?= e(url('/admin/modems')) ?>" class="hover:text-teal-600">مودم‌ها</a><span>/</span><span><?= $editing ? 'ویرایش' : 'افزودن' ?></span>
</div>

<div class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
    <h2 class="text-lg font-extrabold"><?= $editing ? 'ویرایش مودم' : 'افزودن مودم جدید' ?></h2>

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
            <label for="status" class="<?= $label ?>">وضعیت *</label>
            <select id="status" name="status" class="<?= $input ?>">
                <option value="active" <?= (string) old('status', $modem['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>فعال / قابل نمایش</option>
                <option value="inactive" <?= (string) old('status', $modem['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>غیرفعال / مخفی</option>
            </select>
        </div>
        <div>
            <label for="reseller_id" class="<?= $label ?>">نماینده (اختیاری)</label>
            <select id="reseller_id" name="reseller_id" class="<?= $input ?>">
                <option value="">بدون نماینده</option>
                <?php foreach ($resellers as $reseller): ?>
                    <option value="<?= (int) $reseller['id'] ?>" <?= (string) old('reseller_id', $modem['reseller_id'] ?? '') === (string) $reseller['id'] ? 'selected' : '' ?>><?= e($reseller['display_name'] ?? $reseller['username']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label for="image" class="<?= $label ?>">تصویر محصول (JPG/PNG/WebP، حداکثر ۲ مگابایت)</label>
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-2 text-xs file:ml-2 file:rounded-lg file:border-0 file:bg-teal-600 file:px-3 file:py-1.5 file:text-white dark:border-slate-700 dark:bg-slate-800/50">
            <?php if ($editing && !empty($modem['image_path'])): ?>
                <div class="mt-2 flex items-center gap-2 rounded-xl bg-slate-50 p-2 dark:bg-slate-800/50">
                    <img src="<?= e(upload_url((string) $modem['image_path'])) ?>" alt="" class="h-14 w-14 rounded-lg object-cover">
                    <span class="text-[10px] text-slate-400">تصویر فعلی — آپلود تصویر جدید جایگزین می‌شود.</span>
                </div>
            <?php endif; ?>
            <?php if (error('image')): ?><p class="mt-1 text-[11px] text-rose-600"><?= e(error('image')) ?></p><?php endif; ?>
        </div>
        <div class="sm:col-span-2">
            <label for="description" class="<?= $label ?>">توضیحات</label>
            <textarea id="description" name="description" rows="4" class="<?= $input ?>"><?= e(old('description', $modem['description'] ?? '')) ?></textarea>
        </div>
        <div class="flex gap-2 sm:col-span-2">
            <button class="rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-teal-700"><?= $editing ? 'ذخیره تغییرات' : 'ثبت مودم' ?></button>
            <a href="<?= e(url('/admin/modems')) ?>" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm dark:border-slate-700">انصراف</a>
        </div>
    </form>
</div>
