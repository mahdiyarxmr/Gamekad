<?php
require_login();
$u = current_user(true);

if (is_post()) {
    csrf_check();
    $act = (string)post('act');
    if ($act === 'profile') {
        $name = trim((string)post('display_name'));
        $bio = trim((string)post('bio'));
        $phone = trim((string)post('phone'));
        $avatar = pick_photo('avatar', 'preset_avatar', 'avatars', (string)$u['avatar']);
        q('UPDATE users SET display_name=?, bio=?, phone=?, avatar=? WHERE id=?', [$name, $bio, $phone, $avatar, $u['id']]);
        flash('x','پروفایل به‌روز شد.','ok');
        redirect('/account');
    }
}

$res = q('SELECT r.*, s.title, s.session_date, s.start_time, s.price, c.name AS cat_name
          FROM reservations r JOIN game_sessions s ON s.id=r.session_id
          JOIN categories c ON c.id=s.category_id
          WHERE r.user_id=? ORDER BY s.session_date DESC', [$u['id']])->fetchAll();

$title = 'حساب من';
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">ACCOUNT</div>
    <h1><?= e($u['display_name']) ?></h1>
    <p class="sub">
      <a href="<?= e(url('player/' . (int)$u['id'])) ?>">پروفایل عمومی</a> ·
      <a href="<?= e(url('wallet')) ?>">کیف پول</a>
      <?php if (is_admin()): ?> · <a href="<?= e(url('admin')) ?>">پنل ادمین</a><?php endif; ?>
      <?php if (is_godgame()): ?> · <a href="<?= e(url('admin/godgame')) ?>">خدای بازی</a><?php endif; ?>
      · <a href="<?= e(url('logout')) ?>">خروج</a>
    </p>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap">
    <?php if (can_staff()): ?>
      <a class="btn btn-solid staff-jump" href="<?= e(url(staff_home())) ?>">ورود به <?= e(staff_label()) ?></a>
    <?php endif; ?>
  </div>
  <div class="wrap grid-2" style="margin-top:16px">
    <div class="card" style="padding:22px">
      <h3>ویرایش پروفایل</h3>
      <form class="form" method="post" enctype="multipart/form-data" style="margin-top:12px">
        <?= csrf_field() ?>
        <input type="hidden" name="act" value="profile">
        <label>عکس</label>
        <?php render_photo_picker('avatar', 'preset_avatar', (string)$u['avatar'], 'avatar'); ?>
        <label>نام نمایشی</label>
        <input name="display_name" value="<?= e($u['display_name']) ?>" required placeholder="مثلاً آرین کاظمی">
        <label>موبایل</label>
        <input name="phone" value="<?= e($u['phone']) ?>" placeholder="مثلاً 09332881180" dir="ltr" class="ltr-input">
        <label>بیو</label>
        <textarea name="bio" placeholder="چند خط درباره خودت"><?= e($u['bio']) ?></textarea>
        <button class="btn btn-solid" type="submit">ذخیره</button>
      </form>
    </div>
    <div>
      <div class="card" style="padding:22px;margin-bottom:16px">
        <h3>کیف پول</h3>
        <div class="pay-amount" style="font-size:28px;margin:8px 0"><?= money(user_wallet($u)) ?></div>
        <a class="btn btn-solid" href="<?= e(url('wallet')) ?>">شارژ حساب — دیدن درگاه نمونه</a>
      </div>
      <div class="card" style="padding:22px">
        <h3>رزروهای من</h3>
        <?php if (!$res): ?><p class="empty">هنوز رزروی نداری.</p><?php endif; ?>
        <table class="data-table">
          <tbody>
          <?php foreach ($res as $r): ?>
            <tr>
              <td>
                <b><?= e($r['title']) ?></b><br>
                <span style="color:var(--muted);font-size:12px"><?= e($r['cat_name']) ?> · <?= jdate_format($r['session_date'].' '.$r['start_time']) ?></span>
              </td>
              <td><?= e(status_fa($r['payment_status'])) ?><br><span style="color:var(--gold);font-size:12px"><?= money((int)$r['amount_paid']) ?></span></td>
              <td><?= e(status_fa($r['attendance'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
