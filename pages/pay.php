<?php
require_login();
$u = current_user(true);
$rid = (int)$param;
$r = q('SELECT r.*, s.title, s.price, s.pay_type, s.online_amount, s.discount_price, s.session_date, s.start_time, s.id AS sid, s.status AS sstatus, s.total_seats
        FROM reservations r JOIN game_sessions s ON s.id=r.session_id WHERE r.id=?', [$rid])->fetch();
if (!$r || (int)$r['user_id'] !== (int)$u['id']) {
    flash('x','رزرو پیدا نشد.','err'); redirect('/account');
}
if (in_array($r['payment_status'], ['paid_half','paid_full','venue'], true)) {
    flash('x','این رزرو قبلاً ثبت شده.','ok'); redirect('/account');
}
$ch = session_charge($r);
$now = (int)$ch['now'];
if ($now <= 0) {
    q('UPDATE reservations SET payment_status="venue", amount_paid=0 WHERE id=?', [$rid]);
    flash('x','جا رزرو شد. پول را در محل بده.','ok');
    redirect('/account');
}
$left = max(0, (int)$r['total_seats'] - paid_seat_count((int)$r['sid']));
$bal = user_wallet($u);
if ($bal >= $now && $left > 0) {
    try {
        if (reservation_try_wallet($rid, (int)$u['id'], $now, (string)$ch['status'])) {
            flash('x', 'از کیف پول کم شد و جا رزرو شد.', 'ok');
            redirect('/account');
        }
    } catch (RuntimeException $e) {
        flash('x', $e->getMessage(), 'err');
        redirect('/account');
    }
}

if (is_post()) {
    csrf_check();
    try {
        $ok = db_immediate(function () use ($r, $u, $rid, $now, $ch) {
            $sess = q('SELECT * FROM game_sessions WHERE id=?', [(int)$r['sid']])->fetch();
            if (!$sess || $sess['status'] !== 'upcoming') {
                throw new RuntimeException('این نشست دیگر قابل رزرو نیست.');
            }
            $mine = q('SELECT * FROM reservations WHERE id=?', [$rid])->fetch();
            if (!$mine) throw new RuntimeException('رزرو پیدا نشد.');
            if (in_array($mine['payment_status'], ['paid_half','paid_full','venue'], true)) {
                return 'already';
            }
            $used = paid_seat_count((int)$r['sid']);
            if ($used >= (int)$sess['total_seats']) {
                q('UPDATE reservations SET payment_status="failed" WHERE id=?', [$rid]);
                throw new RuntimeException('این صندلی را کس دیگری گرفت. ظرفیت تکمیل است.');
            }
            $auth = 'GK-' . strtoupper(bin2hex(random_bytes(5)));
            q('INSERT INTO payments (reservation_id,user_id,amount,type,status,authority,created_at) VALUES (?,?,?,?,?,?,datetime("now"))',
                [$rid, (int)$u['id'], $now, 'deposit', 'success', $auth]);
            q('UPDATE reservations SET amount_paid=?, payment_status=?, paid_at=datetime("now") WHERE id=?', [$now, $ch['status'], $rid]);
            return $auth;
        });
    } catch (RuntimeException $e) {
        flash('x', $e->getMessage(), 'err');
        redirect('/pay/' . $rid);
    }
    if ($ok === 'already') {
        flash('x', 'این رزرو قبلاً پرداخت شده.', 'ok');
    } else {
        flash('x', 'پرداخت ثبت شد. صندلی تو در «' . $r['title'] . '» رزرو است. رسید: ' . $ok, 'ok');
    }
    redirect('/account');
}

$title = 'درگاه پرداخت';
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">GATEWAY</div>
    <h1>پرداخت رزرو</h1>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="card pay-box">
    <p style="color:var(--muted)"><?= e(pay_provider_label()) ?><?= setting('pay_mode')==='live'?' · واقعی':' · آزمایشی' ?></p>
    <div class="pay-amount"><?= money($now) ?></div>
    <div class="gateway">
      <div class="row"><span>پذیرنده</span><b>رکاد</b></div>
      <div class="row"><span>بابت</span><b><?= e($ch['label']) ?> · <?= e($r['title']) ?></b></div>
      <div class="row"><span>زمان بازی</span><b><?= jdate_format($r['session_date'].' '.$r['start_time']) ?></b></div>
      <div class="row"><span>قیمت</span><b><?= money((int)$ch['price']) ?></b></div>
      <div class="row"><span>الان</span><b><?= money($now) ?></b></div>
      <?php if ($ch['later'] > 0): ?>
      <div class="row"><span>بقیه در محل</span><b><?= money((int)$ch['later']) ?></b></div>
      <?php endif; ?>
      <div class="row"><span>کیف پول</span><b><?= money($bal) ?></b></div>
      <div class="row"><span>صندلی باقی</span><b><?= fa_num($left) ?></b></div>
    </div>
    <?php if ($left <= 0): ?>
      <p class="flash err" style="margin-top:16px">ظرفیت تکمیل است. این صندلی دیگر فروخته نمی‌شود.</p>
    <?php elseif (setting('pay_enabled') === '1'): ?>
      <form method="post" style="margin-top:18px">
        <?= csrf_field() ?>
        <button class="btn btn-solid" type="submit"><?= pay_is_live() ? 'پرداخت با '.e(pay_provider_label()) : 'پرداخت کارت (شبیه‌سازی)' ?></button>
      </form>
    <?php else: ?>
      <p class="flash warn" style="margin-top:16px">درگاه خاموش است. کیف پولت کافی نبود؛ از میزبان بپرس یا اول <a href="<?= e(url('wallet')) ?>">شارژ کن</a>.</p>
    <?php endif; ?>
    <p style="margin-top:12px;font-size:12px;color:var(--muted);line-height:1.8">اگر کیف پول کافی باشد همان‌جا کم می‌شود. وگرنه فقط درگاه. غیبت بعد از پرداخت کامل، درصدی ضبط می‌شود.</p>
  </div>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
