<?php
$u = current_user();
$title = $title ?? SITE_NAME_FA;
$desc = $desc ?? 'رکاد — هنرستان استارتاپی و میزهای بازی گیم‌کاد.';
$meHref = $u ? url('account') : url('login');
$meImg = $u ? media($u['avatar']) : asset('img/icons/icon-192.png');
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title><?= e($title) ?> | <?= e(SITE_NAME) ?></title>
  <meta name="description" content="<?= e($desc) ?>">
  <meta name="theme-color" content="#21295a">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="رکاد">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="format-detection" content="telephone=no">
  <base href="<?= e(url('')) ?>">
  <link rel="manifest" href="<?= e(url('manifest.webmanifest')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>?v=d23">
  <link rel="icon" href="<?= e(asset('img/icons/icon-192.png')) ?>">
  <link rel="apple-touch-icon" href="<?= e(asset('img/icons/apple-touch.png')) ?>">
</head>
<body class="app-shell">
<header class="nav">
  <div class="nav-inner">
    <a class="brand" href="<?= e(url('')) ?>">
      <img src="<?= e(asset('img/logo-emblem.png')) ?>" alt="GAMEKAD">
      <span>
        <b class="en">GAMEKAD</b>
        <small><?= e(SITE_TAGLINE) ?></small>
      </span>
    </a>
    <ul class="nav-links">
      <li><a class="<?= active_nav('') ?>" href="<?= e(url('')) ?>">خانه</a></li>
      <li><a class="<?= active_nav('leaderboard') ?>" href="<?= e(url('leaderboard')) ?>">لیدربورد</a></li>
      <li><a class="<?= active_nav('events') ?>" href="<?= e(url('events')) ?>">رویدادها</a></li>
      <li class="nav-hide"><a class="<?= active_nav('egames') ?>" href="<?= e(url('egames')) ?>">الکترونیک</a></li>
      <li class="nav-hide"><a class="<?= active_nav('players') ?>" href="<?= e(url('players')) ?>">بازیکنان</a></li>
      <li><a class="<?= active_nav('guide') ?> <?= active_nav('roles') ?>" href="<?= e(url('guide')) ?>">آموزش</a></li>
      <li><a class="<?= active_nav('blog') ?>" href="<?= e(url('blog')) ?>">آموزش نوشته</a></li>
    </ul>
    <div class="nav-cta">
      <?php if ($u): ?>
        <a class="btn btn-ghost btn-sm desk-only" href="<?= e(url('account')) ?>"><?= e($u['display_name']) ?></a>
        <?php if (can_staff()): ?>
          <a class="btn btn-sm desk-only" href="<?= e(url(staff_home())) ?>"><?= e(staff_label()) ?></a>
          <a class="nav-panel" href="<?= e(url(staff_home())) ?>" aria-label="<?= e(staff_label()) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 5h16v2H4zm0 6h16v2H4zm0 6h16v2H4z"/></svg>
          </a>
        <?php endif; ?>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm desk-only" href="<?= e(url('login')) ?>">ورود</a>
        <a class="btn btn-solid btn-sm desk-only" href="<?= e(url('register')) ?>">ساخت پروفایل</a>
      <?php endif; ?>
      <a class="nav-me" href="<?= e($meHref) ?>" aria-label="حساب">
        <img src="<?= e($meImg) ?>" alt="">
      </a>
    </div>
  </div>
</header>
<main>
<?php notify_shell(flash()); ?>
