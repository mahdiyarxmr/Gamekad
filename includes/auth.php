<?php
declare(strict_types=1);

function current_user(bool $refresh = false): ?array
{
    static $u = false;
    if ($refresh) $u = false;
    if ($u !== false) return $u;
    $id = $_SESSION['uid'] ?? null;
    if (!$id) { $u = null; return null; }
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([(int)$id]);
    $row = $st->fetch() ?: null;
    $u = $row;
    return $row;
}

function user_role(): string
{
    $u = current_user();
    return $u ? (string)($u['role'] ?? 'user') : '';
}

function is_administrator(): bool
{
    return user_role() === 'administrator';
}

function is_admin(): bool
{
    $r = user_role();
    return $r === 'admin' || $r === 'administrator';
}

function is_godgame(): bool
{
    return user_role() === 'godgame';
}

function can_godgame(): bool
{
    return is_godgame() || is_admin();
}

function is_blogger(): bool
{
    return user_role() === 'blogger';
}

function can_staff(): bool
{
    return is_admin() || is_godgame();
}

function staff_home(): string
{
    if (is_godgame() && !is_admin()) return 'godgame';
    if (is_admin()) return 'admin';
    return 'admin/login';
}

function staff_label(): string
{
    if (is_godgame() && !is_admin()) return 'خدای بازی';
    if (is_administrator()) return 'پنل مدیریت';
    if (is_admin()) return 'پنل میزبان';
    return 'پنل';
}

function all_roles(): array
{
    return ['user', 'admin', 'administrator', 'godgame'];
}

function assignable_roles(): array
{
    $roles = ['user', 'admin', 'godgame'];
    if (is_administrator()) {
        $roles[] = 'administrator';
    }
    return $roles;
}

function can_manage_blog(): bool
{
    return is_admin();
}

function can_edit_post(?array $post): bool
{
    if (is_admin()) return true;
    if (!$post || !current_user()) return false;
    $uid = (int)current_user()['id'];
    if ((int)($post['author_id'] ?? 0) === $uid) return true;
    $row = q('SELECT 1 FROM blog_editors WHERE post_id=? AND user_id=?', [(int)$post['id'], $uid])->fetch();
    return (bool)$row;
}

function can_delete_post(?array $post): bool
{
    if (is_admin()) return true;
    if (!$post || !current_user()) return false;
    return (int)($post['author_id'] ?? 0) === (int)current_user()['id'];
}

function user_wallet(?array $u = null): int
{
    $u = $u ?? current_user();
    return (int)($u['wallet'] ?? 0);
}

function wallet_credit(int $uid, int $amount, string $note, string $type = 'refund'): void
{
    if ($uid < 1 || $amount === 0) {
        return;
    }
    q('UPDATE users SET wallet = COALESCE(wallet,0) + ? WHERE id=?', [$amount, $uid]);
    q('INSERT INTO wallet_tx (user_id,amount,type,status,note,created_at) VALUES (?,?,?,?,?,datetime("now"))',
        [$uid, $amount, $type, 'success', $note]);
}

function noshow_keep_percent(): int
{
    $v = trim(setting('noshow_keep_percent'));
    if ($v === '') {
        return 15;
    }
    return max(0, min(100, (int)$v));
}

function require_login(string $back = '/'): void
{
    if (!current_user()) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? $back;
        flash('x', 'برای ادامه وارد حساب شو.', 'warn');
        redirect('/login');
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('x', 'دسترسی ادمین لازم است.', 'err');
        redirect('/admin/login');
    }
}

function require_administrator(): void
{
    if (!is_administrator()) {
        flash('x', 'فقط Administrator (ادمین سطح ۲) به درگاه دسترسی دارد.', 'err');
        redirect('admin');
    }
}

function require_godgame(): void
{
    if (!can_godgame()) {
        flash('x', 'این بخش برای خدای بازی یا ادمین است.', 'err');
        redirect(current_user() ? '' : 'login');
    }
}

function auth_login(string $user, string $pass): bool
{
    $st = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
    $st->execute([$user, $user]);
    $row = $st->fetch();
    if (!$row || !password_verify($pass, $row['password'])) return false;
    $_SESSION['uid'] = (int)$row['id'];
    session_regenerate_id(true);
    current_user(true);
    return true;
}

function auth_register(array $d): array
{
    $username = trim($d['username'] ?? '');
    $email = trim($d['email'] ?? '');
    $pass = $d['password'] ?? '';
    $name = trim($d['display_name'] ?? '');
    $phone = trim($d['phone'] ?? '');
    $gender = (string)($d['gender'] ?? '');
    $gender = $gender === 'girl' ? 'girl' : ($gender === 'boy' ? 'boy' : '');
    $errs = [];
    if (!preg_match('/^[A-Za-z][A-Za-z0-9._]{2,20}$/', $username)) $errs[] = 'نام کاربری باید انگلیسی، ۳ تا ۲۱ حرف باشد.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = 'ایمیل نامعتبر است.';
    if (strlen($pass) < 6) $errs[] = 'رمز حداقل ۶ کاراکتر.';
    if (empty($d['_admin_create']) && array_key_exists('password_confirm', $d) && (string)$d['password_confirm'] !== (string)$pass) {
        $errs[] = 'دو بار رمز را یکی بنویس.';
    }
    if (mb_strlen($name) < 2) $errs[] = 'نام نمایشی را بنویس.';
    if ($gender === '' && empty($d['_admin_create'])) $errs[] = 'دختر یا پسر را انتخاب کن.';
    if ($errs) return $errs;

    $st = db()->prepare('SELECT id FROM users WHERE username=? OR email=?');
    $st->execute([$username, $email]);
    if ($st->fetch()) return ['این نام کاربری یا ایمیل قبلاً ثبت شده.'];

    $asRole = 'user';
    $origin = 'guest';
    if (!empty($d['_admin_create']) && is_admin()) {
        $want = (string)($d['role'] ?? 'user');
        if (in_array($want, assignable_roles(), true)) {
            $asRole = $want;
        }
        if ((string)($d['origin'] ?? '') === 'rokad') {
            $origin = 'rokad';
        }
        $g = (string)($d['gender'] ?? '');
        $gender = $g === 'girl' ? 'girl' : ($g === 'boy' ? 'boy' : $gender);
    }
    $loginAfter = !empty($d['_login']);
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $rel = random_preset_avatar();
    if ($rel === '') {
        $letter = mb_substr($name, 0, 1);
        $colors = ['#4a1c14','#3d2a12','#1f2a22','#2c1a28','#3a2410','#24160e','#4a2c18','#1a2428'];
        $color = $colors[abs(crc32($username)) % count($colors)];
        try {
            $rel = make_avatar($username, $letter, $color);
        } catch (Throwable $e) {
            $rel = '';
        }
    }

    db()->prepare('INSERT INTO users (username,email,password,display_name,phone,avatar,bio,role,origin,wallet,gender,created_at)
                   VALUES (?,?,?,?,?,?,?,?,?,0,?,datetime("now"))')
        ->execute([$username, $email, $hash, $name, $phone, $rel, '', $asRole, $origin, $gender]);
    if ($loginAfter) {
        $_SESSION['uid'] = (int)db()->lastInsertId();
        current_user(true);
    }
    return [];
}

function auth_logout(): void
{
    unset($_SESSION['uid']);
    session_regenerate_id(true);
}
