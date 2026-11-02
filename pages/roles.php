<?php
$title = 'نقش‌های مافیا';
$desc = 'کارت نقش‌های هر سناریو جداست؛ اول سناریو را انتخاب کن.';
$cats = q('SELECT * FROM categories WHERE has_roles=1 ORDER BY sort_order')->fetchAll();
$slug = $param ?: ($cats[0]['slug'] ?? 'mafia');
$cat = q('SELECT * FROM categories WHERE slug=?', [$slug])->fetch();
if (!$cat) {
    flash('x', 'دسته‌ای پیدا نشد.', 'err');
    redirect('guide');
}
$scenarios = q('SELECT * FROM scenarios WHERE category_id=? ORDER BY id', [$cat['id']])->fetchAll();
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">ROLES</div>
    <h1>نقش‌های <?= e($cat['name']) ?></h1>
    <p class="sub">هر سناریو نقش‌های خودش را دارد. سناریو را بزن تا فقط کارت‌های همان میز را ببینی.</p>
    <div class="tabs">
      <?php foreach ($cats as $c): ?>
        <a class="tab <?= $c['slug']===$cat['slug']?'is-on':'' ?>" href="<?= e(url('roles/' . $c['slug'])) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap grid-2">
    <?php foreach ($scenarios as $s): ?>
      <a class="card cat-card" href="<?= e(url('guide/' . $cat['slug'] . '/' . $s['slug'])) ?>">
        <img src="<?= e(media($s['cover'] ?: $cat['image'], 'img/cat-mafia.jpg')) ?>" alt="">
        <div class="body">
          <div class="type" style="font-size:11px;color:var(--accent-text)">سناریو</div>
          <h3><?= e($s['name']) ?></h3>
          <p><?= e($s['description']) ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if (!$scenarios): ?>
    <p class="empty">برای این بازی هنوز سناریویی ثبت نشده.</p>
  <?php endif; ?>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
