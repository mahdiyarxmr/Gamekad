<?php
declare(strict_types=1);

function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sticker_heading(string $text, string $tag = 'h2', string $class = '', array $accent = []): void
{
    $words = preg_split('/\s+/u', trim($text)) ?: [];
    $rots = [-3, -1.5, 0, 1.5, 2.5, 3, -2];
    echo '<' . $tag . ' class="stick-h ' . e($class) . '">';
    echo '<span class="sr-only">' . e($text) . '</span>';
    echo '<span class="stick-words" aria-hidden="true">';
    foreach ($words as $i => $w) {
        $r = $rots[$i % count($rots)];
        $extra = '';
        if (in_array($w, $accent, true)) {
            $extra = ' is-accent';
        }
        echo '<span class="stick-w' . $extra . '" style="--r:' . $r . 'deg">' . e($w) . '</span>';
    }
    echo '</span></' . $tag . '>';
}

function url(string $path = ''): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function media(?string $path, string $fallback = 'img/logo-emblem.png'): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return asset($fallback);
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return url(ltrim($path, '/'));
}

function html_media(string $html): string
{
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    if ($base === '') {
        return $html;
    }
    return (string)preg_replace('#(src|href)="/(assets|uploads)/#i', '$1="' . $base . '/$2/', $html);
}

function redirect(string $path): never
{
    if (!preg_match('#^https?://#i', $path)) {
        $base = defined('BASE_PATH') ? BASE_PATH : '';
        if (!($base !== '' && ($path === $base || str_starts_with($path, $base . '/')))) {
            $path = url($path);
        }
    }
    header('Location: ' . $path);
    exit;
}

function request_path(): string
{
    if (!empty($_GET['r'])) {
        return trim((string)$_GET['r'], '/');
    }
    $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
    $uri = rawurldecode(str_replace('\\', '/', $uri));
    $base = defined('BASE_PATH') ? BASE_PATH : '';
    if ($base !== '' && ($uri === $base || str_starts_with($uri, $base . '/'))) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }
    $uri = preg_replace('#/index\.php#i', '', $uri) ?? $uri;
    return trim($uri, '/');
}

function flash(?string $key = null, ?string $msg = null, string $type = 'ok'): ?array
{
    if ($key && $msg !== null) {
        $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
        return null;
    }
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function pass_eye_btn(): string
{
    return '<button type="button" class="pass-eye" data-pass-eye aria-label="نمایش رمز">'
        . '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true">'
        . '<g class="eye-shut-g">'
        . '<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M4 13.5c2.4-1.8 4.9-2.7 8-2.7s5.6.9 8 2.7"/>'
        . '<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8.2 15.4c.3.5.6.8 1 .8M12 16.2v.8M15.8 15.4c-.3.5-.6.8-1 .8"/>'
        . '</g>'
        . '<g class="eye-open-g">'
        . '<path fill="currentColor" d="M12 5.2c5.1 0 9 4.1 10.2 6.1C21 13.4 17.1 18 12 18S3 13.4 1.8 11.3C3 9.3 6.9 5.2 12 5.2zm0 3.1a3.7 3.7 0 1 0 0 7.4 3.7 3.7 0 0 0 0-7.4zm0 2a1.7 1.7 0 1 1 0 3.4 1.7 1.7 0 0 1 0-3.4z"/>'
        . '</g>'
        . '</svg></button>';
}

function csrf_check(): void
{
    $t = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(403);
        exit('CSRF');
    }
}

function user_find_by_name(string $name): ?array
{
    $name = trim($name);
    if ($name === '') {
        return null;
    }
    $u = q('SELECT * FROM users WHERE display_name = ? OR username = ? LIMIT 1', [$name, $name])->fetch();
    if ($u) {
        return $u;
    }
    $u = q('SELECT * FROM users WHERE display_name LIKE ? OR username LIKE ? LIMIT 1', [$name . '%', $name . '%'])->fetch();
    return $u ?: null;
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fa_num($n): string
{
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return str_replace($en, $fa, (string)$n);
}

function money(int $toman): string
{
    return fa_num(number_format($toman)) . ' تومان';
}

function gregorian_to_jalali(int $gy, int $gm, int $gd): array
{
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
        + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

function jalali_months(): array
{
    return [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',
            7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند'];
}

function jalali_weekdays(): array
{
    return ['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه'];
}

function jdate_format(?string $datetime, string $fmt = 'full'): string
{
    if (!$datetime) return '—';
    $ts = strtotime($datetime);
    if ($ts === false) return '—';
    [$jy, $jm, $jd] = gregorian_to_jalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    $months = jalali_months();
    $w = jalali_weekdays()[(int)date('w', $ts)];
    $time = date('H:i', $ts);
    if ($fmt === 'date') {
        return fa_num($jd) . ' ' . $months[$jm] . ' ' . fa_num($jy);
    }
    if ($fmt === 'short') {
        return fa_num(sprintf('%04d/%02d/%02d', $jy, $jm, $jd));
    }
    return $w . ' ' . fa_num($jd) . ' ' . $months[$jm] . ' ' . fa_num($jy) . ' — ' . fa_num($time);
}

function slugify(string $s): string
{
    $s = trim($s);
    $s = preg_replace('/\s+/u', '-', $s);
    $s = preg_replace('/[^\p{L}\p{N}\-_]+/u', '', $s);
    return trim($s, '-') ?: ('p-' . bin2hex(random_bytes(3)));
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function post(string $k, $default = '')
{
    return $_POST[$k] ?? $default;
}

function getv(string $k, $default = '')
{
    return $_GET[$k] ?? $default;
}

function save_upload(string $field, string $subdir, array $allowed = ['jpg','jpeg','png','webp','gif']): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if (($f['error'] ?? 1) !== UPLOAD_ERR_OK) return null;
    if (($f['size'] ?? 0) > 8 * 1024 * 1024) return null;
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) return null;
    $dir = UPLOAD_ROOT . '/' . trim($subdir, '/');
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $name = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $dest)) return null;
    return '/uploads/' . trim($subdir, '/') . '/' . $name;
}

function hex_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

function make_avatar(string $username, string $letter, string $bgHex): string
{
    $dir = UPLOAD_ROOT . '/avatars';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $safe = preg_replace('/[^A-Za-z0-9._-]/', '', $username) ?: ('u' . bin2hex(random_bytes(3)));
    if (function_exists('imagecreatetruecolor')) {
        $rel = '/uploads/avatars/' . $safe . '.png';
        try {
            generate_avatar(ROOT . $rel, $letter, $bgHex);
            return $rel;
        } catch (Throwable $e) {
            // بدون GD یا فونت خراب → SVG
        }
    }
    $rel = '/uploads/avatars/' . $safe . '.svg';
    generate_avatar_svg(ROOT . $rel, $letter, $bgHex);
    return $rel;
}

function generate_avatar_svg(string $path, string $letter, string $bgHex): void
{
    $letter = htmlspecialchars(mb_substr($letter, 0, 1), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $bgHex = preg_match('/^#?[0-9A-Fa-f]{3,8}$/', $bgHex) ? $bgHex : '#2a1810';
    if ($bgHex[0] !== '#') $bgHex = '#' . $bgHex;
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 480 480">'
        . '<circle cx="240" cy="240" r="234" fill="#c9a227"/>'
        . '<circle cx="240" cy="240" r="220" fill="#120c08"/>'
        . '<circle cx="240" cy="240" r="208" fill="' . $bgHex . '"/>'
        . '<circle cx="240" cy="240" r="196" fill="none" stroke="#e8c547" stroke-width="3"/>'
        . '<text x="240" y="292" text-anchor="middle" font-size="168" font-family="Tahoma, Vazirmatn, sans-serif" fill="#f3e6d4">' . $letter . '</text>'
        . '</svg>';
    file_put_contents($path, $svg);
}

function generate_avatar(string $path, string $letter, string $bgHex): void
{
    if (!function_exists('imagecreatetruecolor')) {
        $svg = preg_replace('/\.(png|jpe?g|webp)$/i', '.svg', $path) ?: ($path . '.svg');
        generate_avatar_svg($svg, $letter, $bgHex);
        return;
    }
    $s = 480;
    $im = imagecreatetruecolor($s, $s);
    imagesavealpha($im, true);
    $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
    imagefill($im, 0, 0, $trans);

    [$r, $g, $b] = hex_rgb($bgHex);
    $bg = imagecolorallocate($im, $r, $g, $b);
    $gold = imagecolorallocate($im, 201, 162, 39);
    $gold2 = imagecolorallocate($im, 232, 197, 71);
    $dark = imagecolorallocate($im, 18, 10, 6);
    $cream = imagecolorallocate($im, 243, 230, 212);

    $cx = $s / 2; $cy = $s / 2; $rad = 220;
    imagefilledellipse($im, (int)$cx, (int)$cy, $rad * 2 + 28, $rad * 2 + 28, $gold);
    imagefilledellipse($im, (int)$cx, (int)$cy, $rad * 2 + 12, $rad * 2 + 12, $dark);
    imagefilledellipse($im, (int)$cx, (int)$cy, $rad * 2, $rad * 2, $bg);
    imageellipse($im, (int)$cx, (int)$cy, $rad * 2 - 18, $rad * 2 - 18, $gold2);

    $font = ROOT . '/assets/fonts/Vazirmatn-ExtraBold.ttf';
    if (!is_file($font)) $font = ROOT . '/assets/fonts/Vazirmatn-Bold.ttf';
    $letter = mb_substr($letter, 0, 1);
    if (is_file($font) && function_exists('imagettftext')) {
        $size = 150;
        $bbox = imagettfbbox($size, 0, $font, $letter);
        $tw = $bbox[2] - $bbox[0];
        $th = $bbox[1] - $bbox[7];
        $x = (int)($cx - $tw / 2 - $bbox[0]);
        $y = (int)($cy + $th / 2 - 8);
        imagettftext($im, $size, 0, $x, $y, $cream, $font, $letter);
    }
    imagepng($im, $path, 7);
    imagedestroy($im);
}

function role_book(): array
{
    return [
        'شهروند' => ['symbol' => '👤', 'desc' => 'نقش ساده‌ی شهر. شب کاری نداری. روز با حرف، لحن و رأی مافیا را پیدا کن. اگر مافیا تمام شود، برنده‌ای.'],
        'مافیا' => ['symbol' => '🃏', 'desc' => 'شب با هم‌تیمی‌ها یک نفر را حذف می‌کنید. روز باید شهروند به نظر برسی. هدف: رساندن شهر به تعداد مافیا یا کمتر.'],
        'پدرخوانده' => ['symbol' => '👑', 'desc' => 'رئیس مافیا. استعلام بازپرس روی تو معمولاً شهروند نشان می‌دهد. شب تصمیم نهایی حذف اغلب با توست.'],
        'دکتر' => ['symbol' => '✚', 'desc' => 'هر شب یک نفر را نجات می‌دهی. ستون بقای شهر. سیو را خرج آدم درست کن؛ گاهی هم خودت را.'],
        'بازپرس' => ['symbol' => '🔍', 'desc' => 'هر شب استعلام می‌گیری: مافیا هست یا نه. پدرخوانده را معمولاً نمی‌بینی. اطلاعات را خرج کن، خودت را لو نده.'],
        'اسنایپر' => ['symbol' => '🎯', 'desc' => 'یک تیر در طول بازی داری. شلیک به مافیا نجات شهر است؛ شلیک به شهروند فاجعه. صبر کن تا مطمئن شوی.'],
        'روانشناس' => ['symbol' => '👁', 'desc' => 'شب یک نفر را می‌خوانی. می‌فهمی نقشش خاص/مستقل است یا نه. هنر تو دیدن دروغ از روی سکوت است.'],
        'ساقی' => ['symbol' => '🍷', 'desc' => 'شب یک نفر را مست می‌کنی؛ آن شب نقشش کار نمی‌کند. هم می‌توانی مافیا را بخوابانی، هم دکتر شهر را.'],
        'ناتاشا' => ['symbol' => '♠', 'desc' => 'وابسته به مافیا. شب یک نفر را ساکت می‌کند تا فردا حرف نزند. اطلاعات شهر را خفه کن.'],
        'شهردار' => ['symbol' => '🏛', 'desc' => 'روز می‌توانی کارت را رو کنی و رأی‌ات دو برابر شود. یک‌بار، در لحظه‌ی درست. رو کردن زود یعنی هدف شدن.'],
        'روستایی' => ['symbol' => '🌾', 'desc' => 'نسخه روستایی شهروند. شب نقشی نداری؛ روز با رأی گرگ را پیدا کن.'],
        'گرگ' => ['symbol' => '🐺', 'desc' => 'شب شکار می‌کنی. روز بین روستایی‌ها قایم شو. اگر روستا را به تعداد گرگ برسانی، برد با توست.'],
        'پیشگو' => ['symbol' => '🌙', 'desc' => 'شب هویت یک نفر را می‌بینی. اطلاعاتت طلاست؛ اگر زود لو بروی، اولین قربانی گرگی.'],
        'جادوگر' => ['symbol' => '✨', 'desc' => 'معجون نجات و معجون مرگ. هر کدام یک‌بار. انتخاب غلط، کل روستا را می‌سوزاند.'],
        'شکارچی' => ['symbol' => '🏹', 'desc' => 'اگر خورده شوی می‌توانی یک نفر را با خودت ببری. تیر آخرت باید گرگ باشد، نه همسایه.'],
    ];
}

function scenario_book(): array
{
    return [
        'کلاسیک' => [
            'slug' => 'classic',
            'cover' => '/assets/img/blog-mafia-classic.jpg',
            'howto' => '<h3>چطور بازی می‌شود</h3>
<p>مافیا کلاسیک روی میز ۱۲ نفره گیم‌کاد با یک گرداننده بازی می‌شود. نقش‌ها پخش می‌شود، چشم‌ها بسته، و از آن لحظه هر نگاه یک مدرک است.</p>
<h3>ریتم شب و روز</h3>
<ol>
<li>شب اول: مافیا بیدار می‌شود و یک هدف انتخاب می‌کند. دکتر نجات می‌دهد. بازپرس استعلام می‌گیرد.</li>
<li>صبح: اگر نجات نخورده باشد یک صندلی خالی است. بحث شروع می‌شود.</li>
<li>روز: حرف، اتهام، دفاع. در پایان رأی. کسی که بیشترین رأی را بگیرد از بازی خارج می‌شود.</li>
<li>بازی ادامه دارد تا یکی از دو شرط برد رخ دهد.</li>
</ol>
<h3>برد</h3>
<ul>
<li>شهر: همه مافیا حذف شوند.</li>
<li>مافیا: تعداد مافیا با شهر برابر شود یا از آن بیشتر.</li>
</ul>
<p>کارت نقش‌ها را بزن تا پشتش بچرخد و توانایی همان نقش را بخوانی.</p>',
        ],
        'آخرین حرکت' => [
            'slug' => 'last-move',
            'cover' => '/assets/img/blog-lastmove.jpg',
            'howto' => '<h3>تفاوت با کلاسیک</h3>
<p>حذف‌شده‌ها بی‌صدا نمی‌روند. هر نقش یک توانایی نهایی دارد که در لحظه مرگ شلیک می‌شود. بازی طولانی‌تر و بلوف گران‌تر است.</p>
<h3>زمان‌بندی</h3>
<ol>
<li>شب مثل کلاسیک پیش می‌رود.</li>
<li>وقتی کسی حذف می‌شود، قبل از خروج حرکتش را اعلام می‌کند (تیر اسنایپر، سکوت ناتاشا، نجات دکتر و …).</li>
<li>این حرکت می‌تواند کل میز را برگرداند؛ پس اطلاعات را برای آخر بازی نگه ندار.</li>
</ol>
<h3>توصیه میز گیم‌کاد</h3>
<p>بازپرس استعلام‌های شب اول را قسم نکند. مافیا شهروند پرحرف را زنده نگه ندارد. قهوه را قبل از شروع تمام کن.</p>',
        ],
        'مافیا ناتو' => [
            'slug' => 'nato',
            'cover' => '/assets/img/cat-mafia.jpg',
            'howto' => '<h3>هویت پنهان</h3>
<p>در ناتو نقش‌ها پیچیده‌ترند و ائتلاف وسط بازی شکل می‌گیرد. استعلام همیشه راست نیست و پدرخوانده لایهٔ محافظ مافیاست.</p>
<h3>نحوه بازی</h3>
<ol>
<li>نقش‌های کامل پخش می‌شود؛ بعضی نقش‌ها شب همدیگر را نمی‌بینند.</li>
<li>روز با اطلاعات ناقص جلو می‌رود. رأی عجولانه معمولاً به ضرر شهر است.</li>
<li>برد مثل کلاسیک است اما مسیرش پر از فریب است.</li>
</ol>
<p>این سناریو برای بازیکنانی است که حداقل چند دست کلاسیک بازی کرده‌اند.</p>',
        ],
        'گردن‌زنی' => [
            'slug' => 'execution',
            'cover' => '/assets/img/cat-mafia.jpg',
            'howto' => '<h3>سریع و بی‌رحم</h3>
<p>بحث طولانی ممنوع. هر روز بعد از یک دور کوتاه معرفی، مستقیم رأی گرفته می‌شود. زمان طلاست.</p>
<h3>قوانین میز</h3>
<ol>
<li>شب مثل کلاسیک.</li>
<li>روز: حداکثر یک یا دو جمله برای هر نفر، بعد رأی.</li>
<li>تساوی رأی یعنی هر دو در خطرند یا گرداننده دور دوم می‌گیرد.</li>
</ol>
<p>اینجا پرحرفی لو می‌دهد. کوتاه، تیز، بدون داستان اضافه.</p>',
        ],
        'خداحافظی' => [
            'slug' => 'farewell',
            'cover' => '/assets/img/blog-lastmove.jpg',
            'howto' => '<h3>یک جمله قبل از رفتن</h3>
<p>حذف‌شده‌ها قبل از خروج یک جمله می‌گویند؛ نه بیشتر. همین جمله می‌تواند شهر را نجات دهد یا مافیا را لو بدهد.</p>
<h3>نحوه بازی</h3>
<ol>
<li>قوانین شب مثل کلاسیک است.</li>
<li>پس از حذف، بازیکن حق یک جمله دارد. دروغ، حقیقت، یا نمایش.</li>
<li>بعد از جمله، دیگر حرف نمی‌زند.</li>
</ol>
<p>هنر این سناریو کنترل همان یک جمله‌است.</p>',
        ],
        'روستای مه' => [
            'slug' => 'mist-village',
            'cover' => '/assets/img/cat-werewolf.jpg',
            'howto' => '<h3>گرگینه کلاسیک</h3>
<p>روستایی‌ها نمی‌دانند گرگ کیست. شب گرگ شکار می‌کند، پیشگو می‌بیند، جادوگر معجون دارد، شکارچی تیر آخر.</p>
<h3>ریتم</h3>
<ol>
<li>شب: گرگ‌ها هدف، پیشگو استعلام، جادوگر انتخاب معجون.</li>
<li>روز: بحث و رأی برای دار زدن مظنون.</li>
<li>برد روستا: همه گرگ‌ها حذف شوند. برد گرگ: تعدادشان با روستا برابر شود.</li>
</ol>
<p>کارت نقش را بزن تا توانایی‌اش را پشت کارت بخوانی.</p>',
        ],
    ];
}

function render_role_cards(array $roles): void
{
    echo '<div class="role-grid">';
    foreach ($roles as $r) {
        $book = role_book()[$r['name']] ?? [];
        $desc = trim((string)($r['description'] ?? '')) ?: ($book['desc'] ?? 'برای این نقش هنوز توضیحی ثبت نشده.');
        $sym = trim((string)($r['symbol'] ?? '')) ?: ($book['symbol'] ?? '♠');
        $sideKey = (string)($r['side'] ?? 'citizen');
        $side = $sideKey === 'mafia' ? 'مافیا' : ($sideKey === 'indie' ? 'مستقل' : 'شهر');
        $sideClass = $sideKey === 'mafia' ? 'side-mafia' : ($sideKey === 'indie' ? 'side-indie' : 'side-town');
        echo '<div class="flip-scene">';
        echo '<article class="flip-card" data-flip tabindex="0" role="button" aria-pressed="false">';
        echo '<div class="flip-inner">';
        echo '<div class="flip-face flip-front" dir="rtl">';
        echo '<div class="role-sym">' . e($sym) . '</div>';
        echo '<div class="role-name">' . e($r['name']) . '</div>';
        echo '<div class="role-side ' . $sideClass . '">' . e($side) . '</div>';
        echo '<div class="role-hint">بزن تا بچرخه</div>';
        echo '</div>';
        echo '<div class="flip-face flip-back" dir="rtl">';
        echo '<div class="role-name">' . e($r['name']) . '</div>';
        echo '<div class="role-side ' . $sideClass . '">' . e($side) . '</div>';
        echo '<p class="role-desc">' . e($desc) . '</p>';
        echo '<div class="role-hint">بزن تا برگرده</div>';
        echo '</div>';
        echo '</div></article></div>';
    }
    echo '</div>';
}

function winrate(int $w, int $l): int
{
    $t = $w + $l;
    if ($t === 0) return 0;
    return (int)round(($w / $t) * 100);
}

function result_from_table(string $winner, ?string $roleSide): string
{
    if ($winner === 'self') {
        return 'win';
    }
    if ($winner === 'opponent' || $winner === '') {
        return 'loss';
    }
    $side = $roleSide ?: '';
    if ($side === '') {
        return 'loss';
    }
    return $side === $winner ? 'win' : 'loss';
}

function game_record_for(int $uid, int $cid, int $storedW = 0, int $storedL = 0): array
{
    if ($uid < 1 || $cid < 1) {
        return ['wins' => $storedW, 'losses' => $storedL];
    }
    $n = (int)q('SELECT COUNT(*) FROM player_stats WHERE user_id=? AND category_id=?', [$uid, $cid])->fetchColumn();
    if ($n < 1) {
        return ['wins' => $storedW, 'losses' => $storedL];
    }
    $row = q("SELECT SUM(CASE WHEN result='win' THEN 1 ELSE 0 END) AS w,
                     SUM(CASE WHEN result='loss' THEN 1 ELSE 0 END) AS l
              FROM player_stats WHERE user_id=? AND category_id=?", [$uid, $cid])->fetch();
    return ['wins' => (int)($row['w'] ?? 0), 'losses' => (int)($row['l'] ?? 0)];
}

function lb_with_records(array $rows, int $cid): array
{
    foreach ($rows as &$r) {
        $rec = game_record_for((int)($r['user_id'] ?? 0), $cid, (int)($r['wins'] ?? 0), (int)($r['losses'] ?? 0));
        $r['wins'] = $rec['wins'];
        $r['losses'] = $rec['losses'];
    }
    unset($r);
    return $rows;
}

function lb_bump(int $cid, int $uid, int $points, string $result): void
{
    if ($uid < 1 || $cid < 1) {
        return;
    }
    $w = $result === 'win' ? 1 : 0;
    $l = $result === 'loss' ? 1 : 0;
    q('INSERT INTO leaderboard (category_id,user_id,points,wins,losses) VALUES (?,?,?,?,?)
       ON CONFLICT(category_id,user_id) DO UPDATE SET
         points = leaderboard.points + excluded.points,
         wins = leaderboard.wins + excluded.wins,
         losses = leaderboard.losses + excluded.losses',
        [$cid, $uid, $points, $w, $l]);
}

function lb_unbump(int $cid, int $uid, int $points, string $result): void
{
    $w = $result === 'win' ? -1 : 0;
    $l = $result === 'loss' ? -1 : 0;
    q('UPDATE leaderboard SET points=MAX(0,points-?), wins=MAX(0,wins+?), losses=MAX(0,losses+?) WHERE category_id=? AND user_id=?',
        [$points, $w, $l, $cid, $uid]);
}

function lb_add_points(int $cid, int $uid, int $delta): void
{
    if ($uid < 1 || $cid < 1 || $delta === 0) {
        return;
    }
    q('INSERT INTO leaderboard (category_id,user_id,points,wins,losses) VALUES (?,?,?,0,0)
       ON CONFLICT(category_id,user_id) DO UPDATE SET points = MAX(0, leaderboard.points + excluded.points)',
        [$cid, $uid, $delta]);
}

function home_layout_default(): array
{
    return [
        ['id' => 'hero', 'label' => 'هیرو', 'on' => 1],
        ['id' => 'sessions', 'label' => 'رویدادها', 'on' => 1],
        ['id' => 'games', 'label' => 'میزهای بازی', 'on' => 1],
        ['id' => 'egames', 'label' => 'بازی‌های الکترونیک', 'on' => 1],
        ['id' => 'guide', 'label' => 'آموزش نقش', 'on' => 1],
        ['id' => 'leaderboard', 'label' => 'لیدربورد', 'on' => 1],
        ['id' => 'blog', 'label' => 'آموزش', 'on' => 1],
        ['id' => 'about', 'label' => 'درباره رکاد', 'on' => 1],
    ];
}

function home_layout(): array
{
    $def = home_layout_default();
    $raw = setting('home_layout');
    if ($raw === '') {
        return $def;
    }
    $saved = json_decode($raw, true);
    if (!is_array($saved)) {
        return $def;
    }
    $by = [];
    foreach ($def as $d) {
        $by[$d['id']] = $d;
    }
    $out = [];
    $seen = [];
    foreach ($saved as $row) {
        $id = (string)($row['id'] ?? '');
        if ($id === '' || !isset($by[$id]) || isset($seen[$id])) {
            continue;
        }
        $out[] = ['id' => $id, 'label' => $by[$id]['label'], 'on' => empty($row['on']) ? 0 : 1];
        $seen[$id] = 1;
    }
    foreach ($def as $d) {
        if (!isset($seen[$d['id']])) {
            $out[] = $d;
        }
    }
    return $out;
}

function cat_kind(array $c): string
{
    $k = (string)($c['kind'] ?? 'table');
    return $k === 'electronic' ? 'electronic' : 'table';
}

function origin_of(array $u): string
{
    return (($u['origin'] ?? '') === 'guest') ? 'guest' : 'rokad';
}

function origin_fa(string $o): string
{
    return $o === 'guest' ? 'خارج از رکاد' : 'رکاد';
}

function gender_of(array $u): string
{
    $g = (string)($u['gender'] ?? '');
    return $g === 'girl' ? 'girl' : ($g === 'boy' ? 'boy' : '');
}

function gender_fa(string $g): string
{
    return $g === 'girl' ? 'دختر' : ($g === 'boy' ? 'پسر' : 'نامشخص');
}

function audience_of(array $s): string
{
    $a = (string)($s['audience'] ?? 'boys');
    if ($a === 'girls') {
        return 'girls';
    }
    if ($a === 'mixed') {
        return 'mixed';
    }
    return 'boys';
}

function audience_fa(string $a): string
{
    if ($a === 'girls') {
        return 'دختران';
    }
    if ($a === 'mixed') {
        return 'ترکیبی';
    }
    return 'پسران';
}

function badge_level_cost(int $toLevel): int
{
    if ($toLevel <= 1) {
        return 1;
    }
    return 3 * ($toLevel - 1);
}

function badge_wins_for_level(int $level): int
{
    if ($level <= 1) {
        return $level < 1 ? 0 : 1;
    }
    $total = 1;
    for ($l = 2; $l <= $level; $l++) {
        $total += badge_level_cost($l);
    }
    return $total;
}

function badge_level_from_wins(int $wins): int
{
    if ($wins < 1) {
        return 0;
    }
    $level = 1;
    $spent = 1;
    while (true) {
        $cost = badge_level_cost($level + 1);
        if ($wins < $spent + $cost) {
            break;
        }
        $spent += $cost;
        $level++;
        if ($level > 400) {
            break;
        }
    }
    return $level;
}

function badge_progress(int $wins): array
{
    $wins = max(0, $wins);
    $level = badge_level_from_wins($wins);
    $floor = $level >= 1 ? badge_wins_for_level($level) : 0;
    $need = badge_level_cost($level + 1);
    $into = max(0, $wins - $floor);
    return [
        'level' => $level,
        'wins' => $wins,
        'into' => $into,
        'need' => $need,
        'remain' => max(0, $need - $into),
        'pct' => $need > 0 ? (int)min(100, round(100 * $into / $need)) : 0,
    ];
}

function user_badges(int $uid): array
{
    if ($uid < 1) {
        return [];
    }
    return q('SELECT b.*, ub.level, ub.wins, ub.created_at AS earned_at, COALESCE(ub.last_at, ub.created_at) AS last_at
              FROM user_badges ub JOIN badges b ON b.id=ub.badge_id
              WHERE ub.user_id=? ORDER BY ub.level DESC, ub.wins DESC, b.id', [$uid])->fetchAll();
}

function grant_badge(int $uid, int $bid, ?int $by = null): array
{
    if ($uid < 1 || $bid < 1) {
        return ['level' => 0, 'wins' => 0, 'up' => false, 'prev' => 0];
    }
    $row = q('SELECT * FROM user_badges WHERE user_id=? AND badge_id=?', [$uid, $bid])->fetch();
    $prev = $row ? badge_level_from_wins((int)($row['wins'] ?? 0)) : 0;
    $wins = ($row ? (int)($row['wins'] ?? 0) : 0) + 1;
    $level = badge_level_from_wins($wins);
    if ($row) {
        q('UPDATE user_badges SET level=?, wins=?, last_at=datetime("now"), granted_by=? WHERE user_id=? AND badge_id=?',
            [$level, $wins, $by, $uid, $bid]);
    } else {
        q('INSERT INTO user_badges (user_id,badge_id,granted_by,created_at,last_at,level,wins) VALUES (?,?,?,datetime("now"),datetime("now"),?,?)',
            [$uid, $bid, $by, $level, $wins]);
    }
    return ['level' => $level, 'wins' => $wins, 'up' => $prev >= 1 && $level > $prev, 'prev' => $prev];
}

function revoke_badge_wins(int $uid, int $bid, int $n = 1): int
{
    if ($uid < 1 || $bid < 1) {
        return 0;
    }
    $n = max(1, $n);
    $row = q('SELECT * FROM user_badges WHERE user_id=? AND badge_id=?', [$uid, $bid])->fetch();
    if (!$row) {
        return 0;
    }
    $wins = max(0, (int)($row['wins'] ?? 0) - $n);
    if ($wins < 1) {
        q('DELETE FROM user_badges WHERE user_id=? AND badge_id=?', [$uid, $bid]);
        return 0;
    }
    $level = badge_level_from_wins($wins);
    q('UPDATE user_badges SET level=?, wins=? WHERE user_id=? AND badge_id=?', [$level, $wins, $uid, $bid]);
    return $wins;
}

function medal_tier(int $level): array
{
    $named = [
        1 => ['برنز', 'bronze'],
        2 => ['نقره', 'silver'],
        3 => ['طلا', 'gold'],
        4 => ['پلاتین', 'platinum'],
        5 => ['الماس', 'diamond'],
        6 => ['یاقوت', 'ruby'],
        7 => ['استاد', 'master'],
    ];
    if (isset($named[$level])) {
        return $named[$level];
    }
    if ($level < 1) {
        return ['—', 'bronze'];
    }
    return ['افسانه ' . fa_num($level - 7), 'legend'];
}

function medal_coin_html(array $b, string $clsExtra = ''): string
{
    $img = trim((string)($b['image'] ?? ''));
    $ico = (string)($b['icon'] ?? '★');
    $html = '<div class="medal-coin' . ($clsExtra !== '' ? ' ' . e($clsExtra) : '') . '" title="' . e($b['hint'] ?? '') . '">';
    $html .= '<span class="medal-shine" aria-hidden="true"></span>';
    if ($img !== '') {
        $html .= '<img src="' . e(media($img)) . '" alt="">';
    } else {
        $html .= '<span class="medal-ico">' . e($ico !== '' ? $ico : '★') . '</span>';
    }
    $html .= '</div>';
    return $html;
}

function render_medals(int $uid, string $mode = 'compact'): void
{
    $rows = user_badges($uid);
    if (!$rows) {
        echo '<p class="empty medal-empty">هنوز مدالی روی این پروفایل نیست.</p>';
        return;
    }
    static $pop = false;
    if (!$pop) {
        $pop = true;
        echo '<dialog class="medal-pop" id="medalPop">';
        echo '<form method="dialog" class="medal-pop-in">';
        echo '<div class="medal-pop-coin" data-pop-coin></div>';
        echo '<h3 data-pop-name></h3>';
        echo '<p class="medal-pop-hint" data-pop-hint></p>';
        echo '<div class="medal-pop-meta" data-pop-meta></div>';
        echo '<button class="btn btn-solid" value="ok">باشه</button>';
        echo '</form></dialog>';
    }
    echo '<div class="medal-rack' . ($mode === 'compact' ? ' is-compact' : '') . '">';
    foreach ($rows as $b) {
        $wins = max(0, (int)($b['wins'] ?? 0));
        $pg = badge_progress($wins);
        $lv = max(1, $pg['level']);
        [$tier, $cls] = medal_tier($lv);
        $payload = [
            'name' => (string)$b['name'],
            'hint' => (string)($b['hint'] ?? ''),
            'meta' => 'سطح ' . fa_num($lv) . ' · ' . $tier . "\n" . fa_num($wins) . ' برد · تا سطح بعد ' . fa_num($pg['remain']) . ' برد' . "\nاولین: " . jdate_format((string)($b['earned_at'] ?? ''), 'date') . ' · آخرین: ' . jdate_format((string)($b['last_at'] ?? ''), 'date'),
            'icon' => (string)($b['icon'] ?? '★'),
            'image' => trim((string)($b['image'] ?? '')) !== '' ? media($b['image']) : '',
            'cls' => $cls,
        ];
        echo '<button type="button" class="medal-hit medal ' . e($cls) . '" data-medal="' . e(json_encode($payload, JSON_UNESCAPED_UNICODE)) . '">';
        echo medal_coin_html($b);
        echo '<span class="medal-hit-name">' . e($b['name']) . '</span>';
        if ($mode !== 'compact') {
            echo '<div class="medal-tier">سطح ' . fa_num($lv) . ' · ' . e($tier) . '</div>';
            echo '<div class="medal-wins">' . fa_num($wins) . ' برد</div>';
        }
        echo '</button>';
    }
    echo '</div>';
}

function rokad_mark_html(array $u): string
{
    if (origin_of($u) !== 'rokad') {
        return '';
    }
    return '<img class="rokad-mark" src="' . e(asset('img/brand/mark-teal.png')) . '" alt="رکاد" title="هنرستان رکاد">';
}

function player_who_html(array $u, string $href = ''): string
{
    $name = e($u['display_name'] ?? '');
    $inner = '<img src="' . e(media($u['avatar'] ?? '')) . '" alt="">'
        . '<span class="who-meta"><span class="who-name">' . $name . rokad_mark_html($u) . '</span></span>';
    if ($href !== '') {
        return '<a class="who" href="' . e($href) . '">' . $inner . '</a>';
    }
    return '<span class="who">' . $inner . '</span>';
}

function notify_shell(?array $fl = null): void
{
    ?>
<div class="toast-host" id="toastHost" aria-live="polite">
  <?php if ($fl): ?>
    <div class="toast toast-<?= e($fl['type']) ?>" data-toast>
      <span class="toast-dot" aria-hidden="true"></span>
      <p><?= e($fl['msg']) ?></p>
      <button type="button" class="toast-x" data-toast-close aria-label="بستن">×</button>
    </div>
  <?php endif; ?>
</div>
<div class="ask-layer" id="askLayer" hidden>
  <div class="ask-card" role="dialog" aria-modal="true">
    <p id="askMsg"></p>
    <div class="ask-actions">
      <button type="button" class="btn" id="askNo">بی‌خیال</button>
      <button type="button" class="btn btn-solid" id="askYes">انجام بده</button>
    </div>
  </div>
</div>
    <?php
}

function lb_top_half(array $rows): array
{
    $n = count($rows);
    if ($n === 0) return [];
    return array_slice($rows, 0, max(1, (int)ceil($n / 2)));
}

function status_fa(string $s): string
{
    return match ($s) {
        'upcoming' => 'پیش‌رو',
        'ongoing' => 'در حال برگزاری',
        'finished' => 'تمام‌شده',
        'cancelled' => 'لغو',
        'pending' => 'در انتظار',
        'paid_half' => 'بخشی آنلاین',
        'paid_full' => 'تسویه کامل',
        'venue' => 'پرداخت در محل',
        'refunded' => 'بازگشت وجه',
        'forfeited' => 'ضبط‌شده (غیبت)',
        'attended' => 'حاضر',
        'no_show' => 'غایب',
        'success' => 'موفق',
        'failed' => 'ناموفق',
        'user' => 'بازیکن',
        'admin' => 'ادمین',
        'administrator' => 'ادمین سطح ۲ (Administrator)',
        'godgame' => 'گادگیم (خدای بازی)',
        'blogger' => 'وبلاگ‌نویس',
        'simulated' => 'شبیه‌سازی',
        'zarinpal' => 'زرین‌پال',
        'nextpay' => 'نکست‌پی',
        'idpay' => 'آیدی‌پی',
        'sandbox' => 'آزمایشی',
        'live' => 'واقعی',
        'tutorial' => 'آموزش',
        'gallery' => 'گالری',
        'news' => 'خبر',
        'scenario' => 'سناریو',
        'win' => 'برد',
        'loss' => 'باخت',
        'citizen' => 'شهر',
        'mafia' => 'مافیا',
        'indie' => 'مستقل',
        'self' => 'خود بازیکن',
        'opponent' => 'حریف',
        'charge' => 'شارژ کیف پول',
        'spend' => 'پرداخت از کیف پول',
        'admin_adjust' => 'تنظیم ادمین',
        default => $s,
    };
}

function photo_presets(string $kind = 'cover'): array
{
    if ($kind === 'avatar') {
        $out = [];
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
            foreach (glob(ROOT . '/assets/img/presets/*.' . $ext) ?: [] as $f) {
                $out[] = '/assets/img/presets/' . basename($f);
            }
        }
        sort($out);
        return $out;
    }
    if ($kind === 'trust' || $kind === 'badge') {
        return [];
    }
    $files = [
        'hero-cafe.jpg','cat-mafia.jpg','cat-golyapooch.jpg','cat-chess.jpg',
        'cat-boardgame.jpg','cat-werewolf.jpg','blog-mafia-classic.jpg',
        'blog-lastmove.jpg','blog-golyapooch.jpg',
        'egame-fifa.png','egame-ps.png','egame-arcade.png',
    ];
    $out = [];
    foreach ($files as $f) {
        if (is_file(ROOT . '/assets/img/' . $f)) {
            $out[] = '/assets/img/' . $f;
        }
    }
    return $out;
}

function allowed_photo_path(string $path): bool
{
    $path = str_replace('\\', '/', $path);
    if (str_contains($path, '..')) return false;
    if (!preg_match('#^/(assets|uploads)/[A-Za-z0-9/._-]+$#', $path)) return false;
    return is_file(ROOT . $path);
}

function pick_photo(string $fileField, string $presetField, string $subdir, string $old = '', array $allowed = ['jpg','jpeg','png','webp','gif','svg']): string
{
    $up = save_upload($fileField, $subdir, $allowed);
    if ($up) return $up;
    $pre = trim((string)post($presetField));
    if ($pre !== '' && allowed_photo_path($pre)) return $pre;
    return $old;
}

function render_photo_picker(string $fileField, string $presetField, string $current = '', string $kind = 'cover'): void
{
    static $n = 0;
    $n++;
    $src = $current !== '' ? media($current) : asset('img/logo-emblem.png');
    echo '<div class="photo-picker" data-picker>';
    echo '<div class="photo-preview' . ($kind === 'avatar' || $kind === 'badge' ? ' is-avatar' : '') . ($kind === 'trust' ? ' is-trust' : '') . '">';
    echo '<img src="' . e($src) . '" alt="" data-preview' . ($current === '' ? ' class="is-empty"' : '') . '>';
    echo '</div>';
    echo '<div class="photo-actions">';
    echo '<label class="file-btn"><input type="file" name="' . e($fileField) . '" accept="image/*"><span>انتخاب از دستگاه</span></label>';
    echo '<input type="hidden" name="' . e($presetField) . '" value="" data-preset>';
    if ($current !== '') {
        echo '<span class="photo-current">عکس فعلی ذخیره شده</span>';
    }
    echo '</div>';
    $presets = photo_presets($kind);
    if ($presets) {
        echo '<p class="photo-hint">یا از عکس‌های آماده انتخاب کن</p>';
        echo '<div class="preset-grid">';
        foreach ($presets as $p) {
            $on = $current === $p ? ' is-on' : '';
            echo '<button type="button" class="preset-item' . $on . '" data-preset-src="' . e($p) . '" data-preset-url="' . e(media($p)) . '">';
            echo '<img src="' . e(media($p)) . '" alt=""></button>';
        }
        echo '</div>';
    }
    echo '</div>';
}

function session_has_seat_sql(): string
{
    return '(SELECT COUNT(*) FROM reservations r WHERE r.session_id=s.id AND r.payment_status IN ("paid_half","paid_full","venue")) < s.total_seats';
}

function paid_seat_count(int $sessionId): int
{
    return (int)q(
        'SELECT COUNT(*) FROM reservations WHERE session_id=? AND payment_status IN ("paid_half","paid_full","venue")',
        [$sessionId]
    )->fetchColumn();
}

function session_charge(array $s): array
{
    $price = max(0, (int)($s['price'] ?? 0));
    $type = (string)($s['pay_type'] ?? 'full');
    if ($type === 'part') {
        $now = max(0, (int)($s['online_amount'] ?? 0));
        if ($now < 1) {
            $now = $price;
        }
        $now = min($now, $price);
        return [
            'type' => 'part',
            'price' => $price,
            'now' => $now,
            'later' => max(0, $price - $now),
            'status' => $now >= $price ? 'paid_full' : 'paid_half',
            'label' => $now >= $price ? 'پرداخت کامل آنلاین' : ('آنلاین ' . money($now) . ' · بقیه در محل'),
        ];
    }
    if ($type === 'cash') {
        return [
            'type' => 'cash',
            'price' => $price,
            'now' => 0,
            'later' => $price,
            'status' => 'venue',
            'label' => 'پول در محل',
        ];
    }
    if ($type === 'discount') {
        $d = max(0, (int)($s['discount_price'] ?? 0));
        if ($d < 1) {
            $d = $price;
        }
        return [
            'type' => 'discount',
            'price' => $price,
            'now' => $d,
            'later' => 0,
            'status' => 'paid_full',
            'label' => 'با تخفیف · ' . money($d),
        ];
    }
    return [
        'type' => 'full',
        'price' => $price,
        'now' => $price,
        'later' => 0,
        'status' => 'paid_full',
        'label' => 'پرداخت کامل آنلاین',
    ];
}

function reservation_try_wallet(int $rid, int $uid, int $now, string $payStatus): bool
{
    if ($rid < 1 || $uid < 1 || $now < 1) {
        return false;
    }
    return (bool)db_immediate(function () use ($rid, $uid, $now, $payStatus) {
        $mine = q('SELECT * FROM reservations WHERE id=?', [$rid])->fetch();
        if (!$mine || (int)$mine['user_id'] !== $uid) {
            return false;
        }
        if (in_array((string)$mine['payment_status'], ['paid_half', 'paid_full', 'venue'], true)) {
            return true;
        }
        $fresh = q('SELECT wallet FROM users WHERE id=?', [$uid])->fetch();
        if ((int)($fresh['wallet'] ?? 0) < $now) {
            return false;
        }
        $sess = q('SELECT * FROM game_sessions WHERE id=?', [(int)$mine['session_id']])->fetch();
        if (!$sess || $sess['status'] !== 'upcoming') {
            throw new RuntimeException('این نشست دیگر قابل رزرو نیست.');
        }
        if (paid_seat_count((int)$mine['session_id']) >= (int)$sess['total_seats']) {
            throw new RuntimeException('جا خالی نمانده.');
        }
        q('UPDATE users SET wallet = wallet - ? WHERE id=?', [$now, $uid]);
        q('INSERT INTO wallet_tx (user_id,amount,type,status,note,created_at) VALUES (?,?,?,?,?,datetime("now"))',
            [$uid, -$now, 'spend', 'success', 'رزرو #' . $rid]);
        $auth = 'WALLET-' . strtoupper(bin2hex(random_bytes(4)));
        q('INSERT INTO payments (reservation_id,user_id,amount,type,status,authority,created_at) VALUES (?,?,?,?,?,?,datetime("now"))',
            [$rid, $uid, $now, 'wallet', 'success', $auth]);
        q('UPDATE reservations SET amount_paid=?, payment_status=?, paid_at=datetime("now") WHERE id=?',
            [$now, $payStatus, $rid]);
        return true;
    });
}

function db_immediate(callable $fn)
{
    $pdo = db();
    $mine = !$pdo->inTransaction();
    if ($mine) {
        // Raw BEGIN/COMMIT — PDO::commit() after exec('BEGIN IMMEDIATE')
        // throws "no active transaction" on some XAMPP/Windows SQLite builds.
        $pdo->exec('BEGIN IMMEDIATE');
    }
    try {
        $r = $fn();
        if ($mine) {
            $pdo->exec('COMMIT');
        }
        return $r;
    } catch (Throwable $e) {
        if ($mine) {
            try {
                $pdo->exec('ROLLBACK');
            } catch (Throwable $ignore) {
            }
        }
        throw $e;
    }
}

function pay_provider_label(): string
{
    $p = setting('pay_provider') ?: 'simulated';
    return status_fa($p);
}

function pay_is_live(): bool
{
    return setting('pay_enabled') === '1'
        && setting('pay_provider') !== ''
        && setting('pay_provider') !== 'simulated'
        && trim(setting('pay_merchant')) !== '';
}

function comment_avatar(array $c): string
{
    $a = trim((string)($c['avatar'] ?? ''));
    if ($a !== '' && allowed_photo_path($a)) {
        return media($a);
    }
    $presets = photo_presets('avatar');
    if ($presets) {
        return media($presets[abs(crc32((string)($c['name'] ?? 'x'))) % count($presets)]);
    }
    return asset('img/logo-emblem.png');
}

function render_quote_cards(array $quotes): void
{
    if (!$quotes) {
        return;
    }
    $quotes = array_values($quotes);
    while (count($quotes) < 4 && $quotes) {
        $quotes[] = $quotes[count($quotes) % max(1, count($quotes) - 0)];
    }
    $quotes = array_slice($quotes, 0, 4);
    static $skin = false;
    if (!$skin) {
        $skin = true;
        echo '<style>
.voices-section .wrap{overflow:visible}
.voice-stage{position:relative;height:380px;margin:10px auto 8px;max-width:980px;overflow:visible}
.voice-floor{position:absolute;inset:0;perspective:900px}
.voice-card{
  position:absolute;top:18px;left:50%;width:min(300px,56vw);
  padding:20px 18px 16px;border-radius:.8375rem 0 .8375rem 0;border:2px solid #21295a;
  background:#fff;color:#292827;box-shadow:6px 8px 0 0 #e0195b;
  transition:transform .8s cubic-bezier(.22,.8,.28,1),opacity .8s ease;
  transform:translate(-50%,40px) scale(.7);opacity:0;z-index:1;pointer-events:none
}
.voice-card .quote-mark{color:#e0195b;font-size:42px;line-height:.8;font-family:Montserrat,sans-serif;opacity:.75;margin-bottom:8px;font-weight:900}
.voice-card .quote-body{color:#292827;line-height:1.95;font-size:15px;min-height:5.4em;margin:0 0 14px;font-weight:600}
.voice-card .quote-who{display:flex;align-items:center;gap:10px}
.voice-card .quote-who img{width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid #58bdaf}
.voice-card .quote-who b{display:block;color:#21295a;font-size:14px}
.voice-card .quote-who small{color:#5b5a78;font-size:11px}
.voice-card.pos-0{transform:translate(-50%,0) scale(1) rotate(0);opacity:1;z-index:5;pointer-events:auto;box-shadow:6px 8px 0 0 #58bdaf}
.voice-card.pos-1{transform:translate(calc(-50% + 38%),38px) scale(.78) rotate(6deg);opacity:.95;z-index:3;pointer-events:auto}
.voice-card.pos-3{transform:translate(calc(-50% - 38%),38px) scale(.78) rotate(-6deg);opacity:.95;z-index:3;pointer-events:auto}
.voice-card.pos-2{transform:translate(-50%,88px) scale(.62) rotate(0);opacity:0;z-index:1}
.voice-nav{
  position:absolute;top:46%;z-index:8;width:42px;height:42px;border-radius:50%;
  border:2px solid #21295a;background:#fff;color:#21295a;font-size:22px;cursor:pointer;font-weight:800
}
.voice-nav[data-voice="-1"]{right:4px}
.voice-nav[data-voice="1"]{left:4px}
@media (max-width:900px){
  .voice-stage{height:340px}
  .voice-card{width:min(280px,62vw)}
  .voice-card.pos-1{transform:translate(calc(-50% + 30%),34px) scale(.74) rotate(5deg)}
  .voice-card.pos-3{transform:translate(calc(-50% - 30%),34px) scale(.74) rotate(-5deg)}
}
@media (max-width:768px){
  .voice-stage{height:290px}
  .voice-card{width:min(230px,68vw);padding:14px 12px}
  .voice-card .quote-body{font-size:13px;min-height:4.6em;line-height:1.8}
  .voice-card.pos-1{transform:translate(calc(-50% + 20%),28px) scale(.7) rotate(4deg)}
  .voice-card.pos-3{transform:translate(calc(-50% - 20%),28px) scale(.7) rotate(-4deg)}
  .voice-nav{width:36px;height:36px;top:40%}
}
</style>';
    }
    echo '<div class="voice-stage" data-voices>';
    echo '<button type="button" class="voice-nav" data-voice="-1" aria-label="قبلی">‹</button>';
    echo '<div class="voice-floor">';
    foreach ($quotes as $i => $c) {
        echo '<article class="voice-card" data-voice-i="' . (int)$i . '">';
        echo '<div class="quote-mark">”</div>';
        echo '<p class="quote-body">' . e((string)$c['body']) . '</p>';
        echo '<div class="quote-who"><img src="' . e(comment_avatar($c)) . '" alt="">';
        echo '<div><b>' . e((string)$c['name']) . '</b><small>بازیکن رکاد</small></div></div>';
        echo '</article>';
    }
    echo '</div>';
    echo '<button type="button" class="voice-nav" data-voice="1" aria-label="بعدی">›</button>';
    echo '</div>';
    echo '<script>(function(){var r=document.querySelector("[data-voices]");if(!r||r.dataset.on)return;r.dataset.on="1";var cards=[].slice.call(r.querySelectorAll(".voice-card"));var n=cards.length,i=0,t;function lay(){cards.forEach(function(c,k){var d=((k-i)%n+n)%n;c.className="voice-card pos-"+d;});}function go(d){i=(i+d+n)%n;lay();}r.addEventListener("click",function(e){var b=e.target.closest("[data-voice]");if(!b)return;go(parseInt(b.getAttribute("data-voice"),10)||0);});r.addEventListener("pointerdown",function(e){if(e.target.closest(".voice-nav"))return;r._x=e.clientX;});r.addEventListener("pointerup",function(e){if(r._x==null)return;var dx=e.clientX-r._x;r._x=null;if(Math.abs(dx)>40)go(dx>0?-1:1);});lay();t=setInterval(function(){go(1);},4200);r.addEventListener("pointerenter",function(){clearInterval(t);});r.addEventListener("pointerleave",function(){t=setInterval(function(){go(1);},4200);});})();</script>';
}

function random_preset_avatar(): string
{
    $presets = photo_presets('avatar');
    $raster = array_values(array_filter($presets, static function (string $p): bool {
        return (bool)preg_match('/\.(jpe?g|png|webp)$/i', $p);
    }));
    $pool = $raster ?: $presets;
    if (!$pool) {
        return '';
    }
    return $pool[random_int(0, count($pool) - 1)];
}

function god_catalog(): array
{
    $scenarios = q('SELECT s.id, s.name, s.slug, s.category_id, c.name AS cat_name
                    FROM scenarios s JOIN categories c ON c.id=s.category_id
                    ORDER BY s.id')->fetchAll();
    $roles = q('SELECT id, scenario_id, category_id, name, side, symbol, color, night_wake FROM roles ORDER BY side, id')->fetchAll();
    $people = q('SELECT display_name, gender, username FROM users WHERE role="user" ORDER BY display_name')->fetchAll();
    $sessions = q('SELECT s.id, s.title, s.session_date, s.start_time, s.audience, s.total_seats, s.status, c.name AS cat_name
                   FROM game_sessions s JOIN categories c ON c.id=s.category_id
                   WHERE s.status IN ("upcoming","ongoing")
                   ORDER BY s.session_date DESC, s.id DESC LIMIT 40')->fetchAll();
    $resBy = [];
    $rs = q('SELECT r.session_id, r.user_id, u.display_name, u.gender
             FROM reservations r JOIN users u ON u.id=r.user_id
             WHERE r.payment_status IN ("paid_half","paid_full","venue")
             ORDER BY r.id')->fetchAll();
    foreach ($rs as $row) {
        $sid = (int)$row['session_id'];
        if (!isset($resBy[$sid])) {
            $resBy[$sid] = [];
        }
        $resBy[$sid][] = [
            'name' => (string)$row['display_name'],
            'gender' => gender_of($row),
            'user_id' => (int)$row['user_id'],
        ];
    }
    foreach ($sessions as &$sess) {
        $sess['players'] = $resBy[(int)$sess['id']] ?? [];
        $sess['audience'] = audience_of($sess);
    }
    unset($sess);
    foreach ($roles as &$r) {
        if (trim((string)$r['symbol']) === '') {
            $book = role_book()[$r['name']] ?? [];
            $r['symbol'] = $book['symbol'] ?? '♠';
        }
        $r['night_wake'] = (int)($r['night_wake'] ?? 0);
    }
    unset($r);
    $nights = [];
    if (function_exists('rokad_night_order')) {
        $nights = rokad_night_order();
    }
    $badges = q('SELECT id, slug, name, hint, color, icon, image FROM badges ORDER BY id')->fetchAll();
    foreach ($badges as &$b) {
        $img = trim((string)($b['image'] ?? ''));
        $b['image'] = $img !== '' ? media($img) : '';
    }
    unset($b);
    return ['scenarios' => $scenarios, 'roles' => $roles, 'people' => $people, 'nights' => $nights, 'badges' => $badges, 'sessions' => $sessions];
}

function trust_logos(): array
{
    $out = [];
    foreach (['trust_logo_1','trust_logo_2','trust_logo_3'] as $k) {
        $v = trim(setting($k));
        if ($v !== '' && allowed_photo_path($v)) {
            $out[] = $v;
        }
    }
    return $out;
}

function current_path(): string
{
    return request_path();
}

function active_nav(string $prefix): string
{
    $p = current_path();
    if ($prefix === '' && $p === '') return 'is-active';
    if (in_array($prefix, ['reserve', 'events'], true) && ($p === 'reserve' || $p === 'events' || str_starts_with($p, 'reserve/') || str_starts_with($p, 'events/'))) {
        return 'is-active';
    }
    if ($prefix !== '' && ($p === $prefix || str_starts_with($p, $prefix . '/'))) return 'is-active';
    return '';
}

function admin_current_tab(string $default = 'list'): string
{
    $t = (string)getv('tab');
    if ($t !== '') {
        return $t;
    }
    $edit = (string)getv('edit');
    if ($edit !== '' && $edit !== '0') {
        return 'add';
    }
    return $default;
}

function admin_tabs(string $path, array $tabs, ?string $on = null): void
{
    $ids = array_keys($tabs);
    $on = $on ?? admin_current_tab((string)($ids[0] ?? 'list'));
    if (!isset($tabs[$on])) {
        $on = (string)($ids[0] ?? 'list');
    }
    $keep = $_GET;
    foreach (['tab', 'r', 'page', 'route'] as $drop) {
        unset($keep[$drop]);
    }
    $cls = 'admin-tabs' . (count($tabs) >= 3 ? ' tabs-3' : '');
    echo '<nav class="' . $cls . '" aria-label="بخش‌ها">';
    foreach ($tabs as $id => $label) {
        $q = $keep;
        $q['tab'] = $id;
        if (!in_array((string)$id, ['add', 'cats', 'make'], true)) {
            unset($q['edit']);
        }
        if ((string)$id !== 'scens') {
            unset($q['scen']);
        }
        $qs = http_build_query($q);
        $href = url($path) . ($qs !== '' ? '?' . $qs : '');
        echo '<a class="admin-tab' . ($on === $id ? ' is-on' : '') . '" href="' . e($href) . '">' . e($label) . '</a>';
    }
    echo '</nav>';
}

function excerpt(string $html, int $len = 140): string
{
    $t = trim(preg_replace('/\s+/u', ' ', strip_tags($html)));
    if (mb_strlen($t) <= $len) return $t;
    return mb_substr($t, 0, $len) . '…';
}

function paginate(int $total, int $page, int $per = 12): array
{
    $pages = max(1, (int)ceil($total / $per));
    $page = max(1, min($page, $pages));
    return [$page, $pages, ($page - 1) * $per, $per];
}
