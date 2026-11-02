<?php
$adminTitle = 'آمار نقش بازیکنان';
if (is_post()) {
    csrf_check();
    $act = (string)post('act');
    if ($act === 'add') {
        q('INSERT INTO player_stats (user_id,category_id,scenario_id,role_id,result,session_id,notes,created_at)
           VALUES (?,?,?,?,?,?,?,datetime("now"))',
            [(int)post('user_id'), (int)post('category_id'), (int)post('scenario_id') ?: null,
             (int)post('role_id') ?: null, (string)post('result'), (int)post('session_id') ?: null,
             trim((string)post('notes'))]);
        flash('x','نتیجه بازی ثبت شد.','ok');
    }
    if ($act === 'del') {
        q('DELETE FROM player_stats WHERE id=?', [(int)post('id')]);
        flash('x','حذف شد.','ok');
    }
    redirect('/admin/stats');
}
$users = q('SELECT id, display_name FROM users WHERE role="user" ORDER BY display_name')->fetchAll();
$cats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$roles = q('SELECT r.*, c.name AS cat, sc.name AS scen
            FROM roles r JOIN categories c ON c.id=r.category_id
            LEFT JOIN scenarios sc ON sc.id=r.scenario_id
            ORDER BY sc.id, r.id')->fetchAll();
$scens = q('SELECT s.*, c.name AS cat FROM scenarios s JOIN categories c ON c.id=s.category_id ORDER BY c.sort_order, s.id')->fetchAll();
$sess = q('SELECT id, title, session_date FROM game_sessions ORDER BY session_date DESC LIMIT 30')->fetchAll();
$recent = q('SELECT s.*, u.display_name, c.name AS cat, r.name AS role_name, sc.name AS scen_name
             FROM player_stats s
             JOIN users u ON u.id=s.user_id
             JOIN categories c ON c.id=s.category_id
             LEFT JOIN roles r ON r.id=s.role_id
             LEFT JOIN scenarios sc ON sc.id=s.scenario_id
             ORDER BY s.id DESC LIMIT 40')->fetchAll();
?>
<div class="card" style="padding:18px;margin-bottom:18px">
  <h3>ثبت نتیجه یک بازی برای بازیکن</h3>
  <p style="color:var(--muted);font-size:13px;margin:8px 0 12px">برای ثبت کل میز با امتیاز، از «بازی‌های اخیر» استفاده کن. اینجا فقط نقش تکی برای نمودار پروفایل است.</p>
  <form class="form" method="post">
    <?= csrf_field() ?><input type="hidden" name="act" value="add">
    <div class="grid-2">
      <div><label>بازیکن</label>
        <select name="user_id"><?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= e($u['display_name']) ?></option><?php endforeach; ?></select></div>
      <div><label>بازی</label>
        <select name="category_id" id="catSel">
          <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div><label>سناریو</label>
        <select name="scenario_id">
          <option value="0">—</option>
          <?php foreach ($scens as $s): ?><option value="<?= (int)$s['id'] ?>" data-cat="<?= (int)$s['category_id'] ?>"><?= e($s['cat']) ?> / <?= e($s['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div><label>نقش</label>
        <select name="role_id">
          <option value="0">بدون نقش</option>
          <?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>" data-cat="<?= (int)$r['category_id'] ?>" data-scen="<?= (int)$r['scenario_id'] ?>"><?= e(($r['scen'] ?: $r['cat']) . ' / ' . $r['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div><label>نتیجه</label>
        <select name="result"><option value="win">برد</option><option value="loss">باخت</option></select></div>
      <div><label>نشست (اختیاری)</label>
        <select name="session_id">
          <option value="0">—</option>
          <?php foreach ($sess as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['title']) ?> · <?= jdate_format($s['session_date'], 'short') ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <label>یادداشت</label><input name="notes" placeholder="مثلاً استعلام درست شب دوم">
    <button class="btn btn-solid" type="submit">ثبت آمار</button>
  </form>
</div>
<div class="admin-table-wrap">
<table class="data-table">
  <thead><tr><th>بازیکن</th><th>بازی</th><th>سناریو</th><th>نقش</th><th>نتیجه</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($recent as $s): ?>
    <tr>
      <td><?= e($s['display_name']) ?></td>
      <td><?= e($s['cat']) ?></td>
      <td><?= e($s['scen_name'] ?? '—') ?></td>
      <td><?= e($s['role_name'] ?? '—') ?></td>
      <td><?= e(status_fa($s['result'])) ?></td>
      <td>
        <form method="post"><?= csrf_field() ?>
          <input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <button class="btn btn-sm btn-danger" data-confirm="حذف؟">حذف</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<script>
(function () {
  const cat = document.getElementById('catSel');
  const scen = document.querySelector('select[name="scenario_id"]');
  const role = document.querySelector('select[name="role_id"]');
  if (!cat || !scen || !role) return;
  const filter = () => {
    const c = String(cat.value);
    const s = String(scen.value);
    [...scen.options].forEach((o) => {
      if (!o.value || o.value === '0') return;
      o.hidden = !!(o.dataset.cat && o.dataset.cat !== c);
    });
    [...role.options].forEach((o) => {
      if (!o.value || o.value === '0') return;
      const byCat = o.dataset.cat && o.dataset.cat !== c;
      const byScen = s && s !== '0' && o.dataset.scen && o.dataset.scen !== s;
      o.hidden = !!(byCat || byScen);
    });
  };
  cat.addEventListener('change', filter);
  scen.addEventListener('change', filter);
  filter();
})();
</script>
