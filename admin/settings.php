<?php
$adminTitle = 'تنظیمات رکاد';
if (is_post()) {
    csrf_check();
    foreach (['cafe_address','cafe_phone','cafe_hours','about','mvp_label','noshow_keep_percent'] as $k) {
        setting($k, trim((string)post($k)));
    }
    foreach ([1,2,3] as $i) {
        $old = setting('trust_logo_' . $i);
        $got = pick_photo('trust_' . $i, 'trust_preset_' . $i, 'trust', $old);
        if (post('trust_clear_' . $i)) {
            setting('trust_logo_' . $i, '');
        } else {
            setting('trust_logo_' . $i, $got);
        }
    }
    flash('x','تنظیمات ذخیره شد.','ok');
    redirect('/admin/settings');
}
?>
<form class="form card" method="post" enctype="multipart/form-data" style="padding:22px;max-width:760px">
  <?= csrf_field() ?>
  <label>آدرس</label>
  <input name="cafe_address" value="<?= e(setting('cafe_address')) ?>">
  <label>تلفن</label>
  <input name="cafe_phone" value="<?= e(setting('cafe_phone')) ?>" dir="ltr" class="ltr-input">
  <label>ساعت بازی</label>
  <input name="cafe_hours" value="<?= e(setting('cafe_hours')) ?>">
  <label>برچسب MVP</label>
  <input name="mvp_label" value="<?= e(setting('mvp_label')) ?>">
  <label>متن درباره رکاد</label>
  <textarea name="about" style="min-height:160px"><?= e(setting('about')) ?></textarea>
  <label>غیبت بعد از پرداخت کامل — درصد ضبط (۰ تا ۱۰۰)</label>
  <input name="noshow_keep_percent" type="number" min="0" max="100" inputmode="numeric" placeholder="مثلاً ۱۵" value="<?= e(setting('noshow_keep_percent') !== '' ? setting('noshow_keep_percent') : '15') ?>">
  <p class="sub" style="margin:0 0 12px">اگر ۱۵ باشد و ۱۵۰ هزار پرداخته، ۲۲٬۵۰۰ ضبط و بقیه به کیف برمی‌گردد.</p>

  <h3 style="margin-top:12px">نمادهای اعتماد فوتر</h3>
  <p style="color:var(--muted);font-size:13px;line-height:1.8">اگر چیزی نگذاری، در فوتر هیچ جعبه‌ی خالی نشان داده نمی‌شود.</p>
  <?php foreach ([1,2,3] as $i): $cur = setting('trust_logo_'.$i); ?>
    <div class="card" style="padding:14px;margin:10px 0">
      <label>نماد <?= fa_num($i) ?></label>
      <?php render_photo_picker('trust_'.$i, 'trust_preset_'.$i, $cur, 'trust'); ?>
      <?php if ($cur): ?>
        <label style="margin-top:8px"><input type="checkbox" name="trust_clear_<?= $i ?>" value="1"> حذف این نماد</label>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-solid" type="submit">ذخیره</button>
</form>
