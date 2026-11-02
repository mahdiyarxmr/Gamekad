<?php
declare(strict_types=1);

require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';

db_init();

$uri = request_path();
$parts = $uri === '' ? [] : explode('/', $uri);
$route = $parts[0] ?? '';
$param = $parts[1] ?? null;
$param2 = $parts[2] ?? null;

if ($route === 'logout') {
    auth_logout();
    flash('x', 'خارج شدی.', 'ok');
    redirect('');
}

if ($route === 'admin') {
    require ROOT . '/admin/index.php';
    exit;
}

$map = [
    '' => 'home',
    'leaderboard' => 'leaderboard',
    'players' => 'players',
    'player' => 'player',
    'reserve' => 'reserve',
    'events' => 'reserve',
    'blog' => 'blog',
    'login' => 'login',
    'register' => 'register',
    'account' => 'account',
    'pay' => 'pay',
    'wallet' => 'wallet',
    'roles' => 'guide',
    'guide' => 'guide',
    'godgame' => 'godgame',
    'egames' => 'egames',
];

$page = $map[$route] ?? null;
if (!$page) {
    http_response_code(404);
    $title = 'پیدا نشد';
    require ROOT . '/includes/header.php';
    echo '<section class="page-hero"><div class="wrap"><h1>۴۰۴</h1><p class="sub">این صفحه در کافه نیست.</p><a class="btn" href="'.e(url('')).'">بازگشت</a></div></section>';
    require ROOT . '/includes/footer.php';
    exit;
}

require ROOT . '/pages/' . $page . '.php';
