<?php
$adminTitle = 'رزرو و پرداخت';
$cats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$cid = (int)(post('category_id') ?: getv('cat') ?: 0);

if (is_post()) {
    csrf_check();
    $id = (int)post('id');
    $act = (string)post('act');
    $back = 'admin/reservations' . ($cid ? ('?cat=' . $cid) : '');
    $r = $id ? q('SELECT r.*, s.price, s.title FROM reservations r JOIN game_sessions s ON s.id=r.session_id WHERE r.id=?', [$id])->fetch() : null;

    if ($act === 'attend' && $r) {
        q('UPDATE reservations SET attendance="attended" WHERE id=?', [$id]);
        flash('x', 'حضور ثبت شد.', 'ok');
    }

    if ($act === 'noshow' && $r) {
        $paid = (int)$r['amount_paid'];
        $pay = (string)$r['payment_status'];
        $keepPct = noshow_keep_percent();
        if ($pay === 'paid_full' && $paid > 0) {
            $keep = (int)round($paid * $keepPct / 100);
            $backAmt = max(0, $paid - $keep);
            if ($backAmt > 0) {
                wallet_credit((int)$r['user_id'], $backAmt, 'غیبت · برگشت از «' . $r['title'] . '»', 'refund');
            }
            q('UPDATE reservations SET attendance="no_show", payment_status="forfeited" WHERE id=?', [$id]);
            flash('x', 'غایب. ' . money($backAmt) . ' به کیف برگشت · ' . money($keep) . ' ضبط شد (' . fa_num($keepPct) . '٪).', 'warn');
        } else {
            q('UPDATE reservations SET attendance="no_show", payment_status="forfeited" WHERE id=?', [$id]);
            flash('x', 'غیبت ثبت شد. مبلغ پرداخت‌شده ضبط شد.', 'warn');
        }
    }

    if ($act === 'full' && $r) {
        if ((string)$r['payment_status'] === 'paid_full') {
            flash('x', 'این رزرو از قبل تسویه کامل است.', 'warn');
        } else {
            $due = max(0, (int)$r['price'] - (int)$r['amount_paid']);
            q('UPDATE reservations SET payment_status="paid_full", attendance="attended", amount_paid=? WHERE id=?', [(int)$r['price'], $id]);
            if ($due > 0) {
                q('INSERT INTO payments (reservation_id,user_id,amount,type,status,authority,created_at) VALUES (?,?,?,"remainder","success",?,datetime("now"))',
                    [$id, $r['user_id'], $due, 'CASH-' . time()]);
            }
            flash('x', 'تسویه کامل ثبت شد.', 'ok');
        }
    }

    if ($act === 'refund' && $r) {
        $pay = (string)$r['payment_status'];
        if (in_array($pay, ['refunded', 'forfeited'], true)) {
            flash('x', 'این رزرو قبلاً برگشت یا ضبط شده.', 'warn');
        } else {
            $amt = (int)$r['amount_paid'];
            if ($amt > 0) {
                wallet_credit((int)$r['user_id'], $amt, 'بازگشت وجه «' . $r['title'] . '»', 'refund');
            }
            q('UPDATE reservations SET payment_status="refunded", attendance="pending" WHERE id=?', [$id]);
            flash('x', $amt > 0 ? (money($amt) . ' به کیف پول نشست.') : 'بازگشت وجه ثبت شد.', 'ok');
        }
    }
    redirect($back);
}

$sql = 'SELECT r.*, u.display_name, u.phone, s.title, s.session_date, s.start_time, s.price, s.category_id, c.name AS cat_name
        FROM reservations r
        JOIN users u ON u.id=r.user_id
        JOIN game_sessions s ON s.id=r.session_id
        JOIN categories c ON c.id=s.category_id';
$args = [];
if ($cid) {
    $sql .= ' WHERE s.category_id=?';
    $args[] = $cid;
}
$sql .= ' ORDER BY s.session_date DESC, r.id DESC';
$rows = q($sql, $args)->fetchAll();
$keepPct = noshow_keep_percent();
?>
<div class="tabs" style="justify-content:flex-start;margin-bottom:14px">
  <a class="tab <?= !$cid ? 'is-on' : '' ?>" href="<?= e(url('admin/reservations')) ?>">همه</a>
  <?php foreach ($cats as $c): ?>
    <a class="tab <?= $cid === (int)$c['id'] ? 'is-on' : '' ?>" href="<?= e(url('admin/reservations?cat=' . (int)$c['id'])) ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</div>
<p class="sub" style="text-align:right;margin:0 0 14px">غایبِ تسویه کامل: <?= fa_num($keepPct) ?>٪ ضبط، بقیه به کیف. درصد را از تنظیمات عوض کن.</p>
<div class="admin-table-wrap">
<table class="data-table">
  <thead><tr><th>بازیکن</th><th>نشست</th><th>پرداخت</th><th>حضور</th><th>عملیات</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <?php $pay = (string)$r['payment_status']; ?>
    <tr>
      <td><?= e($r['display_name']) ?><br><span style="color:var(--muted);font-size:12px"><?= e($r['phone']) ?></span></td>
      <td><?= e($r['title']) ?><br><span style="color:var(--muted);font-size:12px"><?= e($r['cat_name']) ?> · <?= jdate_format($r['session_date'].' '.$r['start_time']) ?></span></td>
      <td><?= e(status_fa($pay)) ?><br><span style="color:var(--gold)"><?= money((int)$r['amount_paid']) ?> / <?= money((int)$r['price']) ?></span></td>
      <td><?= e(status_fa($r['attendance'])) ?></td>
      <td>
        <form method="post" class="inline-form">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <?php if ($cid): ?><input type="hidden" name="category_id" value="<?= $cid ?>"><?php endif; ?>
          <button class="btn btn-sm btn-ok" name="act" value="attend">حاضر</button>
          <?php if ($pay !== 'paid_full'): ?>
            <button class="btn btn-sm btn-solid" name="act" value="full">تسویه کامل</button>
          <?php endif; ?>
          <button class="btn btn-sm btn-danger" name="act" value="noshow" data-confirm="غایب شود؟ اگر تسویه کامل باشد بخشی به کیف برمی‌گردد.">غایب</button>
          <?php if (!in_array($pay, ['refunded', 'forfeited'], true)): ?>
            <button class="btn btn-sm btn-ghost" name="act" value="refund" data-confirm="مبلغ پرداخت‌شده به کیف پول برگردد؟">بازگشت وجه</button>
          <?php endif; ?>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php if (!$rows): ?>
  <p class="empty">رزروی در این دسته نیست.</p>
<?php endif; ?>
