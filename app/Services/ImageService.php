<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * آپلود تصویر بدون Imagick (فقط GD). فایل ورودی بازکدگذاری می‌شود تا
 * محتوای مخرب/پلی‌گلات حذف و ابعاد بزرگ کوچک شوند.
 */
final class ImageService
{
    private const MAX_BYTES = 3 * 1024 * 1024;

    private const MAX_DIMENSION = 1200;

    /**
     * @param array<string, mixed> $file
     * @return string مسیر نسبی برای ذخیره در دیتابیس: uploads/modems/xxx.jpg
     */
    public function store(array $file, string $subdir = 'modems'): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('آپلود تصویر ناموفق بود.');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new RuntimeException('حجم تصویر باید کمتر از ۳ مگابایت باشد.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if ($tmp === '' || !is_file($tmp)) {
            throw new RuntimeException('فایل تصویر یافت نشد.');
        }

        $info = @getimagesize($tmp);

        if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new RuntimeException('فرمت تصویر باید JPG، PNG یا WebP باشد.');
        }

        $contents = file_get_contents($tmp);
        $image = $contents !== false ? @imagecreatefromstring($contents) : false;

        if ($image === false) {
            throw new RuntimeException('تصویر قابل پردازش نیست.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width < 10 || $height < 10) {
            imagedestroy($image);

            throw new RuntimeException('ابعاد تصویر نامعتبر است.');
        }

        $ratio = min(1, self::MAX_DIMENSION / max($width, $height));
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $relativeDir = 'uploads/' . trim($subdir, '/');
        $absoluteDir = rtrim((string) config('paths.uploads'), '/') . '/' . trim($subdir, '/');

        if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
            imagedestroy($image);
            imagedestroy($canvas);

            throw new RuntimeException('پوشه تصاویر قابل ایجاد نیست.');
        }

        $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.jpg';
        $destination = $absoluteDir . '/' . $name;

        $saved = imagejpeg($canvas, $destination, 85);
        imagedestroy($image);
        imagedestroy($canvas);

        if (!$saved) {
            throw new RuntimeException('ذخیره تصویر ناموفق بود.');
        }

        @chmod($destination, 0644);

        return $relativeDir . '/' . $name;
    }

    public function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '' || !str_starts_with($relativePath, 'uploads/')) {
            return;
        }

        $base = realpath((string) config('paths.uploads'));

        if ($base === false) {
            return;
        }

        $target = realpath(rtrim((string) config('paths.uploads'), '/') . '/' . substr($relativePath, strlen('uploads/')));

        if ($target !== false && str_starts_with($target, $base) && is_file($target)) {
            @unlink($target);
        }
    }
}
