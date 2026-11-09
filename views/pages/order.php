<?php
$status = (string) $order['status'];
$statusLabel = order_status_label($status);
$pending = in_array($status, ['pending', 'awaiting_payment'], true);
$physical = in_array($status, ['pending_physical_send', 'sent'], true);
?>
<div class="mx-auto max-w-3xl">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs text-slate-500">کد رهگیری سفارش</p>
                <div class="mt-1 flex items-center gap-2">
                    <span class="ltr text-lg font-extrabold tracking-wider"><?= e($order['tracking_code']) ?></span>
                    <button type="button" onclick="copyText('<?= e($order['tracking_code']) ?>', this)" class="rounded-lg border border-slate-200 px-2 py-1 text-[10px] text-slate-500 dark:border-slate-700">کپی</button>
                </div>
                <p class="mt-1 text-[11px] text-slate-400">تاریخ ثبت: <?= jalali_datetime((string) $order['created_at']) ?></p>
            </div>
            <span class="rounded-full px-3 py-1.5 text-xs font-bold <?= e(status_badge_class($status)) ?>"><?= e($statusLabel) ?></span>
        </div>

        <?php if ($status === 'paid'): ?>
            <div class="mt-4 rounded-xl bg-emerald-50 p-4 text-sm leading-7 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                پرداخت با موفقیت تأیید شد. شماره‌های خریداری‌شده در بخش اقلام سفارش قابل مشاهده است و به نام شما ثبت شده‌اند.
            </div>
        <?php elseif ($physical): ?>
            <div class="mt-4 rounded-xl bg-amber-50 p-4 text-sm leading-7 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                پرداخت تأیید شد. سفارش شما شامل محصول فیزیکی (کد فعال‌سازی/مودم) است و پس از آماده‌سازی ارسال می‌شود. وضعیت ارسال از همین صفحه قابل پیگیری است.
            </div>
        <?php elseif ($pending): ?>
            <div class="mt-4 rounded-xl bg-amber-50 p-4 text-sm leading-7 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                سفارش در انتظار پرداخت است. اگر پرداخت را انجام داده‌اید، تا ۱۰ دقیقه آینده وضعیت به‌صورت خودکار به‌روزرسانی می‌شود. در صورت نیاز، پرداخت را دوباره آغاز کنید.
            </div>
        <?php else: ?>
            <div class="mt-4 rounded-xl bg-rose-50 p-4 text-sm leading-7 text-rose-800 dark:bg-rose-950/40 dark:text-rose-200">
                <?= e(order_status_label($status)) ?>. <?= e((string) ($order['admin_note'] ?? '')) ?>
            </div>
        <?php endif; ?>

        <div class="mt-5 overflow-hidden rounded-xl border border-slate-100 dark:border-slate-800">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-right text-xs text-slate-500 dark:bg-slate-800/60">
                    <tr><th class="p-3">آیتم</th><th class="p-3">قیمت</th><th class="p-3">تعداد</th><th class="p-3">جمع</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <?php $snapshot = $item['snapshot_array']; ?>
                    <tr class="border-t border-slate-100 dark:border-slate-800">
                        <td class="p-3">
                            <span class="font-medium"><?= e(product_type_label((string) $item['product_type'])) ?></span>
                            <span class="mt-0.5 block text-[11px] text-slate-500">
                                <?php if ($item['product_type'] === 'number'): ?>
                                    <span class="ltr digits"><?= e((string) ($snapshot['number'] ?? '')) ?></span> — <?= e((string) ($snapshot['operator_name'] ?? '')) ?>
                                <?php elseif ($item['product_type'] === 'modem'): ?>
                                    <?= e(($snapshot['brand'] ?? '') . ' ' . ($snapshot['model'] ?? '')) ?>
                                <?php else: ?>
                                    <?= e((string) ($snapshot['operator_name'] ?? '')) ?>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="p-3 text-xs"><?= price($item['price']) ?></td>
                        <td class="p-3 text-xs"><?= fa_num($item['qty']) ?></td>
                        <td class="p-3 text-xs font-bold"><?= price((int) $item['price'] * (int) $item['qty']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-800/60">
                    <tr><td colspan="3" class="p-3 text-left text-xs font-bold">مبلغ کل</td><td class="p-3 font-extrabold text-teal-700 dark:text-teal-300"><?= price($order['total_price']) ?> تومان</td></tr>
                </tfoot>
            </table>
        </div>

        <dl class="mt-4 grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
            <div>نام خریدار: <span class="font-medium text-slate-700 dark:text-slate-300"><?= e($order['customer_name']) ?></span></div>
            <div>موبایل: <span class="ltr font-medium text-slate-700 dark:text-slate-300"><?= e($order['customer_phone']) ?></span></div>
            <?php if (!empty($order['admin_note'])): ?>
                <div class="sm:col-span-2">یادداشت: <?= e($order['admin_note']) ?></div>
            <?php endif; ?>
        </dl>

        <div class="mt-5 flex flex-wrap gap-2">
            <?php if ($canRetry): ?>
                <form method="post" action="<?= e(url('/payment/start')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="tracking_code" value="<?= e($order['tracking_code']) ?>">
                    <button class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-teal-700">تلاش مجدد پرداخت</button>
                </form>
            <?php endif; ?>
            <a href="<?= e(url('/')) ?>" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm hover:border-teal-400 dark:border-slate-700">بازگشت به فروشگاه</a>
            <?php if (setting('support_phone') !== ''): ?>
                <a href="tel:<?= e(preg_replace('/\D+/', '', setting('support_phone'))) ?>" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm hover:border-teal-400 dark:border-slate-700">تماس با پشتیبانی</a>
            <?php endif; ?>
        </div>

        <?php if ($payments !== []): ?>
            <div class="mt-6">
                <h2 class="mb-2 text-xs font-bold">تاریخچه پرداخت</h2>
                <div class="table-scroll">
                    <table class="w-full text-xs">
                        <thead class="text-right text-slate-500"><tr><th class="p-2">درگاه</th><th class="p-2">مبلغ</th><th class="p-2">وضعیت</th><th class="p-2">کد پیگیری</th><th class="p-2">تاریخ</th></tr></thead>
                        <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr class="border-t border-slate-100 dark:border-slate-800">
                                <td class="p-2"><?= e((string) $payment['gateway']) ?></td>
                                <td class="p-2"><?= price($payment['amount']) ?></td>
                                <td class="p-2"><?= e((string) $payment['status']) ?></td>
                                <td class="ltr p-2"><?= e((string) ($payment['ref_id'] ?? '-')) ?></td>
                                <td class="p-2"><?= jalali_datetime((string) ($payment['paid_at'] ?? $payment['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
