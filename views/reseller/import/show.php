<?php
$statusLabels = ['pending' => 'در انتظار شروع', 'processing' => 'در حال پردازش', 'completed' => 'تکمیل شده', 'failed' => 'ناموفق'];
$p = $progress ?? ['status' => 'pending', 'processed' => 0, 'total' => 0, 'inserted' => 0, 'failed' => 0, 'percent' => 0, 'message' => ''];
$canProcess = in_array($p['status'], ['pending', 'processing'], true);
$errors = trim((string) ($log['error_log'] ?? ''));
?>
<div class="mb-4 flex items-center gap-2 text-[11px] text-slate-400">
    <a href="<?= e(url('/reseller/import')) ?>" class="hover:text-teal-600">ایمپورت‌ها</a><span>/</span><span>#<?= fa_num($log['id']) ?></span>
</div>

<div class="mx-auto max-w-3xl">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold">پردازش ایمپورت #<?= fa_num($log['id']) ?></h2>
                <p class="mt-1 text-xs text-slate-500">
                    نوع: <?= $log['type'] === 'modem' ? 'مودم' : 'شماره' ?> —
                    فایل: <span class="ltr text-[10px]"><?= e(basename((string) $log['file_path'])) ?></span>
                </p>
            </div>
            <span id="status-badge" class="rounded-full px-3 py-1 text-[10px] font-bold <?= e(status_badge_class((string) $p['status'])) ?>"><?= e($statusLabels[(string) $p['status']] ?? $p['status']) ?></span>
        </div>

        <div class="mt-5">
            <div class="mb-1.5 flex items-center justify-between text-[10px] text-slate-500">
                <span id="progress-text"><?= fa_num($p['processed']) ?> از <?= fa_num($p['total']) ?> ردیف</span>
                <span id="progress-percent" class="font-bold"><?= fa_num($p['percent']) ?>٪</span>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div id="progress-bar" class="h-full rounded-full bg-gradient-to-l from-teal-500 to-emerald-400 transition-all duration-300" style="width: <?= (int) $p['percent'] ?>%"></div>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
            <div class="rounded-xl bg-slate-50 p-3 text-center dark:bg-slate-800/50">
                <span class="block text-lg font-extrabold" id="stat-total"><?= fa_num($p['total']) ?></span>
                <span class="text-[9px] text-slate-400">کل ردیف</span>
            </div>
            <div class="rounded-xl bg-emerald-50 p-3 text-center dark:bg-emerald-950/30">
                <span class="block text-lg font-extrabold text-emerald-700 dark:text-emerald-300" id="stat-inserted"><?= fa_num($p['inserted']) ?></span>
                <span class="text-[9px] text-slate-400">ثبت موفق</span>
            </div>
            <div class="rounded-xl bg-rose-50 p-3 text-center dark:bg-rose-950/30">
                <span class="block text-lg font-extrabold text-rose-700 dark:text-rose-300" id="stat-failed"><?= fa_num($p['failed']) ?></span>
                <span class="text-[9px] text-slate-400">ردیف ناموفق</span>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 text-center dark:bg-slate-800/50">
                <span class="block text-lg font-extrabold" id="stat-processed"><?= fa_num($p['processed']) ?></span>
                <span class="text-[9px] text-slate-400">پردازش‌شده</span>
            </div>
        </div>

        <p id="progress-message" class="mt-3 hidden rounded-xl bg-sky-50 p-3 text-[11px] text-sky-800 dark:bg-sky-950/40 dark:text-sky-200"></p>

        <div class="mt-5 flex flex-wrap gap-2">
            <?php if ($canProcess): ?>
                <button type="button" id="start-btn" data-url="<?= e(url('/reseller/import/' . $log['id'] . '/process')) ?>" data-token="<?= e(csrf_token()) ?>" class="rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-teal-700">شروع / ادامه پردازش</button>
            <?php endif; ?>
            <a href="<?= e(url('/reseller/import')) ?>" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm dark:border-slate-700">بازگشت به ایمپورت‌ها</a>
            <a href="<?= e(url('/reseller/numbers')) ?>" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm dark:border-slate-700">مشاهده شماره‌ها</a>
        </div>

        <div class="mt-4 rounded-xl bg-slate-50 p-3 text-[10px] leading-6 text-slate-500 dark:bg-slate-800/50 dark:text-slate-400">
            هر مرحله حداکثر ۱۰۰ ردیف را پردازش می‌کند تا محدودیت ۳۰ ثانیه‌ای هاست رعایت شود. اگر صفحه را ببندید، Cron با فایل <code class="ltr">import_worker.php</code> ادامه کار را انجام می‌دهد.
        </div>

        <?php if ($errors !== ''): ?>
            <div class="mt-4">
                <h3 class="text-xs font-bold text-rose-700 dark:text-rose-300">گزارش خطاهای ایمپورت</h3>
                <pre class="mt-2 max-h-64 overflow-auto whitespace-pre-wrap rounded-xl bg-rose-50 p-3 text-[10px] leading-6 text-rose-800 dark:bg-rose-950/30 dark:text-rose-200"><?= e($errors) ?></pre>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('start-btn');
    if (!btn) return;

    var url = btn.dataset.url;
    var token = btn.dataset.token;
    var running = false;

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function toFa(value) {
        return String(value).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
    }

    function paint(data) {
        var percent = Math.max(0, Math.min(100, parseInt(data.percent || 0, 10)));
        var bar = document.getElementById('progress-bar');
        if (bar) bar.style.width = percent + '%';
        setText('progress-percent', toFa(percent) + '٪');
        setText('progress-text', toFa(data.processed || 0) + ' از ' + toFa(data.total || 0) + ' ردیف');
        setText('stat-total', toFa(data.total || 0));
        setText('stat-processed', toFa(data.processed || 0));
        setText('stat-inserted', toFa(data.inserted || 0));
        setText('stat-failed', toFa(data.failed || 0));

        var msg = document.getElementById('progress-message');
        if (msg && data.message) {
            msg.textContent = data.message;
            msg.classList.remove('hidden');
        }
    }

    function run() {
        if (running) return;
        running = true;
        btn.disabled = true;
        btn.textContent = 'در حال پردازش…';

        var form = new FormData();
        form.append('_token', token);

        fetch(url, {
            method: 'POST',
            body: form,
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('پاسخ نامعتبر از سرور (' + response.status + ')');
            }
            return response.json();
        }).then(function (data) {
            paint(data);
            running = false;

            if (data.status === 'pending' || data.status === 'processing') {
                btn.disabled = false;
                btn.textContent = 'ادامه پردازش';
                setTimeout(run, 350);
            } else {
                btn.textContent = data.status === 'completed' ? 'تکمیل شد ✓' : 'پایان با خطا';
                btn.classList.toggle('bg-rose-600', data.status === 'failed');
                btn.disabled = true;
                var badge = document.getElementById('status-badge');
                if (badge) {
                    badge.textContent = data.status === 'completed' ? 'تکمیل شده' : 'ناموفق';
                    badge.className = 'rounded-full px-3 py-1 text-[10px] font-bold ' + (data.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800');
                }
            }
        }).catch(function (error) {
            running = false;
            btn.disabled = false;
            btn.textContent = 'تلاش مجدد';
            var msg = document.getElementById('progress-message');
            if (msg) {
                msg.textContent = 'خطا در پردازش: ' + error.message + ' — می‌توانید دوباره تلاش کنید.';
                msg.classList.remove('hidden');
            }
        });
    }

    btn.addEventListener('click', run);
})();
</script>
