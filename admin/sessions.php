<?php
$adminTitle = 'نشست‌های بازی';
if (is_post()) {
    csrf_check();
    $act = (string)post('act');
    if ($act === 'save') {
        $id = (int)post('id');
        $cover = pick_photo('cover', 'preset_cover', 'covers', (string)post('cover_old'));
        $fields = [
            (int)post('category_id'),
            trim((string)post('title')),
            trim((string)post('description')),
            (string)post('session_date'),
            (string)post('start_time'),
            (string)post('end_time'),
            trim((string)post('location')),
            (int)post('total_seats'),
            (int)post('price'),
            (string)post('status'),
            $cover,
            in_array((string)post('audience'), ['girls', 'mixed'], true) ? (string)post('audience') : 'boys',
            in_array((string)post('pay_type'), ['full', 'part', 'discount', 'cash'], true) ? (string)post('pay_type') : 'full',
            max(0, (int)post('online_amount')),
            max(0, (int)post('discount_price')),
            trim((string)post('venue_note')),
        ];
        if ($id) {
            q('UPDATE game_sessions SET category_id=?,title=?,description=?,session_date=?,start_time=?,end_time=?,location=?,total_seats=?,price=?,status=?,cover=?,audience=?,pay_type=?,online_amount=?,discount_price=?,venue_note=? WHERE id=?',
                [...$fields, $id]);
        } else {
            q('INSERT INTO game_sessions (category_id,title,description,session_date,start_time,end_time,location,total_seats,price,status,cover,audience,pay_type,online_amount,discount_price,venue_note,created_at)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,datetime("now"))', $fields);
        }
        flash('x','نشست ذخیره شد.','ok');
    }
    if ($act === 'del') {
        q('DELETE FROM game_sessions WHERE id=?', [(int)post('id')]);
        flash('x','نشست حذف شد.','ok');
    }
    redirect('admin/sessions?tab=list');
}
$cats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$list = q('SELECT s.*, c.name AS cat_name,
          (SELECT COUNT(*) FROM reservations r WHERE r.session_id=s.id AND r.payment_status IN ("paid_half","paid_full","venue")) AS reserved
          FROM game_sessions s JOIN categories c ON c.id=s.category_id
          ORDER BY s.session_date DESC')->fetchAll();
$ed = (int)getv('edit') ? q('SELECT * FROM game_sessions WHERE id=?', [(int)getv('edit')])->fetch() : null;
$tab = admin_current_tab('list');
admin_tabs('admin/sessions', ['add' => $ed ? 'ویرایش نشست' : 'نشست جدید', 'list' => 'فهرست'], $tab);
?>
<div class="admin-pane"<?= $tab === 'add' ? '' : ' hidden' ?>>
<div class="card" style="padding:18px;margin-bottom:18px">
  <h3><?= $ed ? 'ویرایش نشست' : 'نشست جدید' ?></h3>
  <form class="form" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="act" value="save">
    <input type="hidden" name="id" value="<?= (int)($ed['id'] ?? 0) ?>">
    <input type="hidden" name="cover_old" value="<?= e($ed['cover'] ?? '') ?>">
    <label>دسته بازی</label>
    <select name="category_id"><?php foreach ($cats as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= isset($ed['category_id']) && (int)$ed['category_id']===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
    <?php endforeach; ?></select>
    <label>عنوان</label><input name="title" required value="<?= e($ed['title'] ?? '') ?>" placeholder="شب مافیا کلاسیک">
    <label>توضیح</label><textarea name="description"><?= e($ed['description'] ?? '') ?></textarea>
    <div class="grid-2">
      <div><label>تاریخ</label><input type="date" name="session_date" required value="<?= e($ed['session_date'] ?? '') ?>"></div>
      <div><label>ساعت شروع</label><input type="time" name="start_time" required value="<?= e($ed['start_time'] ?? '20:00') ?>"></div>
      <div><label>ساعت پایان</label><input type="time" name="end_time" value="<?= e($ed['end_time'] ?? '23:00') ?>"></div>
      <div><label>مکان</label><input name="location" value="<?= e($ed['location'] ?? 'سالن اصلی گیم‌کاد') ?>"></div>
      <div><label>ظرفیت</label><input type="number" name="total_seats" value="<?= e((string)($ed['total_seats'] ?? 12)) ?>"></div>
      <div><label>قیمت (تومان)</label><input type="number" name="price" min="0" placeholder="مثلاً ۱۰۰۰۰۰" value="<?= e((string)($ed['price'] ?? '')) ?>"></div>
    </div>
    <label>چطور پول بگیرد؟</label>
    <select name="pay_type" data-pay-type>
      <?php $pt = (string)($ed['pay_type'] ?? 'full'); ?>
      <option value="full" <?= $pt==='full'?'selected':'' ?>>کل مبلغ آنلاین</option>
      <option value="part" <?= $pt==='part'?'selected':'' ?>>بخشی آنلاین، بقیه در محل</option>
      <option value="discount" <?= $pt==='discount'?'selected':'' ?>>تخفیف‌خورده — مبلغ تخفیف آنلاین</option>
      <option value="cash" <?= $pt==='cash'?'selected':'' ?>>پول فقط در محل</option>
    </select>
    <div data-pay-extra="part" <?= $pt==='part'?'':'hidden' ?>>
      <label>مبلغ آنلاین (تومان)</label>
      <input type="number" name="online_amount" min="0" placeholder="مثلاً ۴۰۰۰۰" value="<?= e((string)($ed['online_amount'] ?? '')) ?>">
    </div>
    <div data-pay-extra="discount" <?= $pt==='discount'?'':'hidden' ?>>
      <label>مبلغ بعد تخفیف (تومان)</label>
      <input type="number" name="discount_price" min="0" placeholder="مثلاً ۸۰۰۰۰" value="<?= e((string)($ed['discount_price'] ?? '')) ?>">
    </div>
    <div data-pay-extra="cash,part" <?= in_array($pt, ['cash','part'], true)?'':'hidden' ?>>
      <label>پرداخت در محل چطور است؟</label>
      <textarea name="venue_note" placeholder="مثلاً کارت‌خوان بوفه، خرید از کافه، یا نقدی دم در"><?= e($ed['venue_note'] ?? '') ?></textarea>
    </div>
    <label>میز برای</label>
    <select name="audience">
      <?php $aud = audience_of($ed ?: []); ?>
      <option value="boys" <?= $aud==='boys'?'selected':'' ?>>پسران</option>
      <option value="girls" <?= $aud==='girls'?'selected':'' ?>>دختران</option>
      <option value="mixed" <?= $aud==='mixed'?'selected':'' ?>>ترکیبی — دختر و پسر</option>
    </select>
    <label>وضعیت</label>
    <select name="status">
      <?php foreach (['upcoming'=>'پیش‌رو','ongoing'=>'در حال برگزاری','finished'=>'تمام‌شده','cancelled'=>'لغو'] as $k=>$v): ?>
        <option value="<?= $k ?>" <?= ($ed['status'] ?? '')===$k?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <label>کاور</label>
    <?php render_photo_picker('cover', 'preset_cover', (string)($ed['cover'] ?? ''), 'cover'); ?>
    <button class="btn btn-solid" type="submit">ذخیره نشست</button>
  </form>
</div>
</div>
<div class="admin-pane"<?= $tab === 'list' ? '' : ' hidden' ?>>
<div class="admin-table-wrap">
<table class="data-table">
  <thead><tr><th>تاریخ</th><th>عنوان</th><th>دسته</th><th>میز</th><th>ظرفیت</th><th>قیمت</th><th>وضعیت</th><th>عملیات</th></tr></thead>
  <tbody>
  <?php foreach ($list as $s): ?>
    <tr>
      <td><?= jdate_format($s['session_date'].' '.$s['start_time']) ?></td>
      <td><?= e($s['title']) ?></td>
      <td><?= e($s['cat_name']) ?></td>
      <td><?= e(audience_fa(audience_of($s))) ?></td>
      <td><?= fa_num($s['reserved']) ?> / <?= fa_num($s['total_seats']) ?></td>
      <td><?= money((int)$s['price']) ?><br><small><?= e(session_charge($s)['label']) ?></small></td>
      <td><?= e(status_fa($s['status'])) ?></td>
      <td>
        <a class="btn btn-sm" href="<?= e(url('admin/sessions?edit=' . (int)$s['id'])) ?>">ویرایش</a>
        <form method="post" style="display:inline"><?= csrf_field() ?>
          <input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <button class="btn btn-sm btn-danger" data-confirm="حذف نشست؟">حذف</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
