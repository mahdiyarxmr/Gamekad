<?php
declare(strict_types=1);

$sub = $param ?: 'dashboard';
if ($sub === 'login') {
    if (is_administrator() || is_admin()) redirect('admin');
    if (is_godgame()) redirect('admin/godgame');
    if (is_post()) {
        csrf_check();
        if (auth_login(trim((string)post('user')), (string)post('password'))) {
            if (is_godgame()) {
                flash('x','وارد پنل خدای بازی شدی.','ok');
                redirect('admin/godgame');
            }
            if (is_admin()) {
                flash('x','وارد پنل شدی.','ok');
                redirect('admin');
            }
        }
        auth_logout();
        flash('x','فقط ادمین یا گادگیم.','err');
        redirect('admin/login');
    }
    $title = 'ورود ادمین';
    require ROOT . '/includes/header.php';
    echo '<section class="page-hero"><div class="wrap"><div class="kicker">ADMIN</div><h1>ورود میزبان</h1></div></section>';
    echo '<section class="section" style="padding-top:0"><div class="wrap"><div class="card auth-box"><form class="form" method="post">';
    echo csrf_field();
    echo '<label>نام کاربری</label><input name="user" placeholder="مثلاً admin" autocomplete="username" dir="ltr" class="ltr-input">';
    echo '<label>رمز عبور</label><input name="password" type="password" placeholder="رمز عبور خود را وارد کنید" autocomplete="current-password">';
    echo '<button class="btn btn-solid" type="submit">ورود</button>';
    echo '</form></div></div></section>';
    require ROOT . '/includes/footer.php';
    exit;
}

if (!can_staff()) {
    flash('x','برای پنل وارد شو.','warn');
    redirect('admin/login');
}

if (is_godgame() && !is_admin()) {
    $allowed = ['godgame', 'badges', 'leaderboard', 'stats', 'recent'];
    $home = 'admin/godgame';
} elseif (is_administrator()) {
    $allowed = ['dashboard','godgame','users','categories','games','layout','leaderboard','sessions','reservations','blog','badges','stats','recent','settings','gateway'];
    $home = 'admin';
} elseif (is_admin()) {
    $allowed = ['dashboard','godgame','users','categories','games','layout','leaderboard','sessions','reservations','blog','badges','stats','recent','settings'];
    $home = 'admin';
} else {
    $allowed = [];
    $home = 'admin/login';
}
if (!in_array($sub, $allowed, true)) {
    if ($sub === 'gateway') {
        flash('x', 'تنظیمات درگاه فقط برای Administrator است.', 'err');
    } elseif ($sub === 'godgame') {
        flash('x', 'تخصیص نقش فقط برای گادگیم است.', 'err');
    }
    redirect($home);
}

$fl = flash();
ob_start();
require ROOT . '/admin/' . $sub . '.php';
$content = ob_get_clean();
$adminTitle = $adminTitle ?? 'پنل';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#21295a">
  <title><?= e($adminTitle) ?> | رکاد</title>
  <base href="<?= e(url('')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>?v=d23">
  <link rel="icon" href="<?= e(asset('img/icons/icon-192.png')) ?>">
</head>
<body class="admin-body">
<button class="admin-scrim" type="button" data-admin-scrim aria-label="بستن منو"></button>
<aside class="aside" id="adminAside">
  <a class="brand" href="<?= e(url($home)) ?>">
    <img src="<?= e(asset('img/logo-emblem.png')) ?>" alt="">
    <span><b class="en">GAMEKAD</b><small><?= is_godgame() && !is_admin() ? 'خدای بازی' : (is_administrator() ? 'Administrator' : 'پنل میزبان') ?></small></span>
  </a>
  <?php
    $asideGroup = static function (string $title, array $links, string $sub): void {
        $open = isset($links[$sub]);
        echo '<div class="aside-g' . ($open ? ' is-open' : '') . '">';
        echo '<button type="button" class="aside-g-h" aria-expanded="' . ($open ? 'true' : 'false') . '">';
        echo '<span>' . e($title) . '</span><i class="aside-caret" aria-hidden="true"></i></button>';
        echo '<div class="aside-g-b">';
        foreach ($links as $k => $lab) {
            $on = $sub === $k ? 'is-active' : '';
            echo '<a class="' . $on . '" href="' . e(url('admin/' . $k)) . '">' . e($lab) . '</a>';
        }
        echo '</div></div>';
    };
    if (is_godgame() && !is_admin()) {
        $asideGroup('خدای بازی', ['godgame'=>'میز شب', 'recent'=>'بازی‌های اخیر', 'badges'=>'بج‌ها', 'leaderboard'=>'امتیاز'], $sub);
    } else {
        $asideGroup('میزبانی', ['dashboard'=>'داشبورد', 'layout'=>'چینش خانه'], $sub);
        $asideGroup('رویدادها', ['sessions'=>'نشست‌ها', 'reservations'=>'رزرو و پرداخت'], $sub);
        $asideGroup('بازی‌ها', ['games'=>'الکترونیک', 'categories'=>'دسته‌ها و نقش‌ها'], $sub);
        $asideGroup('خدای بازی', ['godgame'=>'میز شب', 'recent'=>'بازی‌های اخیر', 'badges'=>'بج‌ها', 'leaderboard'=>'امتیاز'], $sub);
        $asideGroup('محتوا', ['blog'=>'آموزش نوشته'], $sub);
        $asideGroup('افراد', ['users'=>'کاربران'], $sub);
        $set = ['settings'=>'تنظیمات رکاد'];
        if (is_administrator()) {
            $set['gateway'] = 'درگاه پرداخت';
        }
        $asideGroup('تنظیمات', $set, $sub);
    }
  ?>
  <a href="<?= e(url('')) ?>">مشاهده سایت</a>
  <a href="<?= e(url('logout')) ?>">خروج</a>
</aside>
<div class="admin-main">
  <div class="admin-top">
    <button class="admin-burger" type="button" data-admin-nav aria-label="منوی مدیریت">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"/></svg>
    </button>
    <h1><?= e($adminTitle) ?></h1>
    <span class="admin-who"><?= e(current_user()['display_name']) ?></span>
  </div>
  <?php notify_shell($fl); ?>
  <?= $content ?>
</div>
<script src="<?= e(asset('js/main.js')) ?>?v=d15"></script>
</body>
</html>
