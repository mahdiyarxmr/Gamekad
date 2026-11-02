<?php
$adminTitle = 'آموزش';
$me = current_user();
$uid = (int)$me['id'];

if (is_post()) {
    csrf_check();
    $act = (string)post('act');
    if ($act === 'save') {
        $id = (int)post('id');
        $ed = $id ? q('SELECT * FROM blog_posts WHERE id=?', [$id])->fetch() : null;
        if ($id && !can_edit_post($ed ?: null)) {
            flash('x','اجازه ویرایش این مطلب را نداری.','err');
            redirect('admin/blog?tab=list');
        }
        $titleP = trim((string)post('title'));
        $slug = slugify((string)post('slug') ?: $titleP);
        $excerpt = trim((string)post('excerpt'));
        $content = (string)post('content');
        $type = (string)post('type');
        $cat = (int)post('category_id') ?: null;
        $video = trim((string)post('video_url'));
        $pub = post('published') ? 1 : 0;
        $cover = pick_photo('cover', 'preset_cover', 'blog', (string)($ed['cover'] ?? ''));
        if ($id) {
            q('UPDATE blog_posts SET title=?,slug=?,excerpt=?,content=?,cover=?,category_id=?,type=?,video_url=?,published=? WHERE id=?',
                [$titleP,$slug,$excerpt,$content,$cover,$cat,$type,$video,$pub,$id]);
            $postId = $id;
        } else {
            q('INSERT INTO blog_posts (title,slug,excerpt,content,cover,category_id,type,video_url,author_id,published,created_at)
               VALUES (?,?,?,?,?,?,?,?,?,?,datetime("now"))',
                [$titleP,$slug,$excerpt,$content,$cover,$cat,$type,$video,$uid,$pub]);
            $postId = (int)db()->lastInsertId();
        }
        $authorId = $id ? (int)$ed['author_id'] : $uid;
        if (is_admin() || $authorId === $uid) {
            q('DELETE FROM blog_editors WHERE post_id=?', [$postId]);
            foreach ((array)post('editors') as $eid) {
                $eid = (int)$eid;
                if ($eid && $eid !== $authorId) {
                    q('INSERT OR IGNORE INTO blog_editors (post_id,user_id) VALUES (?,?)', [$postId, $eid]);
                }
            }
        }
        flash('x','مطلب ذخیره شد.','ok');
    }
    if ($act === 'del') {
        $row = q('SELECT * FROM blog_posts WHERE id=?', [(int)post('id')])->fetch();
        if (!can_delete_post($row ?: null)) {
            flash('x','فقط نویسنده یا ادمین می‌تواند حذف کند.','err');
        } else {
            q('DELETE FROM blog_posts WHERE id=?', [(int)$row['id']]);
            flash('x','حذف شد.','ok');
        }
    }
    redirect('admin/blog?tab=list');
}

$cats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
if (is_admin()) {
    $list = q('SELECT p.*, c.name AS cat_name, u.display_name AS author
               FROM blog_posts p LEFT JOIN categories c ON c.id=p.category_id
               LEFT JOIN users u ON u.id=p.author_id ORDER BY p.created_at DESC')->fetchAll();
} else {
    $list = q('SELECT p.*, c.name AS cat_name, u.display_name AS author
               FROM blog_posts p LEFT JOIN categories c ON c.id=p.category_id
               LEFT JOIN users u ON u.id=p.author_id
               WHERE p.author_id=? OR p.id IN (SELECT post_id FROM blog_editors WHERE user_id=?)
               ORDER BY p.created_at DESC', [$uid, $uid])->fetchAll();
}
$ed = (int)getv('edit') ? q('SELECT * FROM blog_posts WHERE id=?', [(int)getv('edit')])->fetch() : null;
if ($ed && !can_edit_post($ed)) {
    flash('x','اجازه ویرایش این مطلب را نداری.','err');
    redirect('admin/blog?tab=list');
}
$editorIds = [];
if ($ed) {
    $editorIds = array_map('intval', array_column(q('SELECT user_id FROM blog_editors WHERE post_id=?', [(int)$ed['id']])->fetchAll(), 'user_id'));
}
$people = q('SELECT id, display_name, username, role FROM users ORDER BY display_name')->fetchAll();
$canGrant = !$ed || is_admin() || (int)$ed['author_id'] === $uid;
$tab = admin_current_tab('list');
admin_tabs('admin/blog', ['add' => $ed ? 'ویرایش مطلب' : 'مطلب جدید', 'list' => 'فهرست'], $tab);
?>
<div class="admin-pane"<?= $tab === 'add' ? '' : ' hidden' ?>>
<div class="card" style="padding:18px;margin-bottom:18px">
  <h3><?= $ed ? 'ویرایش مطلب' : 'مطلب جدید' ?></h3>
  <form class="form" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="act" value="save">
    <input type="hidden" name="id" value="<?= (int)($ed['id'] ?? 0) ?>">
    <label>عنوان</label><input name="title" required value="<?= e($ed['title'] ?? '') ?>" placeholder="عنوان مطلب">
    <label>اسلاگ</label><input name="slug" value="<?= e($ed['slug'] ?? '') ?>" placeholder="مثلاً amozesh-mafia" dir="ltr" class="ltr-input">
    <label>خلاصه</label><textarea name="excerpt" placeholder="یک بند کوتاه"><?= e($ed['excerpt'] ?? '') ?></textarea>
    <label>متن (HTML مجاز)</label><textarea name="content" style="min-height:220px" placeholder="متن مطلب"><?= e($ed['content'] ?? '') ?></textarea>
    <div class="grid-2">
      <div>
        <label>نوع</label>
        <select name="type">
          <?php foreach (['tutorial'=>'آموزش','scenario'=>'سناریو','news'=>'خبر'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= ($ed['type'] ?? '')===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>دسته بازی</label>
        <select name="category_id">
          <option value="0">—</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= isset($ed['category_id']) && (int)$ed['category_id']===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <label>لینک ویدیو (یوتیوب / آپارات)</label>
    <input name="video_url" value="<?= e($ed['video_url'] ?? '') ?>" placeholder="https://...">
    <label>کاور</label>
    <?php render_photo_picker('cover', 'preset_cover', (string)($ed['cover'] ?? ''), 'cover'); ?>
    <label><input type="checkbox" name="published" <?= !isset($ed['published']) || $ed['published'] ? 'checked' : '' ?>> منتشر شود</label>
    <?php if ($canGrant): ?>
      <label>چه کسانی بتوانند این مطلب را ویرایش کنند</label>
      <p style="color:var(--muted);font-size:12px;margin-top:-6px">فقط برای مطالبی که خودت نوشته‌ای. ادمین همیشه دسترسی دارد.</p>
      <select name="editors[]" multiple size="6" style="min-height:120px">
        <?php foreach ($people as $p): if ((int)$p['id'] === (int)($ed['author_id'] ?? $uid)) continue; ?>
          <option value="<?= (int)$p['id'] ?>" <?= in_array((int)$p['id'], $editorIds, true)?'selected':'' ?>>
            <?= e($p['display_name']) ?> · <?= e(status_fa($p['role'])) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <button class="btn btn-solid" type="submit">ذخیره مطلب</button>
  </form>
</div>
</div>
<div class="admin-pane"<?= $tab === 'list' ? '' : ' hidden' ?>>
<div class="admin-table-wrap">
<table class="data-table">
  <thead><tr><th>عنوان</th><th>نویسنده</th><th>نوع</th><th>دسته</th><th>تاریخ</th><th>عملیات</th></tr></thead>
  <tbody>
  <?php foreach ($list as $p): ?>
    <tr>
      <td><?= e($p['title']) ?></td>
      <td><?= e($p['author'] ?? '') ?></td>
      <td><?= e(status_fa($p['type'])) ?></td>
      <td><?= e($p['cat_name'] ?? '') ?></td>
      <td><?= jdate_format($p['created_at'], 'date') ?></td>
      <td>
        <a class="btn btn-sm" href="<?= e(url('admin/blog?edit=' . (int)$p['id'])) ?>">ویرایش</a>
        <a class="btn btn-sm btn-ghost" href="<?= e(url('blog/' . $p['slug'])) ?>" target="_blank">نمایش</a>
        <?php if (can_delete_post($p)): ?>
        <form method="post" style="display:inline"><?= csrf_field() ?>
          <input type="hidden" name="act" value="del"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button class="btn btn-sm btn-danger" data-confirm="حذف مطلب؟">حذف</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
