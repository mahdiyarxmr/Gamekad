<?php
$catSlug = $param;
$scenSlug = $param2;

if ($catSlug && $scenSlug) {
    $cat = q('SELECT * FROM categories WHERE slug=?', [$catSlug])->fetch();
    $sc = $cat ? q('SELECT * FROM scenarios WHERE category_id=? AND slug=?', [$cat['id'], $scenSlug])->fetch() : null;
    if (!$cat || !$sc) {
        flash('x', 'سناریو پیدا نشد.', 'err');
        redirect('guide');
    }
    $roles = q('SELECT * FROM roles WHERE scenario_id=? ORDER BY id', [(int)$sc['id']])->fetchAll();
    $title = $sc['name'] . ' · ' . $cat['name'];
    require ROOT . '/includes/header.php';
    ?>
    <section class="page-hero">
      <div class="wrap">
        <div class="crumbs">
          <a href="<?= e(url('guide')) ?>">آموزش</a>
          <span>‹</span>
          <a href="<?= e(url('guide/' . $cat['slug'])) ?>"><?= e($cat['name']) ?></a>
          <span>‹</span>
          <b><?= e($sc['name']) ?></b>
        </div>
        <div class="kicker">SCENARIO</div>
        <h1><?= e($cat['name']) ?> — <?= e($sc['name']) ?></h1>
        <p class="sub"><?= e($sc['description']) ?></p>
      </div>
    </section>
    <section class="section" style="padding-top:0">
      <div class="wrap">
        <?php if (!empty($sc['cover'])): ?>
          <img src="<?= e(media($sc['cover'])) ?>" alt="" class="guide-cover">
        <?php endif; ?>
        <div class="prose guide-howto">
          <?php
            $howto = trim((string)($sc['howto'] ?? ''));
            echo $howto !== '' ? html_media($howto) : '<p>هنوز آموزش این سناریو نوشته نشده. از پنل ادمین متن نحوه بازی را اضافه کن.</p>';
          ?>
        </div>
        <?php if ($roles): ?>
          <div class="gold-line" style="margin:36px 0 28px"></div>
          <h2 style="text-align:center;margin-bottom:8px">کارت نقش‌ها</h2>
          <p class="sub">موس را ببر روی کارت تا انتخاب به نظر برسد. بزن تا با انیمیشن بچرخد و توانایی نقش را بخوانی.</p>
          <?php render_role_cards($roles); ?>
        <?php endif; ?>
        <p style="text-align:center;margin-top:28px">
          <a class="btn" href="<?= e(url('guide/' . $cat['slug'])) ?>">سناریوهای دیگر <?= e($cat['name']) ?></a>
        </p>
      </div>
    </section>
    <?php require ROOT . '/includes/footer.php'; exit;
}

if ($catSlug) {
    $cat = q('SELECT * FROM categories WHERE slug=?', [$catSlug])->fetch();
    if (!$cat) {
        flash('x', 'بازی پیدا نشد.', 'err');
        redirect('guide');
    }
    $scenarios = q('SELECT * FROM scenarios WHERE category_id=? ORDER BY id', [$cat['id']])->fetchAll();
    $title = 'آموزش ' . $cat['name'];
    require ROOT . '/includes/header.php';
    ?>
    <section class="page-hero">
      <div class="wrap">
        <div class="crumbs">
          <a href="<?= e(url('guide')) ?>">آموزش</a>
          <span>‹</span>
          <b><?= e($cat['name']) ?></b>
        </div>
        <div class="kicker">SCENARIOS</div>
        <h1>سناریوهای <?= e($cat['name']) ?></h1>
        <p class="sub">یک سناریو را انتخاب کن تا نحوه بازی و نقش‌هایش را ببینی.</p>
      </div>
    </section>
    <section class="section" style="padding-top:0">
      <div class="wrap grid-3">
        <?php foreach ($scenarios as $s): ?>
          <a class="card cat-card" href="<?= e(url('guide/' . $cat['slug'] . '/' . $s['slug'])) ?>">
            <img src="<?= e(media($s['cover'] ?: $cat['image'], 'img/cat-mafia.jpg')) ?>" alt="">
            <div class="body">
              <div class="type" style="font-size:11px;color:var(--gold);letter-spacing:.12em">سناریو</div>
              <h3><?= e($s['name']) ?></h3>
              <p><?= e($s['description']) ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <?php if (!$scenarios): ?><p class="empty">برای این بازی هنوز سناریویی ثبت نشده.</p><?php endif; ?>
    </section>
    <?php require ROOT . '/includes/footer.php'; exit;
}

$scenarios = q('SELECT s.*, c.name AS cat_name, c.slug AS cat_slug, c.image AS cat_image
                FROM scenarios s JOIN categories c ON c.id=s.category_id
                ORDER BY s.id')->fetchAll();
$title = 'آموزش نقش‌ها و سناریوها';
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">ACADEMY</div>
    <h1>آموزش نقش‌ها و سناریوها</h1>
    <p class="sub">اول سناریو را انتخاب کن. داخل هر کدام نحوه بازی و کارت نقش‌های همان سناریو است.</p>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap grid-2">
    <?php foreach ($scenarios as $s): ?>
      <a class="card cat-card" href="<?= e(url('guide/' . $s['cat_slug'] . '/' . $s['slug'])) ?>">
        <img src="<?= e(media($s['cover'] ?: $s['cat_image'], 'img/cat-mafia.jpg')) ?>" alt="">
        <div class="body">
          <div class="type" style="font-size:11px;color:var(--gold)"><?= e($s['cat_name']) ?></div>
          <h3><?= e($s['name']) ?></h3>
          <p><?= e($s['description']) ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if (!$scenarios): ?><p class="empty">هنوز سناریویی ثبت نشده.</p><?php endif; ?>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
