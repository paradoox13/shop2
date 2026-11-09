<?php

declare(strict_types=1);

namespace App\Support;

/**
 * ارسال ایمیل با mail() محلی هاست (sendmail → localhost:25).
 * ایمیل فقط نقش پشتیبان دارد؛ پیامک مسیر اصلی اطلاع‌رسانی است.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        if (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $from = (string) (env('ADMIN_EMAIL', 'no-reply@' . (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost')));
        $from = filter_var($from, FILTER_VALIDATE_EMAIL) !== false ? $from : 'no-reply@localhost';
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: ' . (string) config('app.name', 'AvalSim') . ' <' . $from . '>',
            'Reply-To: ' . $from,
            'X-Mailer: AvalSim',
        ];

        $result = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));

        if (!$result) {
            Logger::warning('Email send failed', ['to' => $to, 'subject' => $subject]);
        }

        return $result;
    }
}
