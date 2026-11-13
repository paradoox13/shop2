<?php
$f = $filters ?? [];
$field = 'mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100 dark:border-slate-700 dark:bg-slate-900 dark:focus:ring-teal-900';
$label = 'mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400';
$categories = \App\Models\PhoneNumber::TYPES;
unset($categories['other']);
$currentType = (string) ($f['number_type'] ?? '');
$currentLine = (string) ($f['line_type'] ?? '');
?>

<section class="relative overflow-hidden rounded-3xl bg-gradient-to-bl from-brand-900 via-teal-800 to-emerald-700 px-6 py-10 text-white shadow-xl shadow-teal-900/20 sm:px-10">
    <div class="absolute -left-16 -top-16 h-56 w-56 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>
    <div class="absolute -bottom-20 right-10 h-64 w-64 rounded-full bg-emerald-400/20 blur-3xl" aria-hidden="true"></div>
    <div class="relative grid items-center gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs backdrop-blur">✔ ضمانت بازگشت وجه در صورت خطای درگاه</span>
            <h1 class="mt-4 text-2xl font-extrabold leading-tight sm:text-3xl lg:text-4xl"><?= e(setting('site_tagline', 'سیم‌کارت، مودم و کد فعال‌سازی؛ مطمئن و سریع')) ?></h1>
            <p class="mt-3 max-w-xl text-sm leading-7 text-teal-50/90">شماره‌های همراه اول، ایرانسل و رایتل در دسته‌های رند، خانوادگی، فوق رند، سریالی و تاریخ تولد؛ تحویل فوری پس از پرداخت و پشتیبانی واقعی.</p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="#numbers" class="rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-teal-900 shadow-sm transition hover:bg-teal-50">مشاهده شماره‌ها</a>
                <a href="#activation" class="rounded-xl border border-white/40 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-white/10">خرید کد فعال‌سازی</a>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2 text-center">
            <div class="rounded-2xl bg-white/10 p-3 backdrop-blur">
                <span class="block text-2xl font-extrabold"><?= fa_num($stats['available'] ?? 0) ?></span>
                <span class="text-[11px] text-teal-100">شماره موجود</span>
            </div>
            <div class="rounded-2xl bg-white/10 p-3 backdrop-blur">
                <span class="block text-2xl font-extrabold"><?= fa_num($stats['orders'] ?? 0) ?></span>
                <span class="text-[11px] text-teal-100">سفارش موفق</span>
            </div>
            <div class="rounded-2xl bg-white/10 p-3 backdrop-blur">
                <span class="block text-2xl font-extrabold"><?= fa_num($stats['operators'] ?? 0) ?></span>
                <span class="text-[11px] text-teal-100">اپراتور</span>
            </div>
        </div>
    </div>
</section>

<section id="numbers" class="mt-8 scroll-mt-24">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-extrabold">خرید شماره سیم‌کارت</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400"><?= fa_num($numbers['total'] ?? 0) ?> شماره موجود — فیلترها با آدرس صفحه قابل اشتراک‌گذاری هستند.</p>
        </div>
        <a href="<?= e(url('/')) ?>" class="text-xs font-medium text-teal-700 hover:underline dark:text-teal-300">پاک کردن فیلترها</a>
    </div>

    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-6">
        <a href="<?= e(query_url('/', ['number_type' => null, 'page' => null])) ?>" class="rounded-2xl border p-3 text-center transition <?= $currentType === '' ? 'border-teal-500 bg-teal-50 shadow-sm dark:border-teal-500 dark:bg-teal-950/30' : 'border-slate-200 bg-white hover:border-teal-300 dark:border-slate-800 dark:bg-slate-900' ?>">
            <span class="block text-lg" aria-hidden="true">▦</span><span class="mt-1 block text-xs font-bold">همه شماره‌ها</span>
        </a>
        <?php foreach ($categories as $type => $typeLabel): ?>
            <a href="<?= e(query_url('/', ['number_type' => $type, 'page' => null])) ?>" class="rounded-2xl border p-3 text-center transition <?= $currentType === $type ? 'border-teal-500 bg-teal-50 shadow-sm dark:border-teal-500 dark:bg-teal-950/30' : 'border-slate-200 bg-white hover:border-teal-300 dark:border-slate-800 dark:bg-slate-900' ?>">
                <span class="block text-lg" aria-hidden="true"><?= match ($type) { 'round' => '◉', 'family' => '👨‍👩‍👧', 'super_round' => '✦', 'serial' => '↗', 'birthday' => '🎂', default => '#' } ?></span>
                <span class="mt-1 block text-xs font-bold"><?= e($typeLabel) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-3 text-xs dark:border-slate-800 dark:bg-slate-900">
        <span class="text-slate-400">نوع خط:</span>
        <a href="<?= e(query_url('/', ['line_type' => null, 'page' => null])) ?>" class="rounded-full px-3 py-1.5 font-bold <?= $currentLine === '' ? 'bg-slate-800 text-white dark:bg-slate-700' : 'bg-slate-100 text-slate-600 hover:bg-teal-50 dark:bg-slate-800 dark:text-slate-300' ?>">همه</a>
        <?php foreach (\App\Models\PhoneNumber::LINE_TYPES as $lineKey => $lineLabel): ?>
            <a href="<?= e(query_url('/', ['line_type' => $lineKey, 'page' => null])) ?>" class="rounded-full px-3 py-1.5 font-bold <?= $currentLine === $lineKey ? 'bg-slate-800 text-white dark:bg-slate-700' : 'bg-slate-100 text-slate-600 hover:bg-teal-50 dark:bg-slate-800 dark:text-slate-300' ?>"><?= e($lineLabel) ?></a>
        <?php endforeach; ?>
        <?php if ($currentType !== ''): ?><span class="mr-auto text-[11px] text-teal-700 dark:text-teal-300">دسته: <?= e($categories[$currentType] ?? number_type_label($currentType)) ?></span><?php endif; ?>
    </div>

    <form method="get" action="<?= e(url('/')) ?>" class="mt-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <input type="hidden" name="number_type" value="<?= e($currentType) ?>">
        <input type="hidden" name="line_type" value="<?= e($currentLine) ?>">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="q" class="<?= $label ?>">جستجوی سریع</label>
                <input id="q" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="مثلاً 0912 یا 1234" class="<?= $field ?>">
            </div>
            <div>
                <label for="operator" class="<?= $label ?>">اپراتور</label>
                <select id="operator" name="operator" class="<?= $field ?>">
                    <option value="">همه اپراتورها</option>
                    <?php foreach ($operators as $operator): ?>
                        <option value="<?= (int) $operator['id'] ?>" <?= (string) ($f['operator'] ?? '') === (string) $operator['id'] ? 'selected' : '' ?>><?= e($operator['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="min_price" class="<?= $label ?>">حداقل قیمت (تومان)</label>
                <input id="min_price" name="min_price" inputmode="numeric" value="<?= e($f['min_price'] ?? '') ?>" placeholder="۵۰۰۰۰۰" class="<?= $field ?>">
            </div>
            <div>
                <label for="max_price" class="<?= $label ?>">حداکثر قیمت (تومان)</label>
                <input id="max_price" name="max_price" inputmode="numeric" value="<?= e($f['max_price'] ?? '') ?>" placeholder="۵۰۰۰۰۰۰" class="<?= $field ?>">
            </div>
            <div>
                <label for="prefix" class="<?= $label ?>">پیش‌شماره</label>
                <input id="prefix" name="prefix" value="<?= e($f['prefix'] ?? '') ?>" placeholder="0912" class="<?= $field ?>">
            </div>
            <div>
                <label for="suffix" class="<?= $label ?>">پسوند</label>
                <input id="suffix" name="suffix" value="<?= e($f['suffix'] ?? '') ?>" placeholder="1234" class="<?= $field ?>">
            </div>
            <div>
                <label for="sort" class="<?= $label ?>">مرتب‌سازی</label>
                <select id="sort" name="sort" class="<?= $field ?>">
                    <option value="newest" <?= ($f['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>جدیدترین</option>
                    <option value="price_asc" <?= ($f['sort'] ?? '') === 'price_asc' ? 'selected' : '' ?>>ارزان‌ترین</option>
                    <option value="price_desc" <?= ($f['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>گران‌ترین</option>
                    <option value="popular" <?= ($f['sort'] ?? '') === 'popular' ? 'selected' : '' ?>>محبوب‌ترین</option>
                </select>
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <?php if (!empty($popularPrefixes)): ?>
                <span class="text-[11px] text-slate-400">پیش‌شماره‌های پرطرفدار:</span>
                <?php foreach ($popularPrefixes as $prefix): ?>
                    <a href="<?= e(query_url('/', ['prefix' => $prefix, 'page' => null])) ?>" class="ltr rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-600 hover:bg-teal-100 hover:text-teal-800 dark:bg-slate-800 dark:text-slate-300"><?= fa_num($prefix) ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <button type="submit" class="rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700">اعمال فیلتر</button>
            <a href="<?= e(url('/')) ?>" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-medium hover:border-slate-300 dark:border-slate-700">حذف فیلترها</a>
        </div>
    </form>

    <?php if (($numbers['items'] ?? []) === []): ?>
        <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
            <p class="text-lg font-bold">شماره‌ای با این فیلترها پیدا نشد</p>
            <p class="mt-2 text-sm text-slate-500">فیلترها را ساده‌تر کنید یا با پشتیبانی تماس بگیرید تا شماره مورد نظر شما را پیدا کنیم.</p>
            <a href="<?= e(url('/contact')) ?>" class="mt-4 inline-block rounded-xl bg-teal-600 px-5 py-2 text-sm font-bold text-white">درخواست شماره خاص</a>
        </div>
    <?php else: ?>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php foreach ($numbers['items'] as $item): ?>
                <?= \App\Support\View::partial('partials/number-card', ['item' => $item]) ?>
            <?php endforeach; ?>
        </div>
        <?= \App\Support\View::partial('partials/pagination', ['paginator' => $numbers, 'path' => '/']) ?>
    <?php endif; ?>
</section>

<section id="activation" class="mt-12 scroll-mt-24">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
        <div>
            <h2 class="text-xl font-extrabold">کد فعال‌سازی سیم‌کارت</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">انتخاب اپراتور و تعداد؛ پس از پرداخت، کد فعال‌سازی به‌صورت فیزیکی ارسال می‌شود.</p>
        </div>
        <span class="rounded-full bg-amber-50 px-3 py-1 text-[11px] font-medium text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">ارسال فیزیکی پس از تأیید</span>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach ($activationPrices as $activation): ?>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900" style="border-top: 4px solid <?= e(operator_color((string) ($activation['slug'] ?? ''))) ?>">
                <div class="flex items-center justify-between gap-2">
                    <span class="flex items-center gap-2 font-bold"><img src="<?= e(operator_logo((string) ($activation['slug'] ?? ''))) ?>" alt="" class="h-7 w-7 rounded-md object-contain"><span><?= e($activation['operator_name']) ?></span></span>
                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] text-slate-500 dark:bg-slate-800">کد فعال‌سازی</span>
                </div>
                <div class="mt-4">
                    <?php if ((int) $activation['price'] > 0): ?>
                        <span class="text-lg font-extrabold text-teal-700 dark:text-teal-300"><?= price($activation['price']) ?></span>
                        <span class="text-xs text-slate-400">تومان</span>
                    <?php else: ?>
                        <span class="text-sm font-medium text-slate-400">قیمت به‌زودی اعلام می‌شود</span>
                    <?php endif; ?>
                </div>
                <?php if ((int) $activation['price'] > 0): ?>
                    <form method="post" action="<?= e(url('/cart/add-activation')) ?>" class="mt-3 flex gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="operator_id" value="<?= (int) $activation['operator_id'] ?>">
                        <label class="sr-only" for="qty-<?= (int) $activation['operator_id'] ?>">تعداد</label>
                        <input id="qty-<?= (int) $activation['operator_id'] ?>" type="number" name="qty" min="1" max="10" value="1" class="w-16 rounded-xl border border-slate-200 px-2 py-2 text-center text-sm dark:border-slate-700 dark:bg-slate-800">
                        <button type="submit" class="flex-1 rounded-xl bg-teal-600 px-3 py-2 text-xs font-bold text-white hover:bg-teal-700">افزودن به سبد</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="modems" class="mt-12 scroll-mt-24">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
        <div>
            <h2 class="text-xl font-extrabold">مودم‌های موجود</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">مودم‌های LTE و خانگی با گارانتی فروشنده.</p>
        </div>
        <a href="<?= e(url('/contact')) ?>" class="text-xs font-medium text-teal-700 hover:underline dark:text-teal-300">درخواست مدل خاص</a>
    </div>
    <?php if (($modems['items'] ?? []) === []): ?>
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900">مودمی موجود نیست؛ به‌زودی مدل‌های جدید اضافه می‌شود.</div>
    <?php else: ?>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($modems['items'] as $modem): ?>
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
                    <a href="<?= e(url('/modems/' . $modem['id'])) ?>">
                        <img src="<?= e(upload_url($modem['image_path'])) ?>" alt="<?= e($modem['brand'] . ' ' . $modem['model']) ?>" class="h-36 w-full object-cover" loading="lazy">
                    </a>
                    <div class="p-4">
                        <a href="<?= e(url('/modems/' . $modem['id'])) ?>" class="block font-bold hover:text-teal-700"><?= e($modem['brand'] . ' ' . $modem['model']) ?></a>
                        <span class="mt-1 block text-[11px] text-slate-400">موجودی: <?= fa_num($modem['stock']) ?> عدد</span>
                        <div class="mt-3 flex items-center justify-between gap-2">
                            <span class="text-sm font-extrabold text-teal-700 dark:text-teal-300"><?= price($modem['price']) ?> <span class="text-[10px] font-normal text-slate-400">تومان</span></span>
                            <form method="post" action="<?= e(url('/cart/add-modem')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $modem['id'] ?>">
                                <button class="rounded-lg bg-teal-600 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-teal-700">افزودن</button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="mt-12 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="مزایای خرید">
    <?php
    $features = [
        ['⚡', 'تحویل فوری', 'پس از پرداخت، اطلاعات شماره در همان لحظه تحویل داده می‌شود.'],
        ['🔒', 'پرداخت امن', 'پرداخت از طریق درگاه بانکی معتبر و تأیید خودکار تراکنش.'],
        ['🎧', 'پشتیبانی واقعی', 'پاسخ‌گویی تلفنی و تلگرامی برای انتخاب و استعلام شماره.'],
        ['↩️', 'تضمین بازگشت', 'در صورت خطای درگاه، مبلغ حداکثر ۷۲ ساعت کاری بازگردانده می‌شود.'],
    ];
    foreach ($features as [$icon, $titleText, $description]): ?>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <span class="text-2xl" aria-hidden="true"><?= $icon ?></span>
            <h3 class="mt-2 text-sm font-bold"><?= e($titleText) ?></h3>
            <p class="mt-1 text-xs leading-6 text-slate-500 dark:text-slate-400"><?= e($description) ?></p>
        </div>
    <?php endforeach; ?>
</section>
