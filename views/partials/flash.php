<?php
$messages = flash_messages();

if ($messages === []) {
    return;
}

$styles = [
    'success' => ['border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200', '✓'],
    'error' => ['border-rose-300 bg-rose-50 text-rose-900 dark:border-rose-900 dark:bg-rose-950/60 dark:text-rose-200', '!'],
    'info' => ['border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-900 dark:bg-sky-950/60 dark:text-sky-200', 'i'],
];
?>
<div class="space-y-2 mb-4" role="status" aria-live="polite">
    <?php foreach ($messages as $message): ?>
        <?php [$classes, $icon] = $styles[$message['type']] ?? $styles['info']; ?>
        <div class="js-auto-hide flex items-start gap-3 rounded-xl border px-4 py-3 text-sm leading-6 <?= e($classes) ?>">
            <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-current text-xs font-bold"><?= e($icon) ?></span>
            <span class="flex-1"><?= nl2br(e($message['message'])) ?></span>
        </div>
    <?php endforeach; ?>
</div>
