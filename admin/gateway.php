<?php
require_administrator();
$adminTitle = 'درگاه پرداخت';

if (is_post()) {
    csrf_check();
    $provider = (string)post('pay_provider');
    if (!in_array($provider, ['simulated', 'zarinpal', 'nextpay', 'idpay'], true)) {
        $provider = 'simulated';
    }
    $mode = (string)post('pay_mode') === 'live' ? 'live' : 'sandbox';
    setting('pay_enabled', post('pay_enabled') ? '1' : '0');
    setting('pay_provider', $provider);
    setting('pay_mode', $mode);
    setting('pay_merchant', trim((string)post('pay_merchant')));
    setting('pay_extra', trim((string)post('pay_extra')));
    flash('x', 'تنظیمات درگاه ذخیره شد.', 'ok');
    redirect('admin/gateway');
}

$enabled = setting('pay_enabled') === '1';
$provider = setting('pay_provider') ?: 'simulated';
$mode = setting('pay_mode') ?: 'sandbox';
?>
<p style="color:var(--creamd);line-height:1.9;margin-bottom:16px">این صفحه فقط برای <b>Administrator (ادمین سطح ۲)</b> است. ادمین عادی آن را در منو نمی‌بیند و اگر آدرس را دستی باز کند برمی‌گردد. کلید مرچنت را اینجا بگذار؛ تا وقتی شبیه‌سازی روشن باشد، پرداخت واقعی از درگاه زده نمی‌شود.</p>

<div class="grid-2" style="margin-bottom:18px">
  <div class="kpi"><span>وضعیت</span><b><?= $enabled ? 'فعال' : 'خاموش' ?></b></div>
  <div class="kpi"><span>درگاه</span><b><?= e(status_fa($provider)) ?> · <?= e(status_fa($mode)) ?></b></div>
</div>

<form class="form card" method="post" style="padding:22px;max-width:760px">
  <?= csrf_field() ?>
  <label><input type="checkbox" name="pay_enabled" value="1" <?= $enabled ? 'checked' : '' ?>> درگاه برای رزرو و شارژ کیف پول فعال باشد</label>

  <label>ارائه‌دهنده</label>
  <select name="pay_provider">
    <option value="simulated" <?= $provider==='simulated'?'selected':'' ?>>شبیه‌سازی داخلی (بدون درگاه واقعی)</option>
    <option value="zarinpal" <?= $provider==='zarinpal'?'selected':'' ?>>زرین‌پال</option>
    <option value="nextpay" <?= $provider==='nextpay'?'selected':'' ?>>نکست‌پی</option>
    <option value="idpay" <?= $provider==='idpay'?'selected':'' ?>>آیدی‌پی</option>
  </select>

  <label>حالت</label>
  <select name="pay_mode">
    <option value="sandbox" <?= $mode==='sandbox'?'selected':'' ?>>آزمایشی (Sandbox)</option>
    <option value="live" <?= $mode==='live'?'selected':'' ?>>واقعی (Live)</option>
  </select>

  <label>مرچنت / API Key</label>
  <input name="pay_merchant" value="<?= e(setting('pay_merchant')) ?>" dir="ltr" class="ltr-input" placeholder="مثلاً زرین‌پال MerchantID" autocomplete="off">

  <label>کلید دوم / Sandbox (اختیاری)</label>
  <input name="pay_extra" value="<?= e(setting('pay_extra')) ?>" dir="ltr" class="ltr-input" placeholder="اختیاری — توکن سندباکس یا X-API-KEY">

  <p style="color:var(--muted);font-size:13px;line-height:1.8">اگر ارائه‌دهنده «شبیه‌سازی» باشد یا مرچنت خالی باشد، پرداخت کارت در سایت فقط رسید آزمایشی می‌سازد و پولی از بانک کم نمی‌شود. کیف پول داخلی همیشه کار می‌کند.</p>
  <button class="btn btn-solid" type="submit">ذخیره درگاه</button>
</form>
