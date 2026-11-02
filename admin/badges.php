<?php
$adminTitle = 'بج‌ها';
$keepUser = (int)(post('user_id') ?: getv('user'));
$keepBadge = (int)(post('badge_id') ?: getv('badge'));

if (is_post()) {
    csrf_check();
    $act = (string)post('act');
    if ($act === 'grant') {
        $uid = (int)post('user_id');
        $bid = (int)post('badge_id');
        $times = max(1, min(40, (int)post('times') ?: 1));
        if ($uid && $bid) {
            $info = ['level' => 0, 'wins' => 0, 'up' => false];
            for ($i = 0; $i < $times; $i++) {
                $info = grant_badge($uid, $bid, (int)current_user()['id']);
            }
            $pg = badge_progress((int)$info['wins']);
            $who = q('SELECT display_name FROM users WHERE id=?', [$uid])->fetch();
            $bdg = q('SELECT name FROM badges WHERE id=?', [$bid])->fetch();
            $name = (string)($who['display_name'] ?? 'بازیکن');
            $bn = (string)($bdg['name'] ?? 'بج');
            $msg = fa_num($times) . ' برد «' . $bn . '» برای ' . $name . ' · جمع ' . fa_num($info['wins']) . ' · سطح ' . fa_num($info['level']);
            if ($info['up']) {
                $msg .= ' — سطح بالا رفت.';
            } else {
                $msg .= ' · تا سطح بعد ' . fa_num($pg['remain']) . ' برد.';
            }
            flash('x', $msg, 'ok');
        }
        redirect('admin/badges?tab=add&user=' . $uid . '&badge=' . $bid);
    }
    if ($act === 'down') {
        $uid = (int)post('user_id');
        $bid = (int)post('badge_id');
        $n = max(1, (int)post('n'));
        $left = revoke_badge_wins($uid, $bid, $n);
        flash('x', $left ? ('کم شد. ' . fa_num($left) . ' برد مانده.') : 'بج کامل برداشته شد.', $left ? 'ok' : 'warn');
        redirect('admin/badges?tab=list');
    }
    if ($act === 'revoke') {
        q('DELETE FROM user_badges WHERE user_id=? AND badge_id=?', [(int)post('user_id'), (int)post('badge_id')]);
        flash('x', 'بج کامل از روی پروفایل برداشته شد.', 'ok');
        redirect('admin/badges?tab=list');
    }
    if ($act === 'save_badge') {
        $id = (int)post('id');
        $name = trim((string)post('name'));
        $slug = slugify((string)post('slug') ?: $name);
        $hint = trim((string)post('hint'));
        $color = trim((string)post('color')) ?: '#58bdaf';
        $icon = trim((string)post('icon')) ?: '★';
        $old = (string)post('image_old');
        $img = pick_photo('image', 'preset_image', 'badges', $old);
        if ($name === '') {
            flash('x', 'نام بج را بنویس.', 'err');
            redirect('admin/badges?tab=make' . ($id ? '&edit=' . $id : ''));
        }
        $dup = q('SELECT id FROM badges WHERE slug=?' . ($id ? ' AND id<>?' : ''), $id ? [$slug, $id] : [$slug])->fetch();
        if ($dup) {
            $slug .= '-' . bin2hex(random_bytes(2));
        }
        if ($id) {
            q('UPDATE badges SET slug=?,name=?,hint=?,color=?,icon=?,image=? WHERE id=?',
                [$slug, $name, $hint, $color, $icon, $img, $id]);
            flash('x', 'بج به‌روز شد.', 'ok');
        } else {
            q('INSERT INTO badges (slug,name,hint,color,icon,image) VALUES (?,?,?,?,?,?)',
                [$slug, $name, $hint, $color, $icon, $img]);
            flash('x', 'بج ساخته شد. از تب «دادن بج» می‌توانی بدهی.', 'ok');
        }
        redirect('admin/badges?tab=make');
    }
    if ($act === 'del_badge') {
        $id = (int)post('id');
        if ($id) {
            q('DELETE FROM badges WHERE id=?', [$id]);
            flash('x', 'بج و مدال‌هایش حذف شد.', 'ok');
        }
        redirect('admin/badges?tab=make');
    }
    redirect('admin/badges?tab=list');
}

$badges = q('SELECT * FROM badges ORDER BY id')->fetchAll();
$users = q('SELECT id, display_name, avatar, origin, gender FROM users ORDER BY display_name')->fetchAll();
$owned = q('SELECT ub.user_id, ub.badge_id, ub.level, ub.wins, ub.last_at, ub.created_at,
            b.name, b.icon, b.color, b.image, b.hint, u.display_name, u.avatar, u.origin
            FROM user_badges ub JOIN badges b ON b.id=ub.badge_id JOIN users u ON u.id=ub.user_id
            ORDER BY u.display_name, b.id')->fetchAll();
$ed = (int)getv('edit') ? q('SELECT * FROM badges WHERE id=?', [(int)getv('edit')])->fetch() : null;
$tab = (string)getv('tab');
if ($tab === '') {
    $tab = $ed ? 'make' : 'list';
}
if (!in_array($tab, ['add', 'list', 'make'], true)) {
    $tab = 'list';
}

$held = null;
if ($keepUser && $keepBadge) {
    $held = q('SELECT ub.wins, ub.level, b.name, b.icon, b.color, b.image, u.display_name
               FROM user_badges ub JOIN badges b ON b.id=ub.badge_id JOIN users u ON u.id=ub.user_id
               WHERE ub.user_id=? AND ub.badge_id=?', [$keepUser, $keepBadge])->fetch() ?: null;
}
?>
<p style="color:var(--muted);line-height:1.85;margin:0 0 16px">مدال فقط روی پروفایل است. هر بار دادن = یک برد. سطح بعدی هر بار سخت‌تر می‌شود (۳، ۶، ۹، …) و سقف ندارد — بعد از استاد، افسانه ۱، ۲، ۳ ادامه دارد.</p>
<?php admin_tabs('admin/badges', ['add' => 'دادن بج', 'list' => 'داده‌شده‌ها', 'make' => $ed ? 'ویرایش بج' : 'ساخت بج'], $tab); ?>

<div class="admin-pane"<?= $tab === 'add' ? '' : ' hidden' ?>>
  <div class="card" style="padding:18px;margin-bottom:16px">
    <h3>دادن بج</h3>
    <form method="post" class="form" style="margin-top:12px">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="grant">
      <label>بازیکن</label>
      <select name="user_id">
        <?php foreach ($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= $keepUser === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['display_name']) ?> · <?= e(gender_fa(gender_of($u))) ?></option>
        <?php endforeach; ?>
      </select>
      <label>جستجوی بج</label>
      <input type="search" data-badge-find placeholder="نام بج را بنویس…" autocomplete="off">
      <input type="hidden" name="badge_id" data-badge-val value="<?= $keepBadge ?: '' ?>" required>
      <div class="badge-find-list" data-badge-list>
        <?php foreach ($badges as $b): ?>
          <button type="button" class="badge-find-row<?= $keepBadge === (int)$b['id'] ? ' is-on' : '' ?>" data-id="<?= (int)$b['id'] ?>" data-hint="<?= e($b['hint'] ?: '') ?>" data-name="<?= e(($b['icon'] ?: '★') . ' ' . $b['name']) ?>">
            <b><?= e(($b['icon'] ?: '★') . ' ' . $b['name']) ?></b>
            <small><?= e($b['hint'] ?: '') ?></small>
          </button>
        <?php endforeach; ?>
      </div>
      <p class="badge-hint-live" data-badge-hint></p>
      <label>چند برد این‌بار</label>
      <input type="number" name="times" min="1" max="40" value="1" placeholder="مثلاً ۳" inputmode="numeric">
      <button class="btn btn-solid" type="submit">بگذار روی پروفایل</button>
    </form>
    <?php if ($held): $pg = badge_progress((int)$held['wins']); [$tier] = medal_tier($pg['level']); ?>
      <div class="grant-held">
        <b><?= e($held['display_name']) ?></b>
        همین حالا «<?= e($held['name']) ?>» دارد:
        سطح <?= fa_num($pg['level']) ?> · <?= e($tier) ?> · <?= fa_num($pg['wins']) ?> برد
        · تا سطح <?= fa_num($pg['level'] + 1) ?> هنوز <?= fa_num($pg['remain']) ?> برد.
      </div>
    <?php elseif ($keepUser && $keepBadge): ?>
      <p class="sub" style="margin-top:12px;text-align:right">این بازیکن هنوز این بج را ندارد. اولین دادن = سطح ۱.</p>
    <?php else: ?>
      <p class="sub" style="margin-top:12px;text-align:right">بعد از دادن، همین بازیکن و همین بج انتخاب می‌مانند تا چندبار پشت‌سرهم سطح بدهی.</p>
    <?php endif; ?>
  </div>
</div>

<div class="admin-pane"<?= $tab === 'list' ? '' : ' hidden' ?>>
  <div class="card" style="padding:8px">
    <h3 style="padding:12px">بج‌های داده‌شده</h3>
    <?php if (!$owned): ?><p class="empty">هنوز بج ندادی.</p><?php endif; ?>
    <div class="night-list" style="padding:8px">
      <?php foreach ($owned as $row): $pg = badge_progress((int)$row['wins']); [$tier, $cls] = medal_tier($pg['level']); ?>
        <article class="night-card medal-owned <?= e($cls) ?>">
          <div class="night-card-head medal-owned-head">
            <?= medal_coin_html($row, 'is-sm') ?>
            <div>
              <?= player_who_html($row, url('player/' . (int)$row['user_id'])) ?>
              <div class="medal-owned-meta">
                <b><?= e($row['name']) ?></b>
                سطح <?= fa_num($pg['level']) ?> · <?= e($tier) ?> · <?= fa_num($pg['wins']) ?> برد
                · تا بعد <?= fa_num($pg['remain']) ?> برد
              </div>
              <div class="medal-bar"><span style="width:<?= (int)$pg['pct'] ?>%"></span></div>
            </div>
          </div>
          <form method="post" class="night-actions medal-revoke">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" value="<?= (int)$row['user_id'] ?>">
            <input type="hidden" name="badge_id" value="<?= (int)$row['badge_id'] ?>">
            <label class="medal-n">کم کردن
              <input type="number" name="n" value="1" min="1" max="<?= (int)$row['wins'] ?>">
            </label>
            <button class="btn btn-sm" name="act" value="down" type="submit">دانه‌دانه</button>
            <button class="btn btn-sm btn-danger" name="act" value="revoke" type="submit" data-confirm="این بج کامل از روی پروفایل برداشته شود؟">برداشتن کامل</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="admin-pane"<?= $tab === 'make' ? '' : ' hidden' ?>>
  <div class="card" style="padding:18px;margin-bottom:16px">
    <h3><?= $ed ? 'ویرایش بج' : 'بج جدید' ?></h3>
    <form class="form" method="post" enctype="multipart/form-data" style="margin-top:12px">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="save_badge">
      <input type="hidden" name="id" value="<?= (int)($ed['id'] ?? 0) ?>">
      <input type="hidden" name="image_old" value="<?= e($ed['image'] ?? '') ?>">
      <div class="grid-2">
        <div><label>نام</label><input name="name" required value="<?= e($ed['name'] ?? '') ?>" placeholder="مثلاً ستاره میز"></div>
        <div><label>اسلاگ</label><input name="slug" value="<?= e($ed['slug'] ?? '') ?>" placeholder="mvp" dir="ltr" class="ltr-input"></div>
        <div><label>نماد (اگر عکس نباشد)</label><input name="icon" value="<?= e($ed['icon'] ?? '★') ?>" maxlength="8" placeholder="★"></div>
        <div><label>رنگ</label><input name="color" value="<?= e($ed['color'] ?? '#58bdaf') ?>"></div>
      </div>
      <label>توضیح کوتاه</label>
      <textarea name="hint" placeholder="روی پروفایل، زیر مدال دیده می‌شود"><?= e($ed['hint'] ?? '') ?></textarea>
      <label>عکس بج</label>
      <?php render_photo_picker('image', 'preset_image', (string)($ed['image'] ?? ''), 'badge'); ?>
      <button class="btn btn-solid" type="submit"><?= $ed ? 'ذخیره بج' : 'ساخت بج' ?></button>
      <?php if ($ed): ?><a class="btn btn-ghost" href="<?= e(url('admin/badges?tab=make')) ?>">انصراف</a><?php endif; ?>
    </form>
  </div>
  <div class="badge-catalog">
    <?php foreach ($badges as $b): ?>
      <article class="card badge-type">
        <?= medal_coin_html($b, 'is-sm') ?>
        <div>
          <b><?= e($b['name']) ?></b>
          <p><?= e($b['hint']) ?></p>
          <div class="night-actions">
            <a class="btn btn-sm" href="<?= e(url('admin/badges?tab=make&edit=' . (int)$b['id'])) ?>">ویرایش</a>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="act" value="del_badge">
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn btn-sm btn-danger" data-confirm="این بج و همهٔ مدال‌هایش حذف شود؟">حذف</button>
            </form>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
