<?php

declare(strict_types=1);

/**
 * داده‌های آزمایشی فقط برای محیط توسعه.
 * این فایل در production اجرا نمی‌شود و بخشی از seeds اصلی نیست.
 */

$env = require __DIR__ . '/../config/env.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    (string) $env['DB_HOST'],
    (int) $env['DB_PORT'],
    (string) $env['DB_DATABASE']
);

$pdo = new PDO($dsn, (string) $env['DB_USERNAME'], (string) $env['DB_PASSWORD'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$operators = $pdo->query('SELECT id, slug FROM operators ORDER BY sort_order ASC, id ASC')->fetchAll();
$operatorIds = array_map(static fn (array $row): int => (int) $row['id'], $operators);
$op1 = $operatorIds[0] ?? 1;
$op2 = $operatorIds[1] ?? $op1;
$op3 = $operatorIds[2] ?? $op1;

$adminHash = password_hash('Admin@12345', PASSWORD_BCRYPT, ['cost' => 12]);
$pdo->prepare("UPDATE users SET password_hash = ?, status = 'active' WHERE username = 'admin'")->execute([$adminHash]);
if ((int) $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn() === 0) {
    $pdo->prepare("INSERT INTO users (username, password_hash, role, status, full_name, phone, created_at, updated_at)
                   VALUES ('admin', ?, 'super_admin', 'active', 'مدیر کل', '09120000000', NOW(), NOW())")
        ->execute([$adminHash]);
}

$resellerHash = password_hash('Reseller@12345', PASSWORD_BCRYPT, ['cost' => 12]);
$pdo->prepare(
    "INSERT INTO users (username, password_hash, role, status, full_name, phone, email, created_at, updated_at)
     VALUES ('reseller1', ?, 'reseller', 'active', 'نماینده آزمایشی', '09121112233', 'reseller@test.local', NOW(), NOW())
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'reseller', status = 'active'"
)->execute([$resellerHash]);

$resellerId = (int) $pdo->query("SELECT id FROM users WHERE username = 'reseller1'")->fetchColumn();

$pdo->prepare(
    "INSERT INTO reseller_profiles (user_id, display_name, contact, commission_rate)
     VALUES (?, 'فروشگاه آزمایشی', '@reseller_test', 7.5)
     ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), contact = VALUES(contact), commission_rate = VALUES(commission_rate)"
)->execute([$resellerId]);

$numbers = [
    ['09121234567', $op1, 1_200_000, null, '1991-08-03', 1370, 5, 12, 'شماره آزمایشی با تاریخ تولد'],
    ['09125558888', $op1, 4_200_000, $resellerId, null, null, null, null, 'شماره رند چهار رقم تکراری'],
    ['09351234987', $op2, 850_000, null, null, null, null, null, 'شماره اقتصادی ایرانسل'],
    ['09201234567', $op3, 950_000, null, null, null, null, null, 'شماره رایتل'],
    ['09901234000', $op1, 1_500_000, null, null, null, null, null, 'شماره رند همراه اول'],
    ['09127770001', $op1, 1_100_000, $resellerId, null, null, null, null, 'موجودی نماینده'],
    ['09127770002', $op1, 1_300_000, $resellerId, null, null, null, null, 'موجودی نماینده'],
];

$insertNumber = $pdo->prepare(
    'INSERT INTO phone_numbers
        (operator_id, number, price, status, reseller_id, birth_date, birth_jy, birth_jm, birth_jd, description, views, created_at, updated_at)
     VALUES (?, ?, ?, "available", ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
     ON DUPLICATE KEY UPDATE price = VALUES(price), reseller_id = VALUES(reseller_id)'
);

foreach ($numbers as [$number, $operator, $price, $reseller, $birthDate, $jy, $jm, $jd, $description]) {
    $insertNumber->execute([
        $operator,
        $number,
        $price,
        $reseller,
        $birthDate,
        $jy,
        $jm,
        $jd,
        $description,
        random_int(10, 900),
    ]);
}

// یک شماره تازه برای خرید تست HTTP تا اجرای مجدد تست ممکن باشد.
do {
    $purchaseNumber = '0912' . str_pad((string) random_int(0, 9_999_999), 7, '0', STR_PAD_LEFT);
    $exists = (int) $pdo->query("SELECT COUNT(*) FROM phone_numbers WHERE number = '" . $purchaseNumber . "'")->fetchColumn();
} while ($exists > 0);

$insertNumber->execute([
    $op1,
    $purchaseNumber,
    4_200_000,
    $resellerId,
    null,
    null,
    null,
    null,
    'خرید تست HTTP',
    random_int(10, 900),
]);

echo 'purchase_number=' . $purchaseNumber . "\n";

$modems = [
    ['TP-Link', 'Archer MR600', 2_500_000, 5, null, 'مودم LTE دو باند با پشتیبانی سیم‌کارت'],
    ['Huawei', 'B535-232', 3_200_000, 2, $resellerId, 'مودم خانگی 4G با پورت LAN'],
    ['D-Link', 'DWR-953', 1_900_000, 0, null, 'مودم ناموجود برای تست مخفی شدن'],
];

$insertModem = $pdo->prepare(
    'INSERT INTO modems (brand, model, price, stock, image_path, description, reseller_id, status, created_at, updated_at)
     VALUES (?, ?, ?, ?, NULL, ?, ?, "active", NOW(), NOW())
     ON DUPLICATE KEY UPDATE price = VALUES(price), stock = VALUES(stock)'
);

foreach ($modems as [$brand, $model, $price, $stock, $reseller, $description]) {
    $insertModem->execute([$brand, $model, $price, $stock, $description, $reseller]);
}

echo "fixtures_ok\n";
echo 'reseller_id=' . $resellerId . "\n";
echo 'numbers=' . $pdo->query('SELECT COUNT(*) FROM phone_numbers')->fetchColumn() . "\n";
echo 'modems=' . $pdo->query('SELECT COUNT(*) FROM modems')->fetchColumn() . "\n";
echo 'admin_login=admin/Admin@12345' . "\n";
echo 'reseller_login=reseller1/Reseller@12345' . "\n";
