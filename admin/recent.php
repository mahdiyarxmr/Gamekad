<?php
$adminTitle = 'بازی‌های اخیر';
$cats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$cid = (int)(post('category_id') ?: getv('cat') ?: ($cats[0]['id'] ?? 0));
$cat = null;
foreach ($cats as $c) {
    if ((int)$c['id'] === $cid) {
        $cat = $c;
    }
}
$hasRoles = !empty($cat['has_roles']);

if (is_post()) {
    csrf_check();
    $act = (string)post('act');
    $cid = (int)post('category_id') ?: $cid;
    $cat = q('SELECT * FROM categories WHERE id=?', [$cid])->fetch() ?: $cat;
    $hasRoles = !empty($cat['has_roles']);

    if ($act === 'save') {
        $uids = $_POST['uid'] ?? [];
        $roles = $_POST['role_id'] ?? [];
        $pts = $_POST['points'] ?? [];
        $rowRes = $_POST['row_result'] ?? [];
        $winner = (string)post('winner');
        $scen = (int)post('scenario_id') ?: null;
        $title = trim((string)post('title'));
        $players = [];
        $seen = [];
        foreach ($uids as $i => $uid) {
            $uid = (int)$uid;
            if ($uid < 1 || isset($seen[$uid])) {
                continue;
            }
            $seen[$uid] = 1;
            $rid = (int)($roles[$i] ?? 0) ?: null;
            $side = null;
            if ($rid) {
                $rr = q('SELECT side FROM roles WHERE id=?', [$rid])->fetch();
                $side = $rr['side'] ?? null;
            }
            if ($hasRoles) {
                if (in_array($winner, ['citizen', 'mafia', 'indie'], true) && !$side) {
                    flash('x', 'برای میز نقش‌دار، نقش هر بازیکن را مشخص کن.', 'err');
                    redirect('admin/recent?tab=add&cat=' . $cid);
                }
                $res = result_from_table($winner, $side);
            } else {
                $res = (($rowRes[$i] ?? 'win') === 'loss') ? 'loss' : 'win';
            }
            $players[] = [
                'uid' => $uid,
                'role' => $rid,
                'result' => $res,
                'points' => max(0, (int)($pts[$i] ?? 0)),
            ];
        }
        if (!$players) {
            flash('x', 'حداقل یک بازیکن لازم است.', 'err');
            redirect('admin/recent?tab=add&cat=' . $cid);
        }
        q('INSERT INTO matches (category_id,scenario_id,winner,title,created_at) VALUES (?,?,?,?,datetime("now"))',
            [$cid, $scen, $winner, $title]);
        $mid = (int)db()->lastInsertId();
        foreach ($players as $pl) {
            q('INSERT INTO match_players (match_id,user_id,role_id,result,points) VALUES (?,?,?,?,?)',
                [$mid, $pl['uid'], $pl['role'], $pl['result'], $pl['points']]);
            q('INSERT INTO player_stats (user_id,category_id,scenario_id,role_id,result,notes,match_id,created_at)
               VALUES (?,?,?,?,?,?,?,datetime("now"))',
                [$pl['uid'], $cid, $scen, $pl['role'], $pl['result'], $title !== '' ? $title : 'بازی اخیر', $mid]);
            lb_bump($cid, $pl['uid'], $pl['points'], $pl['result']);
        }
        flash('x', 'میز ثبت شد. برد/باخت و امتیاز روی لیدربورد نشست.', 'ok');
        redirect('admin/recent?tab=list&cat=' . $cid);
    }

    if ($act === 'del') {
        $id = (int)post('id');
        $m = q('SELECT * FROM matches WHERE id=?', [$id])->fetch();
        if ($m) {
            foreach (q('SELECT * FROM match_players WHERE match_id=?', [$id])->fetchAll() as $pl) {
                lb_unbump((int)$m['category_id'], (int)$pl['user_id'], (int)$pl['points'], (string)$pl['result']);
            }
            q('DELETE FROM player_stats WHERE match_id=?', [$id]);
            q('DELETE FROM match_players WHERE match_id=?', [$id]);
            q('DELETE FROM matches WHERE id=?', [$id]);
            flash('x', 'بازی حذف شد و امتیازش برگشت.', 'ok');
        }
        redirect('admin/recent?tab=list&cat=' . $cid);
    }

    if ($act === 'score') {
        $id = (int)post('id');
        $m = q('SELECT * FROM matches WHERE id=?', [$id])->fetch();
        if ($m) {
            $pids = $_POST['pid'] ?? [];
            $pts = $_POST['points'] ?? [];
            foreach ($pids as $i => $pid) {
                $pid = (int)$pid;
                $pl = q('SELECT * FROM match_players WHERE id=? AND match_id=?', [$pid, $id])->fetch();
                if (!$pl) {
                    continue;
                }
                $next = max(0, (int)($pts[$i] ?? 0));
                $delta = $next - (int)$pl['points'];
                q('UPDATE match_players SET points=? WHERE id=?', [$next, $pid]);
                lb_add_points((int)$m['category_id'], (int)$pl['user_id'], $delta);
            }
            q('UPDATE matches SET scored=1 WHERE id=?', [$id]);
            flash('x', 'امتیاز این میز روی لیدربورد نشست.', 'ok');
        }
        redirect('admin/recent?tab=list&cat=' . $cid);
    }

    if ($act === 'import') {
        $ajax = (string)post('ajax') === '1';
        $godId = trim((string)post('night_id'));
        $title = trim((string)post('title'));
        $scen = (int)post('scen_id') ?: null;
        $winner = (string)post('winner');
        if (!in_array($winner, ['citizen', 'mafia', 'indie', 'self', 'opponent'], true)) {
            $winner = 'citizen';
        }
        if ($scen && !$cid) {
            $row = q('SELECT category_id FROM scenarios WHERE id=?', [$scen])->fetch();
            $cid = (int)($row['category_id'] ?? 0);
        }
        if ($godId !== '') {
            $dup = q('SELECT id, category_id FROM matches WHERE god_id=?', [$godId])->fetch();
            if ($dup) {
                if ($ajax) {
                    json_out(['ok' => true, 'id' => (int)$dup['id'], 'dup' => true]);
                }
                flash('x', 'این میز قبلاً در بازی‌های اخیر بود.', 'ok');
                redirect('admin/recent?tab=list&cat=' . (int)$dup['category_id']);
            }
        }
        $seats = json_decode((string)post('seats'), true);
        if (!is_array($seats)) {
            $seats = [];
        }
        $players = [];
        $miss = [];
        $seen = [];
        foreach ($seats as $s) {
            $nm = trim((string)($s['name'] ?? ''));
            $u = user_find_by_name($nm);
            if (!$u) {
                if ($nm !== '') {
                    $miss[] = $nm;
                }
                continue;
            }
            $uid = (int)$u['id'];
            if (isset($seen[$uid])) {
                continue;
            }
            $seen[$uid] = 1;
            $rid = (int)($s['roleId'] ?? $s['role_id'] ?? 0) ?: null;
            $side = null;
            if ($rid) {
                $rr = q('SELECT side FROM roles WHERE id=?', [$rid])->fetch();
                $side = $rr['side'] ?? null;
            }
            $res = result_from_table($winner, $side);
            $pts = max(0, (int)($s['points'] ?? 0));
            $players[] = ['uid' => $uid, 'role' => $rid, 'result' => $res, 'points' => $pts];
        }
        if (!$cid || !$players) {
            $msg = !$players ? 'اسم بازیکن‌ها با کاربران سایت یکی نبود.' : 'دستهٔ بازی پیدا نشد.';
            if ($ajax) {
                json_out(['ok' => false, 'err' => $msg, 'miss' => $miss], 422);
            }
            flash('x', $msg, 'err');
            redirect('admin/recent');
        }
        $anyPts = 0;
        foreach ($players as $pl) {
            if ((int)($pl['points'] ?? 0) > 0) {
                $anyPts = 1;
                break;
            }
        }
        q('INSERT INTO matches (category_id,scenario_id,winner,title,scored,god_id,created_at) VALUES (?,?,?,?,?,?,datetime("now"))',
            [$cid, $scen, $winner, $title !== '' ? $title : 'میز گرداندگی', $anyPts, $godId]);
        $mid = (int)db()->lastInsertId();
        foreach ($players as $pl) {
            $pts = (int)($pl['points'] ?? 0);
            q('INSERT INTO match_players (match_id,user_id,role_id,result,points) VALUES (?,?,?,?,?)',
                [$mid, $pl['uid'], $pl['role'], $pl['result'], $pts]);
            q('INSERT INTO player_stats (user_id,category_id,scenario_id,role_id,result,notes,match_id,created_at)
               VALUES (?,?,?,?,?,?,?,datetime("now"))',
                [$pl['uid'], $cid, $scen, $pl['role'], $pl['result'], $title !== '' ? $title : 'میز گرداندگی', $mid]);
            lb_bump($cid, $pl['uid'], $pts, $pl['result']);
        }
        if ($ajax) {
            json_out(['ok' => true, 'id' => $mid, 'miss' => $miss]);
        }
        flash('x', 'میز آمد روی بازی‌های اخیر. امتیاز را همین‌جا بزن.' . ($miss ? ' پیدا نشد: ' . implode('، ', $miss) : ''), 'ok');
        redirect('admin/recent?tab=list&cat=' . $cid);
    }
    redirect('admin/recent?cat=' . $cid);
}

$users = q('SELECT id, display_name, avatar FROM users WHERE role="user" ORDER BY display_name')->fetchAll();
$roles = q('SELECT r.*, sc.name AS scen FROM roles r LEFT JOIN scenarios sc ON sc.id=r.scenario_id WHERE r.category_id=? ORDER BY r.id', [$cid])->fetchAll();
$scens = q('SELECT * FROM scenarios WHERE category_id=? ORDER BY id', [$cid])->fetchAll();
$list = q('SELECT m.*, c.name AS cat_name FROM matches m JOIN categories c ON c.id=m.category_id
           WHERE m.category_id=? ORDER BY m.id DESC LIMIT 40', [$cid])->fetchAll();
foreach ($list as &$m) {
    $m['people'] = q('SELECT mp.*, u.display_name, u.avatar, r.name AS role_name
                      FROM match_players mp JOIN users u ON u.id=mp.user_id
                      LEFT JOIN roles r ON r.id=mp.role_id
                      WHERE mp.match_id=? ORDER BY mp.id', [(int)$m['id']])->fetchAll();
}
unset($m);

$tab = admin_current_tab('list');
$winLabel = [
    'citizen' => 'شهر برد',
    'mafia' => 'مافیا برد',
    'indie' => 'مستقل برد',
    'self' => 'برنده تکی',
    'opponent' => 'باخت تکی',
];
?>
<div class="tabs" style="justify-content:flex-start;margin-bottom:14px">
  <?php foreach ($cats as $c): ?>
    <a class="tab <?= $cid===(int)$c['id']?'is-on':'' ?>" href="<?= e(url('admin/recent?cat=' . (int)$c['id'] . '&tab=' . $tab)) ?>"><?= e($c['name']) ?></a>
  <?php endforeach; ?>
</div>
<p class="sub" style="text-align:right;margin:0 0 14px">کل میز را این‌جا ببند. اگر نقش‌دار است (مافیا)، برندهٔ تیم را بگو تا برد/باخت از روی نقش حساب شود. امتیاز همان‌جا به لیدربورد اضافه می‌شود. اصلاح دستی همچنان در «امتیاز» ممکن است.</p>
<?php admin_tabs('admin/recent', ['add' => 'ثبت میز', 'list' => 'فهرست'], $tab); ?>

<div class="admin-pane"<?= $tab === 'add' ? '' : ' hidden' ?>>
  <div class="card" style="padding:18px">
    <h3>میز جدید · <?= e($cat['name'] ?? '') ?></h3>
    <form class="form" method="post" id="recentForm" style="margin-top:12px">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="category_id" value="<?= $cid ?>">
      <label>عنوان (اختیاری)</label>
      <input name="title" placeholder="مثلاً پنجشنبه کاپو">
      <?php if ($hasRoles): ?>
        <?php if ($scens): ?>
          <label>سناریو</label>
          <select name="scenario_id">
            <option value="0">—</option>
            <?php foreach ($scens as $s): ?>
              <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
        <label>برنده میز</label>
        <select name="winner" required>
          <option value="citizen">شهر / شهروندان</option>
          <option value="mafia">مافیا / گرگ</option>
          <option value="indie">مستقل</option>
        </select>
      <?php else: ?>
        <input type="hidden" name="winner" value="self">
      <?php endif; ?>

      <label>بازیکنان این میز</label>
      <div class="recent-rows" data-recent-rows>
        <?php for ($i = 0; $i < 4; $i++): ?>
          <div class="recent-row">
            <select name="uid[]">
              <option value="0">— بازیکن —</option>
              <?php foreach ($users as $u): ?>
                <option value="<?= (int)$u['id'] ?>"><?= e($u['display_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($hasRoles): ?>
              <select name="role_id[]">
                <option value="0">— نقش —</option>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?><?= !empty($r['scen']) ? ' · ' . e($r['scen']) : '' ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="hidden" name="role_id[]" value="0">
              <select name="row_result[]">
                <option value="win">برد</option>
                <option value="loss">باخت</option>
              </select>
            <?php endif; ?>
            <input name="points[]" type="number" min="0" placeholder="امتیاز" inputmode="numeric">
          </div>
        <?php endfor; ?>
      </div>
      <button class="btn btn-ghost" type="button" data-recent-add>افزودن ردیف</button>
      <button class="btn btn-solid" type="submit">ثبت میز و امتیاز</button>
    </form>
  </div>
  <template id="recentRowTpl">
    <div class="recent-row">
      <select name="uid[]">
        <option value="0">— بازیکن —</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>"><?= e($u['display_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($hasRoles): ?>
        <select name="role_id[]">
          <option value="0">— نقش —</option>
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input type="hidden" name="role_id[]" value="0">
        <select name="row_result[]">
          <option value="win">برد</option>
          <option value="loss">باخت</option>
        </select>
      <?php endif; ?>
      <input name="points[]" type="number" min="0" placeholder="امتیاز" inputmode="numeric">
    </div>
  </template>
</div>

<div class="admin-pane"<?= $tab === 'list' ? '' : ' hidden' ?>>
  <?php if (!$list): ?>
    <p class="empty">هنوز میزی برای <?= e($cat['name'] ?? 'این بازی') ?> ثبت نشده.</p>
  <?php endif; ?>
  <div class="recent-list">
    <?php foreach ($list as $m): ?>
      <article class="card recent-card">
        <div class="recent-card-h">
          <div>
            <b><?= e($m['title'] !== '' ? $m['title'] : $m['cat_name']) ?></b>
            <small><?= empty($m['scored']) ? 'پایان یافته — امتیاز بزن' : e($winLabel[$m['winner']] ?? $m['winner']) ?> · <?= jdate_format((string)$m['created_at']) ?></small>
          </div>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="act" value="del">
            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <input type="hidden" name="category_id" value="<?= $cid ?>">
            <button class="btn btn-sm btn-danger" data-confirm="این میز حذف شود و امتیازها برگردند؟">حذف</button>
          </form>
        </div>
        <?php if (empty($m['scored'])): ?>
          <form method="post" class="recent-score">
            <?= csrf_field() ?>
            <input type="hidden" name="act" value="score">
            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <input type="hidden" name="category_id" value="<?= $cid ?>">
            <div class="recent-people">
              <?php foreach ($m['people'] as $pl): ?>
                <label class="recent-chip <?= $pl['result']==='win'?'is-win':'is-loss' ?>">
                  <img src="<?= e(media($pl['avatar'])) ?>" alt="">
                  <span>
                    <?= e($pl['display_name']) ?>
                    <?php if ($pl['role_name']): ?><i><?= e($pl['role_name']) ?></i><?php endif; ?>
                  </span>
                  <strong><?= $pl['result']==='win' ? 'برد' : 'باخت' ?></strong>
                  <input type="hidden" name="pid[]" value="<?= (int)$pl['id'] ?>">
                  <input name="points[]" type="number" min="0" placeholder="امتیاز" value="<?= (int)$pl['points'] ?: '' ?>" inputmode="numeric">
                </label>
              <?php endforeach; ?>
            </div>
            <button class="btn btn-solid btn-sm" type="submit" style="margin-top:10px">ثبت امتیاز این میز</button>
          </form>
        <?php else: ?>
        <div class="recent-people">
          <?php foreach ($m['people'] as $pl): ?>
            <a class="recent-chip <?= $pl['result']==='win'?'is-win':'is-loss' ?>" href="<?= e(url('player/' . (int)$pl['user_id'])) ?>">
              <img src="<?= e(media($pl['avatar'])) ?>" alt="">
              <span>
                <?= e($pl['display_name']) ?>
                <?php if ($pl['role_name']): ?><i><?= e($pl['role_name']) ?></i><?php endif; ?>
              </span>
              <strong><?= $pl['result']==='win' ? 'برد' : 'باخت' ?></strong>
              <?php if ((int)$pl['points']): ?><em>+<?= fa_num($pl['points']) ?></em><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
</div>
