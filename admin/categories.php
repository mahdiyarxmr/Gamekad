<?php
$adminTitle = 'دسته‌ها، نقش‌ها، سناریوها';
if (is_post()) {
    csrf_check();
    $act = (string)post('act');
    if ($act === 'cat') {
        $id = (int)post('id');
        $name = trim((string)post('name'));
        $slug = slugify((string)post('slug') ?: $name);
        $desc = trim((string)post('description'));
        $color = (string)post('color') ?: '#c9a227';
        $has = post('has_roles') ? 1 : 0;
        $img = pick_photo('image', 'preset_image', 'categories', (string)post('image_old'));
        if ($id) {
            $kind = (string)post('kind') === 'electronic' ? 'electronic' : 'table';
            q('UPDATE categories SET name=?,slug=?,description=?,color=?,has_roles=?,image=?,kind=? WHERE id=?',
                [$name,$slug,$desc,$color,$has,$img,$kind,$id]);
        } else {
            $kind = (string)post('kind') === 'electronic' ? 'electronic' : 'table';
            q('INSERT INTO categories (name,slug,description,image,color,sort_order,has_roles,kind) VALUES (?,?,?,?,?,99,?,?)',
                [$name,$slug,$desc,$img,$color,$has,$kind]);
        }
        flash('x','دسته ذخیره شد.','ok');
    }
    if ($act === 'role') {
        $scenId = (int)post('scenario_id');
        $scen = $scenId ? q('SELECT * FROM scenarios WHERE id=?', [$scenId])->fetch() : null;
        $cid = $scen ? (int)$scen['category_id'] : (int)post('category_id');
        $wake = post('night_wake') ? 1 : 0;
        q('INSERT INTO roles (category_id,scenario_id,name,side,color,description,symbol,night_wake) VALUES (?,?,?,?,?,?,?,?)',
            [$cid, $scenId, trim((string)post('name')), (string)post('side'), (string)post('color') ?: '#c9a227',
             trim((string)post('role_description')), trim((string)post('symbol')), $wake]);
        flash('x','نقش اضافه شد.','ok');
    }
    if ($act === 'role_wake') {
        $rid = (int)post('id');
        $cur = q('SELECT night_wake FROM roles WHERE id=?', [$rid])->fetch();
        if ($cur) {
            q('UPDATE roles SET night_wake=? WHERE id=?', [empty($cur['night_wake']) ? 1 : 0, $rid]);
        }
    }
    if ($act === 'scenario') {
        $sid = (int)post('id');
        $sname = trim((string)post('name'));
        $sslug = slugify((string)post('slug') ?: $sname);
        $sdesc = trim((string)post('description'));
        $showto = (string)post('howto');
        $cid = (int)post('category_id');
        if ($sid) {
            q('UPDATE scenarios SET category_id=?,name=?,description=?,slug=?,howto=? WHERE id=?',
                [$cid, $sname, $sdesc, $sslug, $showto, $sid]);
            flash('x','سناریو به‌روز شد.','ok');
        } else {
            q('INSERT INTO scenarios (category_id,name,description,slug,howto) VALUES (?,?,?,?,?)',
                [$cid, $sname, $sdesc, $sslug, $showto]);
            flash('x','سناریو اضافه شد.','ok');
        }
    }
    if ($act === 'del_role') q('DELETE FROM roles WHERE id=?', [(int)post('id')]);
    if ($act === 'del_scen') q('DELETE FROM scenarios WHERE id=?', [(int)post('id')]);
    $tabGo = 'cats';
    if ($act === 'role' || $act === 'del_role' || $act === 'role_wake') {
        $tabGo = 'roles';
    } elseif ($act === 'scenario' || $act === 'del_scen') {
        $tabGo = 'scens';
    }
    redirect('admin/categories?tab=' . $tabGo);
}
$cats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$roles = q('SELECT r.*, c.name AS cat, sc.name AS scen
            FROM roles r JOIN categories c ON c.id=r.category_id
            LEFT JOIN scenarios sc ON sc.id=r.scenario_id
            ORDER BY sc.id, r.id')->fetchAll();
$scens = q('SELECT s.*, c.name AS cat FROM scenarios s JOIN categories c ON c.id=s.category_id ORDER BY c.sort_order, s.id')->fetchAll();
$tab = (string)getv('tab');
if ($tab === '') {
    if ((int)getv('scen')) {
        $tab = 'scens';
    } elseif ((int)getv('edit')) {
        $tab = 'cats';
    } else {
        $tab = 'cats';
    }
}
if (!in_array($tab, ['cats', 'roles', 'scens'], true)) {
    $tab = 'cats';
}
admin_tabs('admin/categories', ['cats' => 'دسته‌ها', 'roles' => 'نقش‌ها', 'scens' => 'سناریوها'], $tab);
?>
<div class="admin-pane"<?= $tab === 'cats' ? '' : ' hidden' ?>>
  <div class="card" style="padding:18px">
    <h3>دسته جدید / ویرایش</h3>
    <form class="form" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="act" value="cat">
      <label>ویرایش دسته موجود (اختیاری)</label>
      <select name="id" onchange="location=<?= json_encode(url('admin/categories'), JSON_UNESCAPED_SLASHES) ?>+(this.value?('?tab=cats&edit='+this.value):'?tab=cats')">
        <option value="0">— جدید —</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)getv('edit')===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php $ed = (int)getv('edit') ? q('SELECT * FROM categories WHERE id=?', [(int)getv('edit')])->fetch() : null; ?>
      <input type="hidden" name="image_old" value="<?= e($ed['image'] ?? '') ?>">
      <label>نام</label><input name="name" required value="<?= e($ed['name'] ?? '') ?>">
      <label>اسلاگ</label><input name="slug" value="<?= e($ed['slug'] ?? '') ?>">
      <label>توضیح</label><textarea name="description"><?= e($ed['description'] ?? '') ?></textarea>
      <label>رنگ</label><input name="color" value="<?= e($ed['color'] ?? '#c9a227') ?>">
      <label>تصویر</label>
      <?php render_photo_picker('image', 'preset_image', (string)($ed['image'] ?? ''), 'cover'); ?>
      <label>نوع</label>
      <select name="kind">
        <option value="table" <?= (($ed['kind'] ?? 'table')!=='electronic')?'selected':'' ?>>میز بازی (مافیا، بردگیم…)</option>
        <option value="electronic" <?= (($ed['kind'] ?? '')==='electronic')?'selected':'' ?>>الکترونیک (فیفا، PS…)</option>
      </select>
      <label><input type="checkbox" name="has_roles" <?= !empty($ed['has_roles'])?'checked':'' ?>> این بازی نقش دارد (مافیا/گرگینه)</label>
      <button class="btn btn-solid" type="submit">ذخیره دسته</button>
    </form>
  </div>
</div>

<div class="admin-pane"<?= $tab === 'roles' ? '' : ' hidden' ?>>
  <div class="card" style="padding:18px;margin-bottom:16px">
    <h3>نقش جدید</h3>
    <form class="form" method="post">
      <?= csrf_field() ?><input type="hidden" name="act" value="role">
      <select name="scenario_id">
        <?php foreach ($scens as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['cat']) ?> / <?= e($s['name']) ?></option><?php endforeach; ?>
      </select>
      <input name="name" placeholder="نام نقش مثلاً بازپرس" required>
      <select name="side"><option value="citizen">شهر</option><option value="mafia">مافیا/گرگ</option><option value="indie">مستقل</option></select>
      <input name="color" value="#c9a227">
      <input name="symbol" placeholder="نماد مثلاً 🔍" maxlength="8">
      <textarea name="role_description" placeholder="توضیح پشت کارت نقش"></textarea>
      <label><input type="checkbox" name="night_wake" value="1"> شب بیدار می‌شود (برای سناریوی دستی)</label>
      <button class="btn" type="submit">افزودن نقش</button>
    </form>
  </div>
  <div class="admin-table-wrap">
    <table class="data-table">
      <thead><tr><th>سناریو</th><th>نقش</th><th>سمت</th><th>شب</th><th>عملیات</th></tr></thead>
      <tbody>
      <?php foreach ($roles as $r): ?>
        <tr>
          <td><?= e($r['scen'] ?: $r['cat']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['side']) ?></td>
          <td>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="role_wake"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-sm<?= !empty($r['night_wake']) ? ' btn-solid' : '' ?>" type="submit"><?= !empty($r['night_wake']) ? 'بیدار' : 'نمی‌شود' ?></button></form>
          </td>
          <td>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="del_role"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-sm btn-danger" data-confirm="حذف نقش؟">حذف</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="admin-pane"<?= $tab === 'scens' ? '' : ' hidden' ?>>
  <div class="card" style="padding:18px;margin-bottom:16px">
    <h3>سناریو و نحوه بازی</h3>
    <?php $eds = (int)getv('scen') ? q('SELECT * FROM scenarios WHERE id=?', [(int)getv('scen')])->fetch() : null; ?>
    <form class="form" method="post">
      <?= csrf_field() ?><input type="hidden" name="act" value="scenario">
      <input type="hidden" name="id" value="<?= (int)($eds['id'] ?? 0) ?>">
      <label>ویرایش سناریوی موجود</label>
      <select onchange="location=<?= json_encode(url('admin/categories'), JSON_UNESCAPED_SLASHES) ?>+(this.value?('?tab=scens&scen='+this.value):'?tab=scens')">
        <option value="">— جدید —</option>
        <?php foreach ($scens as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= (int)($eds['id'] ?? 0)===(int)$s['id']?'selected':'' ?>><?= e($s['cat'].' / '.$s['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="category_id">
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= (int)($eds['category_id'] ?? 0)===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input name="name" placeholder="مثلاً آخرین حرکت" required value="<?= e($eds['name'] ?? '') ?>">
      <input name="slug" placeholder="اسلاگ انگلیسی مثلاً last-move" value="<?= e($eds['slug'] ?? '') ?>">
      <textarea name="description" placeholder="توضیح کوتاه روی کارت سناریو"><?= e($eds['description'] ?? '') ?></textarea>
      <textarea name="howto" placeholder="نحوه بازی (HTML مجاز)" style="min-height:160px"><?= e($eds['howto'] ?? '') ?></textarea>
      <button class="btn" type="submit"><?= $eds ? 'ذخیره سناریو' : 'افزودن سناریو' ?></button>
    </form>
  </div>
  <div class="admin-table-wrap">
    <table class="data-table">
      <thead><tr><th>دسته</th><th>سناریو</th><th></th><th>عملیات</th></tr></thead>
      <tbody>
      <?php foreach ($scens as $s): ?>
        <tr>
          <td><?= e($s['cat']) ?></td><td><?= e($s['name']) ?></td>
          <td><a class="btn btn-sm" href="<?= e(url('admin/categories?tab=scens&scen=' . (int)$s['id'])) ?>">آموزش</a></td>
          <td>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="del_scen"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
              <button class="btn btn-sm btn-danger" data-confirm="حذف سناریو؟">حذف</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
