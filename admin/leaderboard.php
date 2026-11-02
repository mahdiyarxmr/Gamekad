<?php
$adminTitle = 'لیدربورد';
$cats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$cid = (int)(post('category_id') ?: getv('cat') ?: ($cats[0]['id'] ?? 0));
$split = is_admin();

if (is_post()) {
    csrf_check();
    $act = (string)post('act');
    $cid = (int)post('category_id') ?: $cid;
    if ($act === 'save') {
        $ids = $_POST['uid'] ?? [];
        $pts = $_POST['points'] ?? [];
        $wins = $_POST['wins'] ?? [];
        $losses = $_POST['losses'] ?? [];
        foreach ($ids as $i => $uid) {
            $uid = (int)$uid;
            q('INSERT INTO leaderboard (category_id,user_id,points,wins,losses) VALUES (?,?,?,?,?)
               ON CONFLICT(category_id,user_id) DO UPDATE SET points=excluded.points, wins=excluded.wins, losses=excluded.losses',
                [$cid, $uid, (int)($pts[$i] ?? 0), (int)($wins[$i] ?? 0), (int)($losses[$i] ?? 0)]);
        }
        flash('x','لیدربورد ذخیره شد.','ok');
    }
    if ($act === 'add') {
        $uid = (int)post('user_id');
        if ($uid) {
            q('INSERT INTO leaderboard (category_id,user_id,points,wins,losses) VALUES (?,?,?,?,?)
               ON CONFLICT(category_id,user_id) DO UPDATE SET points=excluded.points, wins=excluded.wins, losses=excluded.losses',
                [$cid, $uid, (int)post('points'), (int)post('wins'), (int)post('losses')]);
            flash('x','بازیکن به لیدربورد اضافه شد.','ok');
        }
    }
    if ($act === 'del') {
        q('DELETE FROM leaderboard WHERE id=?', [(int)post('id')]);
        flash('x','حذف شد.','ok');
    }
    $go = 'list';
    if ($act === 'add') {
        $go = 'add';
    }
    redirect('admin/leaderboard?cat=' . $cid . '&tab=' . $go);
}

$rows = q('SELECT l.*, u.display_name, u.avatar, u.origin FROM leaderboard l JOIN users u ON u.id=l.user_id
           WHERE l.category_id=? ORDER BY l.points DESC, l.wins DESC', [$cid])->fetchAll();
$users = q('SELECT id, display_name, origin FROM users WHERE role="user" ORDER BY display_name')->fetchAll();
$rokad = array_values(array_filter($rows, static fn($r) => origin_of($r) === 'rokad'));
$guest = array_values(array_filter($rows, static fn($r) => origin_of($r) === 'guest'));
$catName = '';
foreach ($cats as $c) {
    if ((int)$c['id'] === $cid) $catName = (string)$c['name'];
}

function admin_lb_table(array $rows, bool $edit, string $empty): void
{
    if (!$rows) {
        echo '<p class="empty">' . e($empty) . '</p>';
        return;
    }
    echo '<div class="admin-table-wrap' . ($edit ? ' lb-edit' : '') . '"><table class="data-table lb-table">';
    echo '<thead><tr><th>رتبه</th><th>بازیکن</th>';
    if ($edit) echo '<th>از</th>';
    echo '<th>امتیاز</th><th>برد</th><th>باخت</th>';
    if ($edit) echo '<th>عملیات</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $i => $r) {
        echo '<tr>';
        echo '<td><span class="rank ' . ($i < 3 ? 'r' . ($i + 1) : '') . '">' . fa_num($i + 1) . '</span></td>';
        echo '<td class="who"><img src="' . e(media($r['avatar'])) . '" alt=""><span>' . e($r['display_name']) . '</span>';
        if ($edit) echo '<input type="hidden" name="uid[]" value="' . (int)$r['user_id'] . '">';
        echo '</td>';
        if ($edit) echo '<td><span class="pill">' . e(origin_fa(origin_of($r))) . '</span></td>';
        if ($edit) {
            echo '<td><input class="lb-num" name="points[]" type="number" min="0" placeholder="امتیاز" value="' . (int)$r['points'] . '"></td>';
            echo '<td><input class="lb-num" name="wins[]" type="number" min="0" placeholder="برد" value="' . (int)$r['wins'] . '"></td>';
            echo '<td><input class="lb-num" name="losses[]" type="number" min="0" placeholder="باخت" value="' . (int)$r['losses'] . '"></td>';
            echo '<td><button class="btn btn-sm btn-danger" form="del' . (int)$r['id'] . '" data-confirm="حذف از لیدربورد؟">حذف</button></td>';
        } else {
            echo '<td>' . fa_num($r['points']) . '</td>';
            echo '<td>' . fa_num($r['wins']) . '</td>';
            echo '<td>' . fa_num($r['losses']) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
?>
<?php $tab = admin_current_tab('list'); ?>
<div class="tabs" style="justify-content:flex-start">
  <?php foreach ($cats as $c): ?>
    <a class="tab <?= $cid===(int)$c['id']?'is-on':'' ?>" href="<?= e(url('admin/leaderboard?cat=' . (int)$c['id'] . '&tab=' . $tab)) ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($split): ?>
  <p style="color:var(--muted);line-height:1.9;margin:0 0 16px">سایت عمومی همان لیدربورد کلی را نشان می‌دهد. این دو بخش فقط برای ادمین است تا رکادی‌ها و بازیکن‌های خارج را جدا ببینی و بعداً نصف برتر را مسابقه بدهی.</p>
<?php endif; ?>

<?php
admin_tabs('admin/leaderboard', ['add' => 'افزودن بازیکن', 'list' => 'جدول امتیاز'], $tab);
?>
<div class="admin-pane"<?= $tab === 'add' ? '' : ' hidden' ?>>
<div class="card" style="padding:16px;margin-bottom:16px">
  <h3>افزودن بازیکن به این دسته</h3>
  <form method="post" class="form lb-add-form" style="margin-top:10px">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="add">
    <input type="hidden" name="category_id" value="<?= $cid ?>">
    <label>بازیکن</label>
    <select name="user_id"><?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= e($u['display_name']) ?> · <?= e(origin_fa(origin_of($u))) ?></option><?php endforeach; ?></select>
    <div class="lb-add-nums">
      <div><label>امتیاز</label><input name="points" type="number" min="0" placeholder="مثلاً ۹۰۰" inputmode="numeric"></div>
      <div><label>برد</label><input name="wins" type="number" min="0" placeholder="مثلاً ۱۶" inputmode="numeric"></div>
      <div><label>باخت</label><input name="losses" type="number" min="0" placeholder="مثلاً ۷" inputmode="numeric"></div>
    </div>
    <button class="btn btn-solid" type="submit">افزودن به جدول</button>
  </form>
</div>
</div>

<div class="admin-pane"<?= $tab === 'list' ? '' : ' hidden' ?>>
<form method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="act" value="save">
  <input type="hidden" name="category_id" value="<?= $cid ?>">
  <div class="card" style="padding:16px;margin-bottom:16px">
    <h3>لیدربورد کلی (همان که روی سایت دیده می‌شود)</h3>
    <?php admin_lb_table($rows, true, 'هنوز امتیازی برای این دسته ثبت نشده.'); ?>
    <button class="btn btn-solid" style="margin-top:12px" type="submit">ذخیره همه</button>
  </div>
</form>
<?php foreach ($rows as $r): ?>
<form id="del<?= (int)$r['id'] ?>" method="post" style="display:none">
  <?= csrf_field() ?><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
  <input type="hidden" name="category_id" value="<?= $cid ?>">
</form>
<?php endforeach; ?>

<?php if ($split): ?>
<div class="lb-split">
  <div class="card" style="padding:16px">
    <h3>رکاد</h3>
    <p class="sub" style="margin:6px 0 12px;text-align:right">هنرستان — <?= fa_num(count($rokad)) ?> نفر</p>
    <?php admin_lb_table($rokad, false, 'هنوز بازیکن رکادی در این دسته نیست. از کاربران، «رکاد» را علامت بزن.'); ?>
  </div>
  <div class="card" style="padding:16px">
    <h3>خارج از رکاد</h3>
    <p class="sub" style="margin:6px 0 12px;text-align:right">مهمان میز — <?= fa_num(count($guest)) ?> نفر</p>
    <?php admin_lb_table($guest, false, 'هنوز بازیکن خارج از رکاد در این دسته نیست.'); ?>
  </div>
</div>

<?php
  $topR = lb_top_half($rokad);
  $topG = lb_top_half($guest);
  $nMatch = min(count($topR), count($topG));
?>
<div class="card" style="padding:16px;margin-top:16px">
  <h3>مسابقهٔ بعدی · نصف برتر <?= e($catName) ?></h3>
  <p style="color:var(--muted);line-height:1.8;margin:8px 0 14px">نصف بالای هر لیدربورد. اگر تعداد برابر نباشد، به اندازهٔ سمت کوچک‌تر چیده می‌شود.</p>
  <?php if ($nMatch < 1): ?>
    <p class="empty">برای چیدن مسابقه، در هر دو سمت حداقل یک نفر امتیاز داشته باشد.</p>
  <?php else: ?>
    <div class="lb-split">
      <div>
        <b>رکاد · <?= fa_num($nMatch) ?> نفر</b>
        <ol class="match-list">
          <?php for ($i = 0; $i < $nMatch; $i++): $p = $topR[$i]; ?>
            <li><?= e($p['display_name']) ?> <span><?= fa_num($p['points']) ?></span></li>
          <?php endfor; ?>
        </ol>
      </div>
      <div>
        <b>خارج از رکاد · <?= fa_num($nMatch) ?> نفر</b>
        <ol class="match-list">
          <?php for ($i = 0; $i < $nMatch; $i++): $p = $topG[$i]; ?>
            <li><?= e($p['display_name']) ?> <span><?= fa_num($p['points']) ?></span></li>
          <?php endfor; ?>
        </ol>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>
</div>
