<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * رندر قالب‌های PHP خالص با لایه‌بندی (Layout).
 * هیچ موتور قالبی وابسته به Composer استفاده نمی‌شود.
 */
final class View
{
    public static function render(string $template, array $data = [], string $layout = 'main'): string
    {
        $viewsDir = rtrim((string) config('paths.views', BASE_PATH . '/views'), '/');
        $templateFile = $viewsDir . '/' . trim($template, '/') . '.php';

        if (!is_file($templateFile)) {
            throw new RuntimeException('قالب یافت نشد: ' . $template);
        }

        $data['title'] ??= config('app.name', 'آوال سیم');
        $data['metaDescription'] ??= (string) config('app.description', '');

        $content = self::renderFile($templateFile, $data);

        if ($layout === '' || $layout === 'none') {
            return $content;
        }

        $layoutFile = $viewsDir . '/layouts/' . trim($layout, '/') . '.php';

        if (!is_file($layoutFile)) {
            throw new RuntimeException('Layout یافت نشد: ' . $layout);
        }

        return self::renderFile($layoutFile, array_merge($data, ['content' => $content]));
    }

    public static function partial(string $template, array $data = []): string
    {
        $file = rtrim((string) config('paths.views', BASE_PATH . '/views'), '/') . '/' . trim($template, '/') . '.php';

        if (!is_file($file)) {
            return '';
        }

        return self::renderFile($file, $data);
    }

    /** @param array<string, mixed> $data */
    private static function renderFile(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();

        try {
            require $file;

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }
    }
}
