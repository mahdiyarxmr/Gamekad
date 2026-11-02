<?php
$adminTitle = 'تالار قهرمانان';
if (is_post()) {
    csrf_check();
    if ((string)post('act') === 'save') {
        q('UPDATE mvp SET active=0');
        $label = trim((string)post('week_label'));
        setting('mvp_label', $label);
        for ($pos = 1; $pos <= 3; $pos++) {
            $uid = (int)post('user_' . $pos);
            $cid = (int)post('cat_' . $pos) ?: null;
            $titleP = trim((string)post('title_' . $pos));
            if ($uid) {
                q('INSERT INTO mvp (user_id,position,category_id,title,week_label,active) VALUES (?,?,?,?,?,1)',
                    [$uid, $pos, $cid, $titleP !== '' ? $titleP : 'MVP', $label]);
            }
        }
        flash('x','سکو به‌روز شد. خالی‌ها نشان داده نمی‌شوند.','ok');
    }
    redirect('/admin/mvp');
}
$users = q('SELECT id, display_name FROM users ORDER BY display_name')->fetchAll();
$cats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$cur = q('SELECT * FROM mvp WHERE active=1')->fetchAll();
$by = [];
foreach ($cur as $m) $by[(int)$m['position']] = $m;
$label = setting('mvp_label');
?>
<p style="color:var(--creamd);line-height:1.9;margin-bottom:16px">فقط سه نفر روی خانه، مثل طرح اول. می‌توانند از بازی‌های مختلف باشند (مثلاً مافیا، گل یا پوچ، بازپرس). اگر کسی را خالی بگذاری آن صندلی نشان داده نمی‌شود. اگر هیچ‌کس نباشد کل بخش مخفی است.</p>
<form method="post" class="card" style="padding:20px">
  <?= csrf_field() ?><input type="hidden" name="act" value="save">
  <div class="form">
    <label>برچسب هفته</label>
    <input name="week_label" value="<?= e($label) ?>" placeholder="هفته اول شهریور">
  </div>
  <div class="grid-3" style="margin-top:18px">
    <?php foreach ([1=>'اول (وسط)',2=>'دوم (چپ)',3=>'سوم (راست)'] as $pos=>$lab): $m = $by[$pos] ?? null; ?>
    <div class="kpi">
      <b style="font-size:16px">مقام <?= e($lab) ?></b>
      <label>بازیکن</label>
      <select name="user_<?= $pos ?>" style="width:100%;margin:8px 0">
        <option value="0">— خالی —</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= $m && (int)$m['user_id']===(int)$u['id']?'selected':'' ?>><?= e($u['display_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <label>عنوان</label>
      <input name="title_<?= $pos ?>" value="<?= e($m['title'] ?? 'MVP') ?>" style="width:100%;margin:8px 0">
      <label>بازی</label>
      <select name="cat_<?= $pos ?>" style="width:100%;margin:8px 0">
        <option value="0">—</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $m && (int)$m['category_id']===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endforeach; ?>
  </div>
  <button class="btn btn-solid" style="margin-top:16px" type="submit">نمایش روی صفحه اصلی</button>
</form>
