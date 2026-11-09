<?php

declare(strict_types=1);

namespace App\Http;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $template, array $data = [], string $layout = 'main'): string
    {
        return \App\Support\View::render($template, $data, $layout);
    }

    protected function redirect(string $path, int $status = 302): never
    {
        Response::redirect($path, $status);
    }

    protected function back(string $fallback = '/'): never
    {
        Response::back($fallback);
    }

    /**
     * اعتبارسنجی ورودی و پرتاب ValidationException (هندلر سراسری پیام‌ها را به فرم برمی‌گرداند)
     *
     * @param array<string, mixed> $rules
     * @param array<string, string> $labels
     * @return array<string, mixed>
     */
    protected function validate(Request $request, array $rules, array $labels = []): array
    {
        $validator = \App\Support\Validator::make($request->all(), $rules, $labels);

        if ($validator->fails()) {
            throw new \App\Exceptions\ValidationException($validator->errors(), $request->all());
        }

        return $validator->validated();
    }

    protected function requirePermission(string $permission): void
    {
        if (!\App\Support\Auth::can($permission)) {
            throw new \App\Exceptions\ForbiddenException('شما به این بخش دسترسی ندارید.');
        }
    }

    protected function user(): ?array
    {
        return \App\Support\Auth::user();
    }

    protected function userId(): ?int
    {
        return \App\Support\Auth::id();
    }

    protected function currentIp(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
}
