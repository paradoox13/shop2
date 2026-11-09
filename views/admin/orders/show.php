<?php
$order = $order ?? [];
$status = (string) ($order['status'] ?? '');
$physical = in_array($status, ['pending_physical_send', 'sent'], true);
?>
<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <div class="flex items-center gap-2 text-[11px] text-slate-400">
        <a href="<?= e(url('/admin/orders')) ?>" class="hover:text-teal-600">سفارش‌ها</a><span>/</span>
        <span class="ltr font-bold text-slate-600 dark:text-slate-300"><?= e($order['tracking_code'] ?? '') ?></span>
    </div>
    <a href="<?= e(url('/order/' . $order['tracking_code'])) ?>" target="_blank" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[10px] hover:border-teal-400 dark:border-slate-700 dark:bg-slate-900">نمای مشتری ↗</a>
</div>

<div class="grid gap-4 xl:grid-cols-[1.6fr_1fr]">
    <div class="space-y-4">
        <section class="rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4 dark:border-slate-800">
                <div>
                    <h2 class="text-base font-extrabold">سفارش <span class="ltr"><?= e($order['tracking_code']) ?></span></h2>
                    <p class="mt-1 text-[10px] text-slate-400"><?= fa_num(jalali_datetime((string) $order['created_at'])) ?> — نوع: <?= e($order['type'] === 'mixed' ? 'ترکیبی' : product_type_label((string) $order['type'])) ?></p>
                </div>
                <span class="rounded-full px-3 py-1 text-[11px] font-bold <?= e(status_badge_class($status)) ?>"><?= e(order_status_label($status)) ?></span>
            </div>

            <?php if ($physical): ?>
                <div class="mx-4 mt-4 rounded-xl bg-amber-50 p-3 text-[11px] leading-6 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                    این سفارش شامل محصول فیزیکی است. پس از ارسال، وضعیت را به «ارسال شده» تغییر دهید. مشتری از صفحه سفارش مطلع می‌شود.
                </div>
            <?php endif; ?>

            <div class="table-scroll">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-right text-[10px] text-slate-500 dark:bg-slate-800/50">
                        <tr><th class="p-3">محصول</th><th class="p-3">قیمت واحد</th><th class="p-3">تعداد</th><th class="p-3">جمع</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <?php $snapshot = \App\Models\OrderItem::decodeSnapshot((string) $item['snapshot']); ?>
                        <tr class="border-t border-slate-100 dark:border-slate-800">
                            <td class="p-3">
                                <span class="font-medium"><?= e(product_type_label((string) $item['product_type'])) ?></span>
                                <span class="mt-0.5 block text-[10px] text-slate-500">
                                    <?php if ($item['product_type'] === 'number'): ?>
                                        <span class="ltr digits font-bold"><?= e($snapshot['number'] ?? '') ?></span> — <?= e($snapshot['operator_name'] ?? '') ?>
                                        <?php if (!empty($snapshot['reseller_id'])): ?><span class="rounded bg-slate-100 px-1 text-[9px] dark:bg-slate-800">نماینده #<?= fa_num((int) $snapshot['reseller_id']) ?></span><?php endif; ?>
                                    <?php elseif ($item['product_type'] === 'modem'): ?>
                                        <?= e(($snapshot['brand'] ?? '') . ' ' . ($snapshot['model'] ?? '')) ?>
                                    <?php else: ?>
                                        <?= e($snapshot['operator_name'] ?? '') ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="p-3"><?= price($item['price']) ?></td>
                            <td class="p-3"><?= fa_num($item['qty']) ?></td>
                            <td class="p-3 font-bold"><?= price((int) $item['price'] * (int) $item['qty']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-slate-50 dark:bg-slate-800/50">
                        <tr><td colspan="3" class="p-3 text-left font-bold">مبلغ کل</td><td class="p-3 font-extrabold text-teal-700 dark:text-teal-300"><?= price($order['total_price']) ?> تومان</td></tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-sm font-bold">پرداخت‌ها</h3>
            <?php if ($payments === []): ?>
                <p class="mt-2 text-[11px] text-slate-400">پرداختی برای این سفارش ثبت نشده است.</p>
            <?php else: ?>
                <div class="table-scroll mt-2">
                    <table class="w-full text-[11px]">
                        <thead class="text-right text-[10px] text-slate-500"><tr><th class="p-2">درگاه</th><th class="p-2">مبلغ</th><th class="p-2">وضعیت</th><th class="p-2">کد پیگیری</th><th class="p-2">تاریخ</th></tr></thead>
                        <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr class="border-t border-slate-100 dark:border-slate-800">
                                <td class="p-2"><?= e($payment['gateway']) ?></td>
                                <td class="p-2"><?= price($payment['amount']) ?></td>
                                <td class="p-2"><span class="rounded px-1.5 py-0.5 text-[9px] <?= e(status_badge_class((string) $payment['status'])) ?>"><?= e($payment['status']) ?></span></td>
                                <td class="ltr p-2"><?= e($payment['ref_id'] ?? '—') ?></td>
                                <td class="p-2 text-[10px] text-slate-400"><?= fa_num(jalali_datetime((string) ($payment['paid_at'] ?? $payment['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-sm font-bold">رزروهای مرتبط</h3>
            <?php if ($reservations === []): ?>
                <p class="mt-2 text-[11px] text-slate-400">رزرو فعالی برای این سفارش نیست.</p>
            <?php else: ?>
                <ul class="mt-2 grid gap-1.5 text-[10px] sm:grid-cols-2">
                    <?php foreach ($reservations as $reservation): ?>
                        <li class="rounded-xl border border-slate-100 p-2 dark:border-slate-800">
                            <span class="ltr block truncate text-slate-500"><?= e($reservation['token'] ?? '') ?></span>
                            <span class="mt-1 block text-slate-400">انقضا: <?= fa_num(jalali_datetime((string) ($reservation['expires_at'] ?? ''))) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <aside class="space-y-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-sm font-bold">مشخصات خریدار</h3>
            <dl class="mt-3 space-y-2 text-xs">
                <div><dt class="text-[10px] text-slate-400">نام</dt><dd class="font-medium"><?= e($order['customer_name']) ?></dd></div>
                <div><dt class="text-[10px] text-slate-400">موبایل</dt><dd class="ltr font-medium"><?= e($order['customer_phone']) ?></dd></div>
                <div><dt class="text-[10px] text-slate-400">ایمیل</dt><dd class="ltr font-medium"><?= e($order['customer_email'] ?? '—') ?></dd></div>
                <div><dt class="text-[10px] text-slate-400">یادداشت فعلی</dt><dd class="leading-6 text-slate-600 dark:text-slate-300"><?= e($order['admin_note'] ?? '—') ?></dd></div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-sm font-bold">تغییر وضعیت</h3>
            <?php if ($allowedStatuses === []): ?>
                <p class="mt-2 rounded-xl bg-slate-50 p-3 text-[11px] leading-6 text-slate-500 dark:bg-slate-800/50">برای این وضعیت، تغییر مجاز دیگری تعریف نشده است.</p>
            <?php else: ?>
                <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/status')) ?>" class="mt-3 space-y-3">
                    <?= csrf_field() ?>
                    <div>
                        <label for="status" class="mb-1 block text-[10px] text-slate-500">وضعیت جدید</label>
                        <select id="status" name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-800">
                            <?php foreach ($allowedStatuses as $statusOption): ?>
                                <option value="<?= e($statusOption->value) ?>"><?= e($statusOption->label()) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="admin_note" class="mb-1 block text-[10px] text-slate-500">یادداشت برای مشتری (اختیاری)</label>
                        <textarea id="admin_note" name="admin_note" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-800"><?= e($order['admin_note'] ?? '') ?></textarea>
                    </div>
                    <button class="w-full rounded-xl bg-slate-800 py-2.5 text-xs font-bold text-white hover:bg-slate-900 dark:bg-slate-700">ثبت تغییر وضعیت</button>
                </form>
            <?php endif; ?>
            <p class="mt-3 text-[10px] leading-6 text-slate-400">وضعیت «پرداخت شده» فقط از طریق تأیید درگاه ثبت می‌شود. لغو/بازگشت وجه، رزروهای سفارش را آزاد می‌کند.</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 text-[10px] leading-7 text-slate-500 dark:border-slate-800 dark:bg-slate-900">
            <p>شناسه سفارش: <span class="font-bold">#<?= fa_num($order['id']) ?></span></p>
            <p>شناسه کاربر: <span class="ltr"><?= $order['user_id'] !== null ? fa_num((int) $order['user_id']) : 'مهمان' ?></span></p>
            <p>آخرین به‌روزرسانی: <?= fa_num(jalali_datetime((string) $order['updated_at'])) ?></p>
        </section>
    </aside>
</div>
