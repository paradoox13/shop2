<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Job;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Services\Sms\KavenegarProvider;
use App\Services\Sms\MelipayamakProvider;
use App\Services\Sms\SmsProviderInterface;
use App\Support\Logger;
use App\Support\Str;
use RuntimeException;
use Throwable;

/**
 * مسیر ارسال پیامک: SmsLog + Job → Cron (sms_worker) → Provider
 * هیچ ارسال مستقیمی در مسیر درخواست وب انجام نمی‌شود.
 */
final class SmsService
{
    private ?SmsProviderInterface $providerInstance = null;

    public function provider(): SmsProviderInterface
    {
        if ($this->providerInstance !== null) {
            return $this->providerInstance;
        }

        $name = Setting::get('sms_provider');

        if ($name === '') {
            $name = (string) config('sms.provider', 'kavenegar');
        }

        return $this->providerInstance = match ($name) {
            'melipayamak' => new MelipayamakProvider(),
            'kavenegar' => new KavenegarProvider(),
            default => throw new RuntimeException('سرویس پیامک نامعتبر است.'),
        };
    }

    public function queue(string $phone, string $template, string $message, ?int $orderId = null): int
    {
        $normalized = Str::normalizeMobile($phone);
        $phone = $normalized ?? Str::toEnglishDigits($phone);

        $logId = SmsLog::queue($phone, $template, $message, $orderId);
        Job::enqueue('sms', ['log_id' => $logId]);

        return $logId;
    }

    public function sendLog(int $logId): void
    {
        $log = SmsLog::find($logId);

        if ($log === null || $log['status'] === 'sent') {
            return;
        }

        try {
            $result = $this->provider()->send((string) $log['phone'], (string) $log['message']);

            if (!$result['success']) {
                throw new RuntimeException($result['response']);
            }

            SmsLog::markSent($logId, $this->provider()->name(), $result['response']);
        } catch (Throwable $e) {
            SmsLog::markFailed($logId, $e->getMessage());
            Logger::warning('SMS send failed', ['sms_log_id' => $logId, 'error' => $e->getMessage()]);

            throw $e;
        }
    }

    public function retry(int $logId): bool
    {
        $log = SmsLog::find($logId);

        if ($log === null || $log['status'] === 'sent') {
            return false;
        }

        SmsLog::resetForRetry($logId);
        Job::enqueue('sms', ['log_id' => $logId]);

        return true;
    }

    public function stats(): array
    {
        return SmsLog::stats();
    }
}
