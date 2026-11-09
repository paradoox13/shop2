<?php

declare(strict_types=1);

/**
 * ساخت فایل‌های نمونه ایمپورت در docs/samples
 * اجرا: php tests/make_samples.php
 */

$dir = __DIR__ . '/../docs/samples';

if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    fwrite(STDERR, "cannot create samples directory\n");
    exit(1);
}

/** @param array<int, array<int, string|int>> $rows */
function writeCsv(string $path, array $rows): void
{
    $handle = fopen($path, 'wb');

    if ($handle === false) {
        throw new RuntimeException('cannot write ' . $path);
    }

    fwrite($handle, "\xEF\xBB\xBF");

    foreach ($rows as $row) {
        fputcsv($handle, $row, ',', '"', '\\');
    }

    fclose($handle);
}

/**
 * ساخت XLSX حداقلی و معتبر با sharedStrings.
 * @param array<int, array<int, string|int>> $rows
 */
function writeXlsx(string $path, array $rows): void
{
    $shared = [];
    $sharedIndex = [];

    foreach ($rows as $row) {
        foreach ($row as $cell) {
            if (is_string($cell) && !isset($sharedIndex[$cell])) {
                $sharedIndex[$cell] = count($shared);
                $shared[] = $cell;
            }
        }
    }

    $columnName = static function (int $index): string {
        $name = '';
        $index++;

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $name = chr(65 + $mod) . $name;
            $index = (int) (($index - $mod) / 26);
        }

        return $name;
    };

    $sheetRows = '';

    foreach ($rows as $rowIndex => $row) {
        $excelRow = $rowIndex + 1;
        $cells = '';

        foreach ($row as $columnIndex => $value) {
            $ref = $columnName($columnIndex) . $excelRow;

            if (is_int($value) || (is_string($value) && preg_match('/^\d+$/', $value) === 1)) {
                $cells .= '<c r="' . $ref . '"><v>' . $value . '</v></c>';
            } else {
                $index = $sharedIndex[(string) $value] ?? 0;
                $cells .= '<c r="' . $ref . '" t="s"><v>' . $index . '</v></c>';
            }
        }

        $sheetRows .= '<row r="' . $excelRow . '">' . $cells . '</row>';
    }

    $sharedXml = '';

    foreach ($shared as $text) {
        $sharedXml .= '<si><t xml:space="preserve">' . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></si>';
    }

    $zip = new ZipArchive();

    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('cannot create ' . $path);
    }

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
        . '</Types>');

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>');

    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>');

    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
        . '</Relationships>');

    $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($shared) . '" uniqueCount="' . count($shared) . '">'
        . $sharedXml . '</sst>');

    $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
        . $sheetRows . '</sheetData></worksheet>');

    $zip->close();
}

$numberRows = [
    ['شماره', 'قیمت', 'اپراتور', 'تاریخ تولد', 'توضیحات'],
    ['09361234567', 1850000, 'ایرانسل', '1370/05/12', 'نمونه شماره با تاریخ تولد'],
    ['09121234001', 2700000, 'همراه اول', '', 'نمونه شماره بدون تاریخ تولد'],
    ['09901234002', 990000, 'همراه اول', '1380/01/01', 'نمونه شماره اقتصادی'],
];

$modemRows = [
    ['برند', 'مدل', 'قیمت', 'موجودی', 'توضیحات'],
    ['Xiaomi', 'Mi Router 4A', 1250000, 8, 'روتر خانگی دوباند'],
    ['ZTE', 'MF286R', 2100000, 4, 'مودم LTE با باتری'],
    ['Asus', '4G-AC53U', 3450000, 2, 'مودم LTE دوباند ایسوس'],
];

writeCsv($dir . '/numbers-sample.csv', $numberRows);
writeXlsx($dir . '/numbers-sample.xlsx', $numberRows);
writeCsv($dir . '/modems-sample.csv', $modemRows);
writeXlsx($dir . '/modems-sample.xlsx', $modemRows);

foreach (glob($dir . '/*') ?: [] as $file) {
    echo basename($file) . ' = ' . filesize($file) . " bytes\n";
}
