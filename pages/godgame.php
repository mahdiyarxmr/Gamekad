<?php
require_login();
require_godgame();
header('Cache-Control: no-store, no-cache, must-revalidate');
$title = 'خدای بازی';
$boot = god_catalog();
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">GODGAME</div>
    <h1>خدای بازی</h1>
    <p class="sub">پخش نقش، گرداندگی شب با تایمر، MVP و امتیاز لیدربورد. میز روی همین گوشی می‌ماند و آفلاین هم کار می‌کند.</p>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap">
    <?php require ROOT . '/includes/godgame_panel.php'; ?>
  </div>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
