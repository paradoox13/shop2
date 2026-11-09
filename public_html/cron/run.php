<?php

declare(strict_types=1);

/**
 * نقطه ورود وب برای Cron در هاست اشتراکی بدون SSH.
 * آدرس: https://example.com/cron/run.php?task=sms_worker&token=CRON_TOKEN
 * توکن در config/env.php و با CRON_TOKEN تنظیم می‌شود.
 */

require __DIR__ . '/../../cron/run.php';
