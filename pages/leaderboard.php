<?php
$cats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$slug = $param ?: ($cats[0]['slug'] ?? 'mafia');
$cat = q('SELECT * FROM categories WHERE slug=?', [$slug])->fetch();
if (!$cat) { flash('x','دسته‌ای پیدا نشد.','err'); redirect('leaderboard'); }

$rows = q('SELECT l.*, u.display_name, u.avatar, u.username, u.origin, u.gender
           FROM leaderboard l JOIN users u ON u.id=l.user_id
           WHERE l.category_id=? ORDER BY l.points DESC, l.wins DESC', [$cat['id']])->fetchAll();
$boys = array_values(array_filter($rows, static fn($r) => gender_of($r) !== 'girl'));
$girls = array_values(array_filter($rows, static fn($r) => gender_of($r) === 'girl'));

function public_lb_table(array $rows): void
{
    if (!$rows) {
        echo '<p class="empty">هنوز کسی در این سمت امتیاز ندارد.</p>';
        return;
    }
    echo '<div class="table-scroll"><table class="lb-table">';
    echo '<thead><tr><th>رتبه</th><th>بازیکن</th><th>امتیاز</th><th class="hide-sm">برد</th><th class="hide-sm">باخت</th><th class="hide-sm">نرخ برد</th></tr></thead><tbody>';
    foreach ($rows as $i => $r) {
        $wr = winrate((int)$r['wins'], (int)$r['losses']);
        echo '<tr>';
        echo '<td><span class="rank ' . ($i < 3 ? 'r' . ($i + 1) : '') . '">' . fa_num($i + 1) . '</span></td>';
        echo '<td>' . player_who_html($r, url('player/' . (int)$r['user_id'])) . '</td>';
        echo '<td>' . fa_num($r['points']) . '</td>';
        echo '<td class="hide-sm">' . fa_num($r['wins']) . '</td>';
        echo '<td class="hide-sm">' . fa_num($r['losses']) . '</td>';
        echo '<td class="hide-sm">' . fa_num($wr) . '٪</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

$title = 'لیدربورد ' . $cat['name'];
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">LEADERBOARD</div>
    <h1>لیدربورد <?= e($cat['name']) ?></h1>
    <p class="sub"><?= e($cat['description']) ?></p>
    <div class="tabs">
      <?php foreach ($cats as $c): ?>
        <a class="tab <?= $c['slug']===$cat['slug']?'is-on':'' ?>" href="<?= e(url('leaderboard/' . $c['slug'])) ?>"><?= e($c['name']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap lb-split">
    <div class="card" style="padding:8px 12px">
      <h3 style="padding:12px 8px 4px">پسران</h3>
      <?php public_lb_table($boys); ?>
    </div>
    <div class="card" style="padding:8px 12px">
      <h3 style="padding:12px 8px 4px">دختران</h3>
      <?php public_lb_table($girls); ?>
    </div>
  </div>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
