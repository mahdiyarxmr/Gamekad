<?php
$adminTitle = 'چینش صفحهٔ اصلی';
if (is_post()) {
    csrf_check();
    $ids = $_POST['order'] ?? [];
    $on = $_POST['on'] ?? [];
    $def = home_layout_default();
    $by = [];
    foreach ($def as $d) {
        $by[$d['id']] = $d;
    }
    $out = [];
    $seen = [];
    foreach ((array)$ids as $id) {
        $id = (string)$id;
        if (!isset($by[$id]) || isset($seen[$id])) {
            continue;
        }
        $out[] = ['id' => $id, 'on' => in_array($id, (array)$on, true) ? 1 : 0];
        $seen[$id] = 1;
    }
    foreach ($def as $d) {
        if (!isset($seen[$d['id']])) {
            $out[] = ['id' => $d['id'], 'on' => 1];
        }
    }
    setting('home_layout', json_encode($out, JSON_UNESCAPED_UNICODE));
    flash('x', 'چینش خانه ذخیره شد.', 'ok');
    redirect('admin/layout');
}
$layout = home_layout();
?>
<p style="color:var(--muted);line-height:1.85;margin:0 0 16px">مثل بلوک‌های صفحه: بکش و جابه‌جا کن، چشم را خاموش کن تا بخش از خانه برداشته شود. هیرو را می‌شود پایین‌تر آورد.</p>
<form method="post" id="layoutForm">
  <?= csrf_field() ?>
  <ol class="layout-list" id="layoutList">
    <?php foreach ($layout as $i => $sec): ?>
      <li class="layout-item" draggable="true" data-id="<?= e($sec['id']) ?>">
        <span class="layout-handle" aria-hidden="true">⋮⋮</span>
        <input type="hidden" name="order[]" value="<?= e($sec['id']) ?>">
        <label class="layout-on">
          <input type="checkbox" name="on[]" value="<?= e($sec['id']) ?>" <?= !empty($sec['on']) ? 'checked' : '' ?>>
          <span><?= e($sec['label']) ?></span>
        </label>
        <span class="layout-id"><?= e($sec['id']) ?></span>
      </li>
    <?php endforeach; ?>
  </ol>
  <button class="btn btn-solid" type="submit" style="margin-top:16px">ذخیره چینش</button>
  <a class="btn" href="<?= e(url('')) ?>" target="_blank" rel="noopener">پیش‌نمایش خانه</a>
</form>
<script>
(function () {
  const list = document.getElementById('layoutList');
  if (!list) return;
  let drag = null;
  list.querySelectorAll('.layout-item').forEach((item) => {
    item.addEventListener('dragstart', () => {
      drag = item;
      item.classList.add('is-drag');
    });
    item.addEventListener('dragend', () => {
      item.classList.remove('is-drag');
      drag = null;
    });
    item.addEventListener('dragover', (e) => {
      e.preventDefault();
      const over = e.currentTarget;
      if (!drag || over === drag) return;
      const rect = over.getBoundingClientRect();
      const before = e.clientY < rect.top + rect.height / 2;
      list.insertBefore(drag, before ? over : over.nextSibling);
    });
  });
})();
</script>
