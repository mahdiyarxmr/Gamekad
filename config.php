<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('گیم‌کاد به PHP 8.1 یا بالاتر نیاز دارد. در XAMPP نسخه PHP را از پنل کنترل عوض کن (8.1 / 8.2 / 8.3).');
}

define('ROOT', str_replace('\\', '/', __DIR__));
define('SITE_NAME', 'GAMEKAD');
define('SITE_NAME_FA', 'گیم‌کاد');
define('SITE_TAGLINE', 'رکاد');
define('DB_PATH', ROOT . '/data/gamekad.sqlite');
define('UPLOAD_ROOT', ROOT . '/uploads');
define('DEPOSIT_RATIO', 0.5);

if (is_file(ROOT . '/config.local.php')) {
    require ROOT . '/config.local.php';
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', detect_base_path());
}

date_default_timezone_set('Asia/Tehran');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $cookiePath = BASE_PATH === '' ? '/' : BASE_PATH . '/';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function detect_base_path(): string
{
    $root = str_replace('\\', '/', __DIR__);
    $doc = str_replace('\\', '/', rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));

    if ($doc !== '') {
        $ok = DIRECTORY_SEPARATOR === '\\'
            ? (stripos($root, $doc) === 0)
            : str_starts_with($root, $doc);
        if ($ok) {
            $base = substr($root, strlen($doc));
            $base = '/' . trim(str_replace('\\', '/', $base), '/');
            return $base === '/' ? '' : rtrim($base, '/');
        }
    }

    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = str_replace('\\', '/', dirname($script));
    if (strcasecmp(basename($dir), 'admin') === 0) {
        $dir = dirname($dir);
    }
    if ($dir === '/' || $dir === '.' || $dir === '\\' || $dir === '') {
        return '';
    }
    return rtrim($dir, '/');
}
