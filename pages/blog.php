<?php
if ($param) {
    $p = q('SELECT p.*, c.name AS cat_name, u.display_name AS author
            FROM blog_posts p LEFT JOIN categories c ON c.id=p.category_id
            LEFT JOIN users u ON u.id=p.author_id WHERE p.slug=? AND p.published=1', [$param])->fetch();
    if (!$p) { flash('x','مطلب پیدا نشد.','err'); redirect('/blog'); }
    $title = $p['title'];
    require ROOT . '/includes/header.php';
    ?>
    <section class="page-hero">
      <div class="wrap">
        <div class="kicker"><?= e(status_fa($p['type'])) ?></div>
        <h1><?= e($p['title']) ?></h1>
        <p class="sub"><?= $p['cat_name'] ? e($p['cat_name']).' · ' : '' ?><?= jdate_format($p['created_at'], 'date') ?> · <?= e($p['author'] ?? '') ?></p>
      </div>
    </section>
    <section class="section" style="padding-top:0">
      <div class="wrap prose">
        <?php if ($p['cover']): ?><img src="<?= e(media($p['cover'])) ?>" alt=""><?php endif; ?>
        <?php if ($p['video_url']): ?>
          <?php
            $vu = $p['video_url'];
            $embed = $vu;
            if (preg_match('~(?:youtu\.be/|watch\?v=)([A-Za-z0-9_-]+)~', $vu, $m)) {
                $embed = 'https://www.youtube.com/embed/' . $m[1];
            }
          ?>
          <div style="position:relative;padding-top:56%;margin:16px 0;border-radius:12px;overflow:hidden;border:1px solid var(--line)">
            <iframe src="<?= e($embed) ?>" style="position:absolute;inset:0;width:100%;height:100%;border:0" allowfullscreen></iframe>
          </div>
        <?php endif; ?>
        <?= html_media($p['content']) ?>
      </div>
    </section>
    <?php require ROOT . '/includes/footer.php'; exit;
}

$type = (string)getv('type');
$cat = (string)getv('cat');
$sql = 'SELECT p.*, c.name AS cat_name FROM blog_posts p LEFT JOIN categories c ON c.id=p.category_id WHERE p.published=1 AND p.type!="gallery"';
$args = [];
if ($type !== '' && $type !== 'gallery') { $sql .= ' AND p.type=?'; $args[] = $type; }
if ($cat !== '') { $sql .= ' AND c.slug=?'; $args[] = $cat; }
$sql .= ' ORDER BY p.created_at DESC';
$posts = q($sql, $args)->fetchAll();
$cats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$title = 'آموزش';
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">JOURNAL</div>
    <h1>آموزش میز</h1>
    <p class="sub">نکته و سناریو. گالری و نظرات دیگر روی سایت نیستند.</p>
    <div class="tabs">
      <a class="tab <?= $type===''?'is-on':'' ?>" href="<?= e(url('blog')) ?>">همه</a>
      <a class="tab <?= $type==='tutorial'?'is-on':'' ?>" href="<?= e(url('blog?type=tutorial')) ?>">آموزش</a>
      <a class="tab <?= $type==='scenario'?'is-on':'' ?>" href="<?= e(url('blog?type=scenario')) ?>">سناریو</a>
      <a class="tab <?= $type==='news'?'is-on':'' ?>" href="<?= e(url('blog?type=news')) ?>">خبر</a>
      <?php foreach ($cats as $c): ?>
        <a class="tab <?= $cat===$c['slug']?'is-on':'' ?>" href="<?= e(url('blog?cat=' . $c['slug'])) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap grid-3">
    <?php foreach ($posts as $p): ?>
    <a class="card blog-card" href="<?= e(url('blog/' . $p['slug'])) ?>">
      <img src="<?= e(media($p['cover'], 'img/hero-cafe.jpg')) ?>" alt="">
      <div class="body">
        <div class="type"><?= e(status_fa($p['type'])) ?><?= $p['cat_name']?' · '.e($p['cat_name']):'' ?></div>
        <h3><?= e($p['title']) ?></h3>
        <p><?= e($p['excerpt']) ?></p>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if (!$posts): ?><p class="empty">مطلبی نیست.</p><?php endif; ?>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
