<?php
$u = current_user();
if (is_post()) {
    csrf_check();
    $name = trim((string)post('name'));
    $body = trim((string)post('body'));
    if ($u) {
        $name = trim((string)$u['display_name']);
    }
    $errs = [];
    if (mb_strlen($name) < 2) $errs[] = 'نام را بنویس.';
    if (mb_strlen($body) < 8) $errs[] = 'نظر حداقل چند کلمه باشد.';
    if (mb_strlen($body) > 400) $errs[] = 'نظر کوتاه‌تر بنویس (حداکثر ۴۰۰ حرف).';
    $last = (int)($_SESSION['last_comment_at'] ?? 0);
    if (time() - $last < 40) $errs[] = 'یک لحظه صبر کن و دوباره بفرست.';
    if ($errs) {
        flash('x', implode(' ', $errs), 'err');
        redirect('comments');
    }
    $av = $u ? trim((string)($u['avatar'] ?? '')) : '';
    if ($av === '' || !str_contains($av, '/presets/')) {
        $av = random_preset_avatar();
    }
    q('INSERT INTO comments (user_id,name,body,avatar,featured,sort_order,created_at) VALUES (?,?,?,?,0,0,datetime("now"))',
        [$u ? (int)$u['id'] : null, mb_substr($name, 0, 60), $body, $av]);
    $_SESSION['last_comment_at'] = time();
    flash('x', 'نظر ثبت شد. اگر انتخاب شود روی صفحه اصلی می‌آید.', 'ok');
    redirect('comments');
}

$quotes = q('SELECT c.*, COALESCE(NULLIF(c.avatar,""), u.avatar) AS avatar
             FROM comments c LEFT JOIN users u ON u.id=c.user_id
             WHERE c.featured=1 ORDER BY c.sort_order, c.id DESC LIMIT 4')->fetchAll();
$title = 'نظرات';
$desc = 'نظر بازیکن‌ها درباره میزهای رکاد.';
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">VOICES</div>
    <h1>نظرات بازیکن‌ها</h1>
    <p class="sub">چند خط از شبت بگو. ادمین خوب‌ها را برای صفحه اصلی برمی‌دارد.</p>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap">
    <?php if ($quotes): ?>
      <div style="margin-bottom:36px"><?php render_quote_cards($quotes); ?></div>
    <?php endif; ?>
    <div class="card comment-box">
      <div class="kicker" style="margin-bottom:6px">WRITE</div>
      <h3>نظرت را بنویس</h3>
      <p class="sub" style="margin:0 0 14px">اگر میزبان بپسندد، روی صفحه اصلی می‌آید.</p>
      <form class="form" method="post">
        <?= csrf_field() ?>
        <?php if ($u): ?>
          <p class="sub" style="text-align:right;margin:0 0 8px">با نام <?= e($u['display_name']) ?></p>
        <?php else: ?>
          <label>نام</label>
          <input name="name" required maxlength="60" placeholder="مثلاً آرین کاظمی" autocomplete="name">
        <?php endif; ?>
        <label>نظرت</label>
        <textarea name="body" required maxlength="400" placeholder="مثلاً میز کاپو دیشب عالی بود."></textarea>
        <button class="btn btn-solid" type="submit">ارسال نظر</button>
      </form>
    </div>
  </div>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
