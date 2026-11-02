<?php
$adminTitle = 'داشبورد';
$kpis = [
    ['بازیکنان', (int)q('SELECT COUNT(*) FROM users WHERE role="user"')->fetchColumn()],
    ['نشست پیش‌رو', (int)q('SELECT COUNT(*) FROM game_sessions WHERE status="upcoming"')->fetchColumn()],
    ['رزرو پرداخت‌شده', (int)q('SELECT COUNT(*) FROM reservations WHERE payment_status IN ("paid_half","paid_full")')->fetchColumn()],
    ['آموزش', (int)q('SELECT COUNT(*) FROM blog_posts')->fetchColumn()],
];
$rev = (int)q('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status="success"')->fetchColumn();
$latest = q('SELECT r.*, u.display_name, s.title FROM reservations r JOIN users u ON u.id=r.user_id JOIN game_sessions s ON s.id=r.session_id ORDER BY r.id DESC LIMIT 8')->fetchAll();
?>
<div class="admin-kpis">
  <?php foreach ($kpis as $k): ?>
    <div class="kpi"><span><?= e($k[0]) ?></span><b><?= fa_num($k[1]) ?></b></div>
  <?php endforeach; ?>
  <div class="kpi kpi-wide">مجموع پیش‌پرداخت موفق<b><?= money($rev) ?></b></div>
</div>
<div class="card table-card">
  <h3>آخرین رزروها</h3>
  <div class="admin-table-wrap">
  <table class="data-table">
    <thead><tr><th>بازیکن</th><th>نشست</th><th>وضعیت پرداخت</th><th>حضور</th></tr></thead>
    <tbody>
    <?php foreach ($latest as $r): ?>
      <tr>
        <td><?= e($r['display_name']) ?></td>
        <td><?= e($r['title']) ?></td>
        <td><?= e(status_fa($r['payment_status'])) ?></td>
        <td><?= e(status_fa($r['attendance'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
