<?php
if ($param) {
    $s = q('SELECT s.*, c.name AS cat_name, c.slug AS cat_slug FROM game_sessions s JOIN categories c ON c.id=s.category_id WHERE s.id=?', [(int)$param])->fetch();
    if (!$s) { flash('x','رویداد پیدا نشد.','err'); redirect('events'); }
    $reserved = (int)q('SELECT COUNT(*) FROM reservations WHERE session_id=? AND payment_status IN ("paid_half","paid_full","venue")', [$s['id']])->fetchColumn();
    $left = max(0, (int)$s['total_seats'] - $reserved);
    $ch = session_charge($s);
    $aud = audience_of($s);

    if (is_post()) {
        csrf_check();
        require_login('events/'.$s['id']);
        $u = current_user();
        $g = gender_of($u);
        try {
            if ($aud !== 'mixed' && $g && (($aud === 'boys' && $g === 'girl') || ($aud === 'girls' && $g === 'boy'))) {
                throw new RuntimeException('این میز مخصوص ' . audience_fa($aud) . ' است.');
            }
            $rid = db_immediate(function () use ($s, $u) {
                $sess = q('SELECT * FROM game_sessions WHERE id=?', [(int)$s['id']])->fetch();
                if (!$sess || $sess['status'] !== 'upcoming') {
                    throw new RuntimeException('این رویداد دیگر قابل رزرو نیست.');
                }
                $exists = q('SELECT * FROM reservations WHERE session_id=? AND user_id=?', [$s['id'], $u['id']])->fetch();
                if ($exists && in_array($exists['payment_status'], ['paid_half','paid_full','venue'], true)) {
                    throw new RuntimeException('قبلاً برای این رویداد جا گرفته‌ای.');
                }
                $used = paid_seat_count((int)$s['id']);
                if ($used >= (int)$sess['total_seats']) {
                    throw new RuntimeException('جا خالی نمانده.');
                }
                if ($exists) {
                    return (int)$exists['id'];
                }
                q('INSERT INTO reservations (session_id,user_id,amount_paid,payment_status,attendance,created_at) VALUES (?,?,0,"pending","pending",datetime("now"))',
                    [$s['id'], $u['id']]);
                return (int)db()->lastInsertId();
            });
        } catch (RuntimeException $e) {
            flash('x', $e->getMessage(), $e->getMessage() === 'قبلاً برای این رویداد جا گرفته‌ای.' ? 'warn' : 'err');
            if (str_contains($e->getMessage(), 'قبلاً')) redirect('account');
            redirect('events/'.$s['id']);
        }
        if ($ch['now'] <= 0) {
            q('UPDATE reservations SET payment_status="venue", amount_paid=0 WHERE id=?', [$rid]);
            flash('x', 'جا رزرو شد. پول را در محل بده.', 'ok');
            redirect('account');
        }
        try {
            if (reservation_try_wallet((int)$rid, (int)$u['id'], (int)$ch['now'], (string)$ch['status'])) {
                flash('x', 'از کیف پول کم شد و جا رزرو شد.', 'ok');
                redirect('account');
            }
        } catch (RuntimeException $e) {
            flash('x', $e->getMessage(), 'err');
            redirect('events/'.$s['id']);
        }
        redirect('pay/'.$rid);
    }

    $title = 'رویداد · ' . $s['title'];
    require ROOT . '/includes/header.php';
    ?>
    <section class="page-hero">
      <div class="wrap">
        <div class="crumbs">
          <a href="<?= e(url('events')) ?>">رویدادها</a>
          <span>‹</span>
          <b><?= e($s['cat_name']) ?></b>
        </div>
        <div class="kicker">EVENT</div>
        <h1><?= e($s['title']) ?></h1>
        <p class="sub"><?= e($s['cat_name']) ?> · <?= e(audience_fa($aud)) ?> · <?= jdate_format($s['session_date'].' '.$s['start_time']) ?></p>
      </div>
    </section>
    <section class="section" style="padding-top:0">
      <div class="wrap grid-2">
        <div class="card">
          <img src="<?= e(media($s['cover'], 'img/hero-cafe.jpg')) ?>" alt="" style="height:260px;width:100%;object-fit:cover">
          <div style="padding:18px">
            <p style="line-height:2;color:var(--creamd)"><?= nl2br(e($s['description'])) ?></p>
            <p style="margin-top:10px;color:var(--muted)">مکان: <?= e($s['location']) ?></p>
          </div>
        </div>
        <div class="card" style="padding:22px">
          <h3>رزرو جا</h3>
          <p style="line-height:2;color:var(--creamd);margin:12px 0"><?= e($ch['label']) ?>.</p>
          <div class="gold-line"></div>
          <p>میز: <b><?= e(audience_fa($aud)) ?></b></p>
          <p>قیمت: <b><?= money((int)$ch['price']) ?></b></p>
          <?php if ($ch['now'] > 0): ?>
            <p>الان آنلاین: <b style="color:var(--gold2)"><?= money((int)$ch['now']) ?></b></p>
          <?php endif; ?>
          <?php if ($ch['later'] > 0): ?>
            <p>بقیه در محل: <b><?= money((int)$ch['later']) ?></b></p>
          <?php endif; ?>
          <?php $vnote = trim((string)($s['venue_note'] ?? '')); if ($vnote !== '' && ($ch['type'] === 'cash' || $ch['later'] > 0)): ?>
            <p class="flash warn" style="margin-top:10px"><?= nl2br(e($vnote)) ?></p>
          <?php endif; ?>
          <p>جا باقی: <?= fa_num($left) ?> از <?= fa_num($s['total_seats']) ?></p>
          <?php if ($s['status'] !== 'upcoming'): ?>
            <p class="flash warn">این رویداد دیگر قابل رزرو نیست.</p>
          <?php elseif ($left <= 0): ?>
            <p class="flash err">ظرفیت تکمیل است.</p>
          <?php else: ?>
            <form method="post" style="margin-top:16px">
              <?= csrf_field() ?>
              <button class="btn btn-solid" type="submit"><?= $ch['now'] > 0 ? 'ادامه و پرداخت ' . money((int)$ch['now']) : 'رزرو — پول در محل' ?></button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php require ROOT . '/includes/footer.php'; exit;
}

$catSlug = (string)getv('cat');
$for = (string)getv('for');
$cats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$sql = 'SELECT s.*, c.name AS cat_name, c.slug AS cat_slug,
          (SELECT COUNT(*) FROM reservations r WHERE r.session_id=s.id AND r.payment_status IN ("paid_half","paid_full","venue")) AS reserved
          FROM game_sessions s JOIN categories c ON c.id=s.category_id
          WHERE s.status="upcoming" AND ' . session_has_seat_sql();
$args = [];
if ($catSlug !== '') {
    $sql .= ' AND c.slug=?';
    $args[] = $catSlug;
}
if ($for === 'boys') {
    $sql .= ' AND s.audience IN ("boys","mixed")';
} elseif ($for === 'girls') {
    $sql .= ' AND s.audience IN ("girls","mixed")';
} elseif ($for === 'mixed') {
    $sql .= ' AND s.audience=?';
    $args[] = 'mixed';
}
$sql .= ' ORDER BY s.session_date, s.start_time';
$list = q($sql, $args)->fetchAll();
$title = 'رویدادها';
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">EVENTS</div>
    <h1>رویدادها</h1>
    <p class="sub">بازی را انتخاب کن، میز پسران یا دختران را ببین، جا را با پیش‌پرداخت نگه دار.</p>
    <div class="tabs">
      <a class="tab <?= $catSlug===''?'is-on':'' ?>" href="<?= e(url('events' . ($for ? '?for='.$for : ''))) ?>">همه بازی‌ها</a>
      <?php foreach ($cats as $c):
        $href = 'events?cat=' . urlencode($c['slug']) . ($for ? '&for=' . $for : '');
      ?>
        <a class="tab <?= $catSlug===$c['slug']?'is-on':'' ?>" href="<?= e(url($href)) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <div class="tabs" style="margin-top:8px">
      <?php
        $base = $catSlug !== '' ? 'events?cat=' . urlencode($catSlug) : 'events';
        $join = str_contains($base, '?') ? '&' : '?';
      ?>
      <a class="tab <?= $for===''?'is-on':'' ?>" href="<?= e(url($catSlug!=='' ? 'events?cat='.urlencode($catSlug) : 'events')) ?>">همه میزها</a>
      <a class="tab <?= $for==='boys'?'is-on':'' ?>" href="<?= e(url($base . $join . 'for=boys')) ?>">پسران</a>
      <a class="tab <?= $for==='girls'?'is-on':'' ?>" href="<?= e(url($base . $join . 'for=girls')) ?>">دختران</a>
      <a class="tab <?= $for==='mixed'?'is-on':'' ?>" href="<?= e(url($base . $join . 'for=mixed')) ?>">ترکیبی</a>
    </div>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap grid-3">
    <?php foreach ($list as $s):
      $left = max(0, (int)$s['total_seats']-(int)$s['reserved']);
      $pct = (int)$s['total_seats'] ? (int)round(((int)$s['reserved']/(int)$s['total_seats'])*100) : 0;
    ?>
    <article class="card session-card">
      <img class="cover" src="<?= e(media($s['cover'], 'img/hero-cafe.jpg')) ?>" alt="">
      <div class="body">
        <div class="when"><?= e($s['cat_name']) ?> · <?= e(audience_fa(audience_of($s))) ?> · <?= jdate_format($s['session_date'].' '.$s['start_time']) ?></div>
        <h3><?= e($s['title']) ?></h3>
        <div class="seats"><span>باقی: <?= fa_num($left) ?></span><span><?= money((int)$s['price']) ?></span></div>
        <div class="seat-bar"><span style="width:<?= $pct ?>%"></span></div>
        <div style="margin-top:14px"><a class="btn btn-sm" href="<?= e(url('events/' . (int)$s['id'])) ?>">رزرو جا</a></div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php if (!$list): ?><p class="empty">رویدادی با این فیلتر نیست.</p><?php endif; ?>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
