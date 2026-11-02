<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';
header('Content-Type: text/html; charset=utf-8');
$ok = static function (bool $v): string {
    return $v ? '<b style="color:#3d6b4f">OK</b>' : '<b style="color:#8b2942">FAIL</b>';
};
$css = asset('css/style.css');
$hero = asset('img/hero-cafe.jpg');
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>بررسی گیم‌کاد</title>
  <base href="<?= htmlspecialchars(url('')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
</head>
<body style="padding:40px;max-width:720px;margin:auto">
  <h1>بررسی نصب GAMEKAD</h1>
  <p>اگر دکمه پایین طلایی/قهوه‌ای است، ظاهر سایت درست لود شده.</p>
  <p><a class="btn btn-solid" href="<?= htmlspecialchars(url('')) ?>">بازگشت به سایت</a></p>
  <div class="card" style="padding:18px;margin-top:20px;line-height:2">
    PHP: <?= htmlspecialchars(PHP_VERSION) ?> <?= $ok(PHP_VERSION_ID >= 80100) ?><br>
    PDO SQLite: <?= $ok(extension_loaded('pdo_sqlite')) ?><br>
    GD: <?= $ok(extension_loaded('gd')) ?><br>
    mbstring: <?= $ok(extension_loaded('mbstring')) ?><br>
    BASE_PATH: <code><?= htmlspecialchars(BASE_PATH === '' ? '(ریشه دامنه)' : BASE_PATH) ?></code><br>
    مسیر CSS: <code><?= htmlspecialchars($css) ?></code><br>
    پوشه data قابل نوشتن: <?= $ok(is_writable(__DIR__ . '/data')) ?><br>
    پوشه uploads قابل نوشتن: <?= $ok(is_writable(__DIR__ . '/uploads')) ?><br>
  </div>
  <p style="margin-top:16px">عکس تست:</p>
  <img src="<?= htmlspecialchars($hero) ?>" alt="" style="width:100%;max-width:480px;border:1px solid #c9a227;border-radius:12px">
  <p style="margin-top:18px;color:#8a7a6a;font-size:13px">بعد از اینکه مطمئن شدی همه‌چیز OK است، فایل <code>check.php</code> را پاک کن.</p>
</body>
</html>
