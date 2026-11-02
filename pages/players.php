<?php
$title = 'بازیکنان گیم‌کاد';
$qstr = trim((string)getv('q'));
$sql = 'SELECT * FROM users WHERE role != "admin"';
$args = [];
if ($qstr !== '') {
    $sql .= ' AND (display_name LIKE ? OR username LIKE ?)';
    $args = ['%'.$qstr.'%', '%'.$qstr.'%'];
}
$sql .= ' ORDER BY created_at DESC';
$users = q($sql, $args)->fetchAll();
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">PLAYERS</div>
    <h1>پروفایل بازیکنان</h1>
    <p class="sub">حساب بساز، آمار نقش‌هایت را ببین، صندلی رزرو کن.</p>
    <form method="get" class="inline-form" style="justify-content:center">
      <input name="q" value="<?= e($qstr) ?>" placeholder="جستجوی نام...">
      <button class="btn btn-sm" type="submit">جستجو</button>
    </form>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap grid-4">
    <?php foreach ($users as $p): ?>
      <a class="card" href="<?= e(url('player/' . (int)$p['id'])) ?>" style="padding:22px;text-align:center;color:inherit">
        <img src="<?= e(media($p['avatar'])) ?>" alt="" style="width:92px;height:92px;border-radius:50%;margin:0 auto 12px;border:1px solid var(--gold);object-fit:cover">
        <h3><?= e($p['display_name']) ?></h3>
        <p style="color:var(--muted);font-size:12px;direction:ltr">@<?= e($p['username']) ?></p>
        <p style="color:var(--creamd);font-size:13px;margin-top:8px;line-height:1.7"><?= e(excerpt($p['bio'], 70)) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
