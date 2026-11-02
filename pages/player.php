<?php
$id = (int)$param;
$p = q('SELECT * FROM users WHERE id=?', [$id])->fetch();
if (!$p) { http_response_code(404); $title='پیدا نشد'; require ROOT.'/includes/header.php'; echo '<section class="page-hero"><h1>بازیکن پیدا نشد</h1></section>'; require ROOT.'/includes/footer.php'; exit; }

$boards = q('SELECT l.*, c.name, c.slug, c.has_roles FROM leaderboard l JOIN categories c ON c.id=l.category_id WHERE l.user_id=? ORDER BY l.points DESC', [$id])->fetchAll();

$scenId = (int)getv('scenario');
$catId = (int)(getv('cat') ?: 0);
if (!$catId) {
    $first = q('SELECT DISTINCT category_id FROM player_stats WHERE user_id=? LIMIT 1', [$id])->fetch();
    $catId = $first ? (int)$first['category_id'] : (int)($boards[0]['category_id'] ?? 0);
}
$statCats = q('SELECT DISTINCT c.* FROM player_stats s JOIN categories c ON c.id=s.category_id WHERE s.user_id=?', [$id])->fetchAll();
if (!$statCats && $boards) {
    foreach ($boards as $b) {
        $statCats[] = ['id' => $b['category_id'], 'name' => $b['name'], 'has_roles' => $b['has_roles'] ?? 0];
    }
}
$scenarios = $catId ? q('SELECT * FROM scenarios WHERE category_id=?', [$catId])->fetchAll() : [];
if (!$scenId && $catId) {
    $pick = q('SELECT scenario_id FROM player_stats WHERE user_id=? AND category_id=? AND scenario_id IS NOT NULL GROUP BY scenario_id ORDER BY COUNT(*) DESC', [$id, $catId])->fetch();
    $scenId = (int)($pick['scenario_id'] ?? ($scenarios[0]['id'] ?? 0));
}

$where = 'user_id=? AND category_id=?';
$args = [$id, $catId];
if ($scenId) { $where .= ' AND scenario_id=?'; $args[] = $scenId; }

$roleAgg = [];
if ($scenId) {
    $roleAgg = q("SELECT r.name, r.color,
        COALESCE(SUM(CASE WHEN s.result='win' THEN 1 ELSE 0 END),0) AS wins,
        COALESCE(SUM(CASE WHEN s.result='loss' THEN 1 ELSE 0 END),0) AS losses
        FROM roles r
        LEFT JOIN player_stats s ON s.role_id=r.id AND s.user_id=?
        WHERE r.scenario_id=?
        GROUP BY r.id ORDER BY r.id", [$id, $scenId])->fetchAll();
}

$totals = $catId ? q("SELECT
    SUM(CASE WHEN result='win' THEN 1 ELSE 0 END) AS wins,
    SUM(CASE WHEN result='loss' THEN 1 ELSE 0 END) AS losses
    FROM player_stats WHERE $where", $args)->fetch() : ['wins'=>0,'losses'=>0];

$labels = [];
$values = [];
foreach ($boards as $b) {
    $labels[] = (string)$b['name'];
    $values[] = winrate((int)$b['wins'], (int)$b['losses']);
}

$recent = q('SELECT mp.result, mp.points, m.title, m.created_at, m.winner, c.name AS cat_name, r.name AS role_name
             FROM match_players mp
             JOIN matches m ON m.id=mp.match_id
             JOIN categories c ON c.id=m.category_id
             LEFT JOIN roles r ON r.id=mp.role_id
             WHERE mp.user_id=? ORDER BY m.id DESC LIMIT 8', [$id])->fetchAll();

$pane = (string)getv('view') ?: 'score';
if (!in_array($pane, ['score', 'medal', 'radar', 'roles'], true)) {
    $pane = 'score';
}
if ($pane === 'roles' && $catId) {
    $pane = 'roles';
}

$title = $p['display_name'];
require ROOT . '/includes/header.php';
$base = 'player/' . $id;
?>
<section class="section profile-page">
  <div class="wrap">
    <div class="profile-sheet">
      <header class="profile-top">
        <img class="av" src="<?= e(media($p['avatar'])) ?>" alt="">
        <div>
          <div class="kicker">PLAYER</div>
          <h1><?= e($p['display_name']) ?><?= rokad_mark_html($p) ?></h1>
          <p class="profile-meta">@<?= e($p['username']) ?> · <?= e(gender_fa(gender_of($p))) ?><?= origin_of($p)==='rokad' ? ' · رکاد' : '' ?></p>
          <?php if (trim((string)$p['bio']) !== ''): ?>
            <p class="profile-bio"><?= e($p['bio']) ?></p>
          <?php endif; ?>
        </div>
      </header>

      <nav class="profile-seg" role="tablist">
        <a class="profile-seg-btn <?= $pane==='score'?'is-on':'' ?>" href="<?= e(url($base)) ?>">امتیاز</a>
        <a class="profile-seg-btn <?= $pane==='medal'?'is-on':'' ?>" href="<?= e(url($base . '?view=medal')) ?>">مدال</a>
        <a class="profile-seg-btn <?= $pane==='radar'?'is-on':'' ?>" href="<?= e(url($base . '?view=radar')) ?>">نمودار</a>
        <a class="profile-seg-btn <?= $pane==='roles'?'is-on':'' ?>" href="<?= e(url($base . '?view=roles' . ($catId ? '&cat=' . $catId : ''))) ?>">نقش</a>
      </nav>

      <div class="profile-pane">
      <?php if ($pane === 'score'): ?>
        <?php if ($boards): ?>
          <div class="score-menu">
            <?php foreach ($boards as $b): ?>
              <a class="score-card" href="<?= e(url($base . '?view=roles&cat=' . (int)$b['category_id'])) ?>">
                <b><?= e($b['name']) ?></b>
                <strong><?= fa_num($b['points']) ?></strong>
                <small><?= fa_num($b['wins']) ?> برد · <?= fa_num($b['losses']) ?> باخت</small>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="empty">هنوز امتیازی روی لیدربورد ندارد.</p>
        <?php endif; ?>
        <?php if ($recent): ?>
          <h3 class="profile-mini">آخرین میزها</h3>
          <div class="recent-people">
            <?php foreach ($recent as $g): ?>
              <div class="recent-chip <?= $g['result']==='win'?'is-win':'is-loss' ?>">
                <span>
                  <?= e($g['cat_name']) ?>
                  <?php if ($g['role_name']): ?><i><?= e($g['role_name']) ?></i><?php endif; ?>
                </span>
                <strong><?= $g['result']==='win' ? 'برد' : 'باخت' ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php elseif ($pane === 'medal'): ?>
        <?php render_medals((int)$p['id'], 'compact'); ?>

      <?php elseif ($pane === 'radar'): ?>
        <p class="sub" style="text-align:right;margin:0 0 10px">نرخ برد بین بازی‌ها. نقش مافیا در تب «نقش» است.</p>
        <?php if ($labels): ?>
          <div class="profile-radar-box">
            <canvas id="radarChart" data-label="نرخ برد بازی (%)" data-labels='<?= e(json_encode($labels, JSON_UNESCAPED_UNICODE)) ?>' data-values='<?= e(json_encode($values)) ?>'></canvas>
          </div>
        <?php else: ?>
          <p class="empty">هنوز بازی‌ای روی لیدربورد نیست.</p>
        <?php endif; ?>

      <?php else: ?>
        <?php if (count($statCats) > 1): ?>
        <div class="tabs profile-chips">
          <?php foreach ($statCats as $c): ?>
            <a class="tab <?= $catId===(int)$c['id']?'is-on':'' ?>" href="<?= e(url($base . '?view=roles&cat=' . (int)$c['id'])) ?>"><?= e($c['name']) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($scenarios): ?>
        <div class="tabs profile-chips">
          <?php foreach ($scenarios as $sc): ?>
            <a class="tab <?= $scenId===(int)$sc['id']?'is-on':'' ?>" href="<?= e(url($base . '?view=roles&cat=' . $catId . '&scenario=' . (int)$sc['id'])) ?>"><?= e($sc['name']) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php
          $roleLabels = [];
          $roleValues = [];
          foreach ($roleAgg as $ra) {
              $roleLabels[] = (string)$ra['name'];
              $roleValues[] = winrate((int)$ra['wins'], (int)$ra['losses']);
          }
          $scenName = '';
          foreach ($scenarios as $sc) {
              if ((int)$sc['id'] === $scenId) {
                  $scenName = (string)$sc['name'];
              }
          }
        ?>
        <?php if ($roleLabels): ?>
          <p class="profile-rate">سناریو <?= e($scenName) ?> · برد <?= fa_num(winrate((int)($totals['wins'] ?? 0), (int)($totals['losses'] ?? 0))) ?>٪</p>
          <div class="profile-radar-box">
            <canvas id="radarChart" data-label="نرخ برد نقش (%)" data-labels='<?= e(json_encode($roleLabels, JSON_UNESCAPED_UNICODE)) ?>' data-values='<?= e(json_encode($roleValues)) ?>'></canvas>
          </div>
        <?php else: ?>
          <p class="empty">برای این سناریو هنوز نقش ثبت نشده. سناریو را از دکمه‌ها عوض کن.</p>
        <?php endif; ?>
      <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
