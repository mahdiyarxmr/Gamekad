</main>
<footer class="footer">
  <div class="wrap footer-grid">
    <div>
      <a class="brand" href="<?= e(url('')) ?>" style="margin-bottom:12px">
        <img src="<?= e(asset('img/logo-emblem.png')) ?>" alt="" width="40" height="40">
        <span><b class="en">GAMEKAD</b><small><?= e(SITE_TAGLINE) ?></small></span>
      </a>
      <p><?= e(setting('about') ?: 'رکاد، هنرستان استارتاپی.') ?></p>
    </div>
    <div class="desk-only">
      <h4>دسترسی</h4>
      <a href="<?= e(url('leaderboard')) ?>">لیدربورد</a><br>
      <a href="<?= e(url('events')) ?>">رویدادها</a><br>
      <a href="<?= e(url('egames')) ?>">بازی الکترونیک</a><br>
      <a href="<?= e(url('guide')) ?>">آموزش نقش و سناریو</a><br>
      <a href="<?= e(url('blog')) ?>">آموزش نوشته</a><br>
      <a href="<?= e(url('players')) ?>">پروفایل بازیکنان</a>
    </div>
    <div>
      <h4>رکاد</h4>
      <p><?= e(setting('cafe_address')) ?></p>
      <p>ساعت: <?= e(setting('cafe_hours')) ?></p>
      <p>تلفن: <a href="tel:<?= e(preg_replace('/\s+/', '', setting('cafe_phone'))) ?>"><?= e(setting('cafe_phone')) ?></a></p>
    </div>
  </div>
  <?php $trusts = trust_logos(); if ($trusts): ?>
  <div class="wrap trust-row">
    <?php foreach ($trusts as $t): ?>
      <img src="<?= e(media($t)) ?>" alt="نماد اعتماد">
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="copy">GAMEKAD · ROKAD · <?= fa_num(1405) ?></div>
</footer>

<nav class="tabbar" aria-label="منوی اپ">
  <a class="<?= active_nav('') ?>" href="<?= e(url('')) ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/></svg>
    <span>خانه</span>
  </a>
  <a class="<?= active_nav('events') ?>" href="<?= e(url('events')) ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v4H5zm0 6h14v10H5zm3 3h8v2H8z"/></svg>
    <span>رویداد</span>
  </a>
  <a class="<?= trim(active_nav('guide').' '.active_nav('roles')) ?>" href="<?= e(url('guide')) ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16l-7-3-7 3V5a2 2 0 0 1 2-2z"/></svg>
    <span>آموزش</span>
  </a>
  <a class="<?= active_nav('leaderboard') ?>" href="<?= e(url('leaderboard')) ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 10h3v10H8zm5-6h3v16h-3zM3 14h3v6H3z"/></svg>
    <span>برد</span>
  </a>
  <?php if (!empty($u) && can_staff()): ?>
  <a class="<?= trim(active_nav('admin').' '.active_nav('godgame')) ?>" href="<?= e(url(staff_home())) ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v2H4zm0 6h16v2H4zm0 6h16v2H4z"/></svg>
    <span>پنل</span>
  </a>
  <?php endif; ?>
  <a class="<?= trim(active_nav('account').' '.active_nav('login').' '.active_nav('register').' '.active_nav('wallet')) ?>" href="<?= e($u ? url('account') : url('login')) ?>">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-4 0-8 2-8 5v1h16v-1c0-3-4-5-8-5z"/></svg>
    <span>من</span>
  </a>
</nav>

<div class="install-bar" id="installBar" hidden>
  <span>اپ رکاد را روی گوشی نصب کن</span>
  <button type="button" class="btn btn-solid btn-sm" id="installBtn">نصب</button>
  <button type="button" class="install-x" id="installDismiss" aria-label="بستن">×</button>
</div>

<script src="<?= e(asset('js/chart.umd.min.js')) ?>"></script>
<script src="<?= e(asset('js/main.js')) ?>?v=d15"></script>
</body>
</html>
