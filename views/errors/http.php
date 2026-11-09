<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= isset($title) ? htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') : 'خطا' ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: radial-gradient(circle at 20% 20%, #134e4a 0%, #0f172a 55%, #020617 100%);
            color: #e2e8f0; font-family: Vazirmatn, Tahoma, system-ui, sans-serif; padding: 24px;
        }
        .card {
            width: 100%; max-width: 560px; background: rgba(15, 23, 42, .82); border: 1px solid #334155;
            border-radius: 24px; padding: 32px; text-align: center; box-shadow: 0 30px 80px rgba(0,0,0,.35);
        }
        .code { font-size: 56px; font-weight: 800; color: #5eead4; line-height: 1; letter-spacing: .05em; }
        h1 { font-size: 22px; margin: 16px 0 8px; }
        p { color: #94a3b8; line-height: 1.9; font-size: 14px; margin: 0 0 24px; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        a {
            text-decoration: none; padding: 11px 20px; border-radius: 12px; font-size: 14px; font-weight: 700;
            background: #0d9488; color: white;
        }
        a.secondary { background: transparent; border: 1px solid #475569; color: #cbd5e1; }
        .brand { margin-top: 26px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
<div class="card">
    <div class="code"><?= fa_num((int) ($status ?? 500)) ?></div>
    <h1><?= e($title ?? 'خطا') ?></h1>
    <p><?= e($message ?? 'خطای غیرمنتظره‌ای رخ داد.') ?></p>
    <div class="actions">
        <a href="/">بازگشت به فروشگاه</a>
        <a class="secondary" href="/contact">تماس با پشتیبانی</a>
    </div>
    <div class="brand">آوال سیم — خرید مطمئن سیم‌کارت و مودم</div>
</div>
</body>
</html>
