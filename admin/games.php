<?php
$adminTitle = 'بازی‌های الکترونیک';
if (is_post()) {
    csrf_check();
    $act = (string)post('act');
    if ($act === 'save') {
        $id = (int)post('id');
        $name = trim((string)post('name'));
        $slug = slugify((string)post('slug') ?: $name);
        $desc = trim((string)post('description'));
        $color = (string)post('color') ?: '#58bdaf';
        $img = pick_photo('image', 'preset_image', 'categories', (string)post('image_old'));
        if ($name === '') {
            flash('x', 'نام بازی را بنویس.', 'err');
            redirect('admin/games?tab=add' . ($id ? '&edit=' . $id : ''));
        } elseif ($id) {
            q('UPDATE categories SET name=?,slug=?,description=?,color=?,image=?,kind=? WHERE id=?',
                [$name, $slug, $desc, $color, $img, 'electronic', $id]);
            flash('x', 'بازی به‌روز شد.', 'ok');
        } else {
            q('INSERT INTO categories (name,slug,description,image,color,sort_order,has_roles,kind) VALUES (?,?,?,?,?,90,0,?)',
                [$name, $slug, $desc, $img, $color, 'electronic']);
            flash('x', 'بازی الکترونیک اضافه شد.', 'ok');
        }
    }
    if ($act === 'del') {
        $id = (int)post('id');
        $row = q("SELECT * FROM categories WHERE id=? AND kind='electronic'", [$id])->fetch();
        if ($row) {
            q('DELETE FROM categories WHERE id=?', [$id]);
            flash('x', 'حذف شد.', 'ok');
        }
    }
    if ($act === 'up' || $act === 'down') {
        $id = (int)post('id');
        $rows = q("SELECT id FROM categories WHERE kind='electronic' ORDER BY sort_order, id")->fetchAll();
        foreach ($rows as $k => $r) {
            q('UPDATE categories SET sort_order=? WHERE id=?', [$k * 10, (int)$r['id']]);
        }
        $rows = q("SELECT id, sort_order FROM categories WHERE kind='electronic' ORDER BY sort_order, id")->fetchAll();
        $i = null;
        foreach ($rows as $k => $r) {
            if ((int)$r['id'] === $id) $i = $k;
        }
        if ($i !== null) {
            $j = $act === 'up' ? $i - 1 : $i + 1;
            if (isset($rows[$j])) {
                q('UPDATE categories SET sort_order=? WHERE id=?', [(int)$rows[$j]['sort_order'], (int)$rows[$i]['id']]);
                q('UPDATE categories SET sort_order=? WHERE id=?', [(int)$rows[$i]['sort_order'], (int)$rows[$j]['id']]);
            }
        }
    }
    redirect('admin/games?tab=list');
}
$games = q("SELECT * FROM categories WHERE kind='electronic' ORDER BY sort_order, id")->fetchAll();
$ed = (int)getv('edit') ? q("SELECT * FROM categories WHERE id=? AND kind='electronic'", [(int)getv('edit')])->fetch() : null;
$tab = admin_current_tab('list');
?>
<p style="color:var(--muted);line-height:1.85;margin:0 0 16px">این‌جا فقط بازی‌های کنسولی/الکترونیک است (فیفا، PS و هر عنوان جدید). میزهای مافیا و بردگیم سر جایشان می‌مانند. برای مسابقه، از «نشست‌های بازی» یک نشست روی همین دسته بساز.</p>
<?php admin_tabs('admin/games', ['add' => $ed ? 'ویرایش بازی' : 'افزودن بازی', 'list' => 'فهرست'], $tab); ?>

<div class="admin-pane"<?= $tab === 'add' ? '' : ' hidden' ?>>
  <div class="card" style="padding:18px">
    <h3><?= $ed ? 'ویرایش بازی' : 'بازی الکترونیک جدید' ?></h3>
    <form class="form" method="post" enctype="multipart/form-data" style="margin-top:12px">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="id" value="<?= (int)($ed['id'] ?? 0) ?>">
      <input type="hidden" name="image_old" value="<?= e($ed['image'] ?? '') ?>">
      <label>نام</label>
      <input name="name" required placeholder="مثلاً فیفا ۲۵" value="<?= e($ed['name'] ?? '') ?>">
      <label>اسلاگ</label>
      <input name="slug" placeholder="fifa" value="<?= e($ed['slug'] ?? '') ?>" dir="ltr" class="ltr-input">
      <label>توضیح</label>
      <textarea name="description" placeholder="مسابقات، پلتفرم، قوانین کوتاه"><?= e($ed['description'] ?? '') ?></textarea>
      <label>رنگ</label>
      <input name="color" value="<?= e($ed['color'] ?? '#58bdaf') ?>">
      <label>تصویر</label>
      <?php render_photo_picker('image', 'preset_image', (string)($ed['image'] ?? ''), 'cover'); ?>
      <button class="btn btn-solid" type="submit"><?= $ed ? 'ذخیره' : 'افزودن بازی' ?></button>
      <?php if ($ed): ?><a class="btn btn-ghost" href="<?= e(url('admin/games?tab=add')) ?>">انصراف</a><?php endif; ?>
    </form>
  </div>
</div>
<div class="admin-pane"<?= $tab === 'list' ? '' : ' hidden' ?>>
  <div class="card" style="padding:8px">
    <h3 style="padding:12px">لیست</h3>
    <?php if (!$games): ?><p class="empty">هنوز چیزی نیست.</p><?php endif; ?>
    <div class="night-list" style="padding:8px">
      <?php foreach ($games as $g): ?>
        <article class="night-card">
          <div class="night-card-head">
            <b><?= e($g['name']) ?></b>
            <small><?= e($g['slug']) ?></small>
          </div>
          <div class="night-actions">
            <a class="btn btn-sm" href="<?= e(url('admin/games?edit=' . (int)$g['id'])) ?>">ویرایش</a>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
              <button class="btn btn-sm" name="act" value="up" type="submit">بالا</button>
              <button class="btn btn-sm" name="act" value="down" type="submit">پایین</button>
            </form>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
              <button class="btn btn-sm btn-danger" data-confirm="این بازی الکترونیک حذف شود؟">حذف</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</div>
