<?php

declare(strict_types=1);

namespace App\Support;

use Generator;
use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * خواننده سبک XLSX بدون Composer (بر پایه ZipArchive + XMLReader).
 * جایگزین SimpleXLSX برای این پروژه است؛ در صورت نیاز می‌توانید فایل
 * SimpleXLSX.php را در lib/ قرار داده و از آن استفاده کنید (سازگار با PHP 8.2).
 *
 * محدودیت عمدی: مقادیر تاریخ باید به‌صورت متن شمسی/میلادی وارد شوند
 * (مانند 1370/05/12) تا وابستگی به styles.xml و serial تاریخ اکسل حذف شود.
 */
final class XlsxReader
{
    public function __construct(private readonly string $file)
    {
        if (!is_file($this->file)) {
            throw new RuntimeException('فایل اکسل یافت نشد.');
        }
    }

    /**
     * @return Generator<int, array<int, string|null>>
     */
    public function rows(int $sheet = 1): Generator
    {
        $zip = new ZipArchive();

        if ($zip->open($this->file) !== true) {
            throw new RuntimeException('فایل اکسل قابل باز کردن نیست.');
        }

        try {
            $sharedStrings = $this->sharedStrings($zip);
            $sheetPath = $this->sheetPath($zip, $sheet);

            $xml = $zip->getFromName($sheetPath);

            if ($xml === false) {
                throw new RuntimeException('شیت مورد نظر در فایل اکسل یافت نشد.');
            }

            if (strlen($xml) > 25 * 1024 * 1024) {
                throw new RuntimeException('حجم شیت اکسل بیش از حد بزرگ است.');
            }

            $tmp = tmpfile();

            if ($tmp === false) {
                throw new RuntimeException('امکان پردازش فایل اکسل وجود ندارد.');
            }

            fwrite($tmp, $xml);
            $meta = stream_get_meta_data($tmp);
            $uri = $meta['uri'];

            $reader = new XMLReader();

            if (!$reader->open($uri)) {
                fclose($tmp);

                throw new RuntimeException('خواندن شیت اکسل ناموفق بود.');
            }

            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                $rowXml = $reader->readOuterXML();

                if ($rowXml === '' || $rowXml === false) {
                    continue;
                }

                yield $this->parseRow($rowXml, $sharedStrings);
            }

            $reader->close();
            fclose($tmp);
        } finally {
            $zip->close();
        }
    }

    /** @return array<int, string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        $data = $zip->getFromName('xl/sharedStrings.xml');

        if ($data === false) {
            return [];
        }

        if (strlen($data) > 25 * 1024 * 1024) {
            throw new RuntimeException('فایل sharedStrings بیش از حد بزرگ است.');
        }

        $reader = new XMLReader();

        if (!$reader->XML($data)) {
            return [];
        }

        $strings = [];

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                $xml = $reader->readOuterXML();
                $strings[] = $this->textOf($xml);
            }
        }

        $reader->close();

        return $strings;
    }

    private function sheetPath(ZipArchive $zip, int $sheet): string
    {
        $index = max(1, $sheet);

        if ($zip->locateName('xl/worksheets/sheet' . $index . '.xml') !== false) {
            return 'xl/worksheets/sheet' . $index . '.xml';
        }

        throw new RuntimeException('شیت ' . $index . ' در فایل اکسل وجود ندارد.');
    }

    private function textOf(string $xml): string
    {
        if ($xml === '') {
            return '';
        }

        $document = @simplexml_load_string($xml);

        if ($document === false) {
            return '';
        }

        $texts = $document->xpath('//*[local-name()="t"]');

        if ($texts === false) {
            return '';
        }

        $text = '';

        foreach ($texts as $node) {
            $text .= (string) $node;
        }

        return $text;
    }

    /**
     * @param array<int, string> $sharedStrings
     * @return array<int, string|null>
     */
    private function parseRow(string $rowXml, array $sharedStrings): array
    {
        $row = @simplexml_load_string($rowXml);

        if ($row === false) {
            return [];
        }

        $cells = [];
        $autoIndex = 0;

        foreach ($row->children() as $cell) {
            if ($cell->getName() !== 'c') {
                continue;
            }

            $attributes = $cell->attributes();
            $ref = isset($attributes['r']) ? (string) $attributes['r'] : '';
            $type = isset($attributes['t']) ? (string) $attributes['t'] : '';
            $index = $ref !== '' ? $this->columnIndex($ref) : $autoIndex;

            $value = '';

            if ($type === 'inlineStr') {
                $value = $this->textOf($cell->asXML() ?: '');
            } else {
                $v = $cell->xpath('./*[local-name()="v"]');
                $raw = ($v !== false && isset($v[0])) ? (string) $v[0] : '';

                if ($type === 's') {
                    $sharedIndex = (int) $raw;
                    $value = $sharedStrings[$sharedIndex] ?? '';
                } else {
                    $value = $raw;
                }
            }

            $cells[$index] = $value === '' ? null : trim($value);
            $autoIndex = $index + 1;
        }

        if ($cells === []) {
            return [];
        }

        $max = max(array_keys($cells));
        $result = [];

        for ($i = 0; $i <= $max; $i++) {
            $result[] = $cells[$i] ?? null;
        }

        return $result;
    }

    private function columnIndex(string $reference): int
    {
        $letters = (string) preg_replace('/[^A-Z]/', '', strtoupper($reference));
        $index = 0;

        for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }
}
