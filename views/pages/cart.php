<?php
$hasItems = $cart['count'] > 0;
?>
<h1 class="mb-5 text-xl font-extrabold">سبد خرید</h1>

<?php if (!$hasItems): ?>
    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center dark:border-slate-700 dark:bg-slate-900">
        <span class="text-4xl" aria-hidden="true">🛒</span>
        <p class="mt-3 text-lg font-bold">سبد خرید شما خالی است</p>
        <p class="mt-1 text-sm text-slate-500">از صفحه اصلی شماره، مودم یا کد فعال‌سازی انتخاب کنید.</p>
        <a href="<?= e(url('/')) ?>" class="mt-5 inline-block rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white">مشاهده محصولات</a>
    </div>
<?php else: ?>
    <div class="grid gap-5 lg:grid-cols-[1.4fr_0.6fr]">
        <div class="space-y-3">
            <?php foreach ($cart['numbers'] as $item): ?>
                <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-teal-50 text-lg dark:bg-teal-950" aria-hidden="true">📱</div>
                    <div class="min-w-0 flex-1">
                        <p class="ltr digits font-extrabold"><?= e($item['number']) ?></p>
                        <p class="mt-1 text-[11px] text-slate-500"><?= e($item['operator_name']) ?> — شماره سیم‌کارت</p>
                        <?php if (!$item['available']): ?>
                            <p class="mt-1 text-[11px] font-bold text-rose-600">این شماره دیگر در دسترس نیست.</p>
                        <?php endif; ?>
                    </div>
                    <div class="text-sm font-bold text-teal-700 dark:text-teal-300"><?= price($item['price']) ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></div>
                    <form method="post" action="<?= e(url('/cart/remove')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="number">
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <button class="rounded-lg px-2 py-1 text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30" aria-label="حذف">حذف</button>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php foreach ($cart['modems'] as $item): ?>
                <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <img src="<?= e(upload_url($item['image_path'])) ?>" alt="" class="h-12 w-12 rounded-xl object-cover">
                    <div class="min-w-0 flex-1">
                        <p class="font-bold"><?= e($item['brand'] . ' ' . $item['model']) ?></p>
                        <p class="mt-1 text-[11px] text-slate-500">مودم — موجودی <?= fa_num($item['stock']) ?></p>
                        <?php if (!$item['available']): ?><p class="mt-1 text-[11px] font-bold text-rose-600">موجودی کافی نیست.</p><?php endif; ?>
                    </div>
                    <form method="post" action="<?= e(url('/cart/update')) ?>" class="flex items-center gap-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="modem">
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <label class="sr-only" for="mq-<?= (int) $item['id'] ?>">تعداد</label>
                        <input id="mq-<?= (int) $item['id'] ?>" type="number" name="qty" min="1" max="<?= max(1, (int) $item['stock']) ?>" value="<?= (int) $item['qty'] ?>" class="w-14 rounded-lg border border-slate-200 px-2 py-1 text-center text-xs dark:border-slate-700 dark:bg-slate-800">
                        <button class="rounded-lg border border-slate-200 px-2 py-1 text-[11px] hover:border-teal-400 dark:border-slate-700">به‌روزرسانی</button>
                    </form>
                    <div class="text-sm font-bold text-teal-700 dark:text-teal-300"><?= price($item['line_total']) ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></div>
                    <form method="post" action="<?= e(url('/cart/remove')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="modem">
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <button class="rounded-lg px-2 py-1 text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30">حذف</button>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php foreach ($cart['activation'] as $item): ?>
                <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-lg dark:bg-amber-950" aria-hidden="true">🔑</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold">کد فعال‌سازی <?= e($item['operator_name']) ?></p>
                        <p class="mt-1 text-[11px] text-slate-500">ارسال فیزیکی پس از پرداخت</p>
                    </div>
                    <form method="post" action="<?= e(url('/cart/update')) ?>" class="flex items-center gap-1">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="activation">
                        <input type="hidden" name="id" value="<?= (int) $item['operator_id'] ?>">
                        <label class="sr-only" for="aq-<?= (int) $item['operator_id'] ?>">تعداد</label>
                        <input id="aq-<?= (int) $item['operator_id'] ?>" type="number" name="qty" min="1" max="10" value="<?= (int) $item['qty'] ?>" class="w-14 rounded-lg border border-slate-200 px-2 py-1 text-center text-xs dark:border-slate-700 dark:bg-slate-800">
                        <button class="rounded-lg border border-slate-200 px-2 py-1 text-[11px] hover:border-teal-400 dark:border-slate-700">به‌روزرسانی</button>
                    </form>
                    <div class="text-sm font-bold text-teal-700 dark:text-teal-300"><?= price($item['line_total']) ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></div>
                    <form method="post" action="<?= e(url('/cart/remove')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="activation">
                        <input type="hidden" name="id" value="<?= (int) $item['operator_id'] ?>">
                        <button class="rounded-lg px-2 py-1 text-xs text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30">حذف</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 lg:sticky lg:top-24 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-bold">خلاصه سفارش</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">تعداد اقلام</dt><dd><?= fa_num($cart['count']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">جمع</dt><dd class="font-bold"><?= price($cart['total']) ?> تومان</dd></div>
                <div class="flex justify-between border-t border-dashed border-slate-200 pt-3 dark:border-slate-700"><dt class="font-bold">قابل پرداخت</dt><dd class="text-lg font-extrabold text-teal-700 dark:text-teal-300"><?= price($cart['total']) ?> تومان</dd></div>
            </dl>
            <a href="<?= e(url('/checkout')) ?>" class="mt-5 block w-full rounded-xl bg-teal-600 py-3 text-center text-sm font-bold text-white hover:bg-teal-700">ادامه و پرداخت</a>
            <a href="<?= e(url('/')) ?>" class="mt-2 block w-full rounded-xl border border-slate-200 py-2.5 text-center text-sm hover:border-teal-400 dark:border-slate-700">ادامه خرید</a>
            <p class="mt-4 text-[11px] leading-6 text-slate-400">شماره‌ها هنگام شروع پرداخت ۱۵ دقیقه رزرو می‌شوند. پس از پرداخت موفق، شماره برای همیشه از فروش حذف می‌شود.</p>
        </aside>
    </div>
<?php endif; ?>
