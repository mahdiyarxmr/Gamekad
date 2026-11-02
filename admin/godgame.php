<?php
require_godgame();
header('Cache-Control: no-store, no-cache, must-revalidate');
$adminTitle = 'خدای بازی';
$boot = god_catalog();
?>
<p class="god-offline-note">میز روی همین گوشی ذخیره می‌شود. یک‌بار با اینترنت باز کن؛ بعد آفلاین هم نقش می‌دهی.</p>
<?php require ROOT . '/includes/godgame_panel.php'; ?>
