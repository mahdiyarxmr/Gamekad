<?php
require_login();
$u = current_user(true);

if ($param === 'pay') {
    $amount = (int)($_SESSION['wallet_charge'] ?? 0);
    if ($amount < 10000) {
        flash('x', 'اول مبلغ شارژ را انتخاب کن.', 'warn');
        redirect('wallet');
    }
    if (is_post()) {
        csrf_check();
        $act = (string)post('act');
        if ($act === 'cancel') {
            unset($_SESSION['wallet_charge']);
            flash('x', 'پرداخت لغو شد. هیچ مبلغی کم نشد.', 'warn');
            redirect('wallet');
        }
        $auth = 'WL-' . strtoupper(bin2hex(random_bytes(5)));
        db_immediate(function () use ($u, $amount, $auth) {
            q('UPDATE users SET wallet = COALESCE(wallet,0) + ? WHERE id=?', [$amount, (int)$u['id']]);
            q('INSERT INTO wallet_tx (user_id,amount,type,status,note,created_at) VALUES (?,?,?,?,?,datetime("now"))',
                [(int)$u['id'], $amount, 'charge', 'success', $auth]);
        });
        unset($_SESSION['wallet_charge']);
        current_user(true);
        flash('x', 'کیف پول با ' . money($amount) . ' شارژ شد. رسید: ' . $auth, 'ok');
        redirect('wallet');
    }
    $title = 'درگاه شارژ';
    require ROOT . '/includes/header.php';
    ?>
    <section class="section" style="padding-top:24px">
      <div class="wrap">
        <div class="pay-sim">
          <div class="pay-sim-top">
            <span class="en">SHAPARAK · TEST</span>
            <b><?= e(pay_provider_label()) ?></b>
          </div>
          <p class="pay-sim-k">نمونه پرداخت آزمایشی رکاد</p>
          <div class="pay-amount"><?= money($amount) ?></div>
          <div class="gateway">
            <div class="row"><span>پذیرنده</span><b>رکاد</b></div>
            <div class="row"><span>بابت</span><b>شارژ کیف پول</b></div>
            <div class="row"><span>بازیکن</span><b><?= e($u['display_name']) ?></b></div>
            <div class="row"><span>وضعیت</span><b>آزمایشی — پولی جابه‌جا نمی‌شود</b></div>
          </div>
          <form method="post" class="pay-sim-form">
            <?= csrf_field() ?>
            <label>شماره کارت نمونه</label>
            <input class="ltr-input" dir="ltr" value="6037-9911-1111-1111" readonly>
            <label>CVV2</label>
            <input class="ltr-input" dir="ltr" value="123" readonly>
            <div class="pay-sim-actions">
              <button class="btn btn-solid" name="act" value="ok" type="submit">پرداخت آزمایشی و شارژ</button>
              <button class="btn" name="act" value="cancel" type="submit">انصراف</button>
            </div>
          </form>
          <p class="pay-sim-note">این صفحه همان چیزی است که بعداً به درگاه واقعی وصل می‌شود. الان فقط شبیه‌سازی است تا جریان شارژ را ببینی.</p>
        </div>
      </div>
    </section>
    <?php
    require ROOT . '/includes/footer.php';
    exit;
}

if (is_post()) {
    csrf_check();
    $amount = (int)post('amount');
    if ($amount < 10000 || $amount > 5000000) {
        flash('x', 'مبلغ شارژ باید بین ۱۰ هزار تا ۵ میلیون تومان باشد.', 'err');
        redirect('wallet');
    }
    $_SESSION['wallet_charge'] = $amount;
    redirect('wallet/pay');
}

$tx = q('SELECT * FROM wallet_tx WHERE user_id=? ORDER BY id DESC LIMIT 20', [(int)$u['id']])->fetchAll();
$title = 'کیف پول';
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">WALLET</div>
    <h1>کیف پول</h1>
    <p class="sub">موجودی را شارژ کن و بعداً برای رزرو رویداد خرجش کن. شارژ از یک درگاه نمونه می‌گذرد تا جریان پرداخت را ببینی.</p>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap grid-2">
    <div class="card pay-box">
      <p style="color:var(--muted)">موجودی فعلی</p>
      <div class="pay-amount"><?= money(user_wallet($u)) ?></div>
      <form method="post" class="form" style="text-align:right">
        <?= csrf_field() ?>
        <label>مبلغ شارژ (تومان)</label>
        <div class="amount-picks">
          <?php foreach ([50000,100000,200000,500000] as $a): ?>
            <button type="button" class="btn btn-sm" data-fill-amount="<?= (int)$a ?>"><?= money($a) ?></button>
          <?php endforeach; ?>
        </div>
        <input name="amount" type="number" min="10000" max="5000000" step="1000" required placeholder="مثلاً ۱۰۰۰۰۰" dir="ltr" class="ltr-input">
        <button class="btn btn-solid" type="submit">رفتن به درگاه نمونه</button>
      </form>
    </div>
    <div class="card" style="padding:22px">
      <h3>گردش حساب</h3>
      <?php if (!$tx): ?><p class="empty">هنوز تراکنشی نیست.</p><?php endif; ?>
      <table class="data-table">
        <tbody>
        <?php foreach ($tx as $t): ?>
          <tr>
            <td><?= e(status_fa($t['type'])) ?><br><span style="color:var(--muted);font-size:12px"><?= jdate_format($t['created_at']) ?></span></td>
            <td style="color:<?= (int)$t['amount']>=0?'var(--gold2)':'#ce1754' ?>"><?= ((int)$t['amount']>=0?'+':'') . money((int)$t['amount']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
