<?php

declare(strict_types=1);

namespace App\Support;

/**
 * پیام‌های فلش، خطاهای اعتبارسنجی و ورودی‌های قبلی.
 * در ابتدای درخواست از سشن خوانده و پاک می‌شوند (یک‌بارمصرف).
 */
final class Flash
{
    private const SESSION_KEY = '_flash';

    /** @var array<int, array{type:string, message:string}> */
    private static array $messages = [];

    /** @var array<string, string> */
    private static array $errors = [];

    /** @var array<string, mixed> */
    private static array $old = [];

    public static function boot(): void
    {
        $payload = Session::pull(self::SESSION_KEY);

        if (!is_array($payload)) {
            return;
        }

        self::$messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
        self::$errors = is_array($payload['errors'] ?? null) ? $payload['errors'] : [];
        self::$old = is_array($payload['old'] ?? null) ? $payload['old'] : [];
    }

    public static function add(string $type, string $message): void
    {
        Session::put(self::SESSION_KEY, self::payload(
            messages: array_merge(self::sessionMessages(), [['type' => $type, 'message' => $message]])
        ));
    }

    /** @param array<string, string> $errors */
    public static function withErrors(array $errors, array $old = [], ?string $message = null): void
    {
        $messages = self::sessionMessages();

        if ($message !== null && $message !== '') {
            $messages[] = ['type' => 'error', 'message' => $message];
        }

        Session::put(self::SESSION_KEY, [
            'messages' => $messages,
            'errors' => $errors,
            'old' => self::sanitizeOld($old),
        ]);

        self::$errors = $errors;
        self::$old = self::sanitizeOld($old);
    }

    /** @return array<int, array{type:string, message:string}> */
    public static function messages(): array
    {
        return self::$messages;
    }

    /** @return array<string, string> */
    public static function errors(): array
    {
        return self::$errors;
    }

    public static function error(string $field): ?string
    {
        return self::$errors[$field] ?? null;
    }

    public static function old(string $field, mixed $default = ''): mixed
    {
        return self::$old[$field] ?? $default;
    }

    /** @return array<string, mixed> */
    public static function allOld(): array
    {
        return self::$old;
    }

    /** @param array<string, mixed> $messages */
    private static function payload(array $messages): array
    {
        return [
            'messages' => $messages,
            'errors' => self::$errors,
            'old' => self::$old,
        ];
    }

    /** @return array<int, array{type:string, message:string}> */
    private static function sessionMessages(): array
    {
        $payload = Session::get(self::SESSION_KEY);
        $messages = is_array($payload) && is_array($payload['messages'] ?? null) ? $payload['messages'] : [];

        return array_values(array_filter($messages, static fn ($m): bool => is_array($m) && isset($m['type'], $m['message'])));
    }

    /** @param array<string, mixed> $old */
    private static function sanitizeOld(array $old): array
    {
        foreach (['password', 'password_confirmation', 'current_password', '_token', 'totp_code'] as $sensitive) {
            unset($old[$sensitive]);
        }

        return $old;
    }
}
