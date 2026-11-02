<?php
$slug = $param;
$eCats = q("SELECT * FROM categories WHERE kind='electronic' ORDER BY sort_order, id")->fetchAll();

if ($slug) {
    $cat = q("SELECT * FROM categories WHERE slug=? AND kind='electronic'", [$slug])->fetch();
    if (!$cat) {
        flash('x', 'این بازی الکترونیک پیدا نشد.', 'err');
        redirect('egames');
    }
    $sessions = q('SELECT s.*, c.name AS cat_name,
              (SELECT COUNT(*) FROM reservations r WHERE r.session_id=s.id AND r.payment_status IN ("paid_half","paid_full")) AS reserved
              FROM game_sessions s JOIN categories c ON c.id=s.category_id
              WHERE s.category_id=? AND s.status="upcoming" AND s.session_date >= date("now","localtime")
                AND ' . session_has_seat_sql() . '
              ORDER BY s.session_date, s.start_time', [(int)$cat['id']])->fetchAll();
    $title = $cat['name'] . ' · الکترونیک';
    require ROOT . '/includes/header.php';
    ?>
    <section class="page-hero">
      <div class="wrap">
        <div class="crumbs">
          <a href="<?= e(url('egames')) ?>">الکترونیک</a>
          <span>‹</span>
          <b><?= e($cat['name']) ?></b>
        </div>
        <div class="kicker">CONSOLE</div>
        <h1><?= e($cat['name']) ?></h1>
        <p class="sub"><?= e($cat['description']) ?></p>
      </div>
    </section>
    <section class="section" style="padding-top:0">
      <div class="wrap">
        <?php if (!empty($cat['image'])): ?>
          <img class="guide-cover" src="<?= e(media($cat['image'])) ?>" alt="">
        <?php endif; ?>
        <div class="grid-3">
          <?php foreach ($sessions as $s):
            $left = max(0, (int)$s['total_seats'] - (int)$s['reserved']);
          ?>
          <article class="card session-card">
            <img class="cover" src="<?= e(media($s['cover'] ?: $cat['image'], 'img/egame-arcade.png')) ?>" alt="">
            <div class="body">
              <div class="when"><?= jdate_format($s['session_date'] . ' ' . $s['start_time']) ?></div>
              <h3><?= e($s['title']) ?></h3>
              <div class="seats"><span>جا: <?= fa_num($left) ?></span><span><?= money((int)$s['price']) ?></span></div>
              <div style="margin-top:12px"><a class="btn btn-sm btn-solid" href="<?= e(url('events/' . (int)$s['id'])) ?>">ثبت‌نام مسابقه</a></div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php if (!$sessions): ?><p class="empty">هنوز مسابقه‌ای برای این بازی روی تقویم نیست.</p><?php endif; ?>
        <p style="text-align:center;margin-top:22px">
          <a class="btn" href="<?= e(url('leaderboard/' . $cat['slug'])) ?>">لیدربورد <?= e($cat['name']) ?></a>
        </p>
      </div>
    </section>
    <?php
    require ROOT . '/includes/footer.php';
    exit;
}

$title = 'بازی‌های الکترونیک';
$desc = 'مسابقات فیفا، پلی‌استیشن و بازی‌های کنسولی رکاد.';
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">CONSOLE</div>
    <h1>بازی‌های الکترونیک</h1>
    <p class="sub">فیفا، PS و هر عنوان جدیدی که میزبان اضافه کند. برای مسابقه رزرو کن.</p>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap grid-3">
    <?php foreach ($eCats as $c): ?>
      <a class="card cat-card egame-card" href="<?= e(url('egames/' . $c['slug'])) ?>">
        <img src="<?= e(media($c['image'], 'img/egame-arcade.png')) ?>" alt="">
        <div class="body">
          <div class="type">الکترونیک</div>
          <h3><?= e($c['name']) ?></h3>
          <p><?= e($c['description']) ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if (!$eCats): ?><p class="empty">هنوز بازی الکترونیکی ثبت نشده. از پنل، بازی جدید اضافه کن.</p><?php endif; ?>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
