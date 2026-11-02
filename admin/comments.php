<?php
$adminTitle = 'نظرات';
if (is_post()) {
    csrf_check();
    $act = (string)post('act');
    $id = (int)post('id');
    if ($act === 'feature' && $id) {
        $row = q('SELECT featured FROM comments WHERE id=?', [$id])->fetch();
        if ($row) {
            q('UPDATE comments SET featured=? WHERE id=?', [((int)$row['featured'] === 1) ? 0 : 1, $id]);
            flash('x', (int)$row['featured'] === 1 ? 'از صفحه اصلی برداشته شد.' : 'روی صفحه اصلی آمد.', 'ok');
        }
    }
    if ($act === 'delete' && $id) {
        q('DELETE FROM comments WHERE id=?', [$id]);
        flash('x', 'نظر حذف شد.', 'ok');
    }
    redirect('admin/comments');
}
$list = q('SELECT c.*, u.avatar, u.username
           FROM comments c LEFT JOIN users u ON u.id=c.user_id
           ORDER BY c.featured DESC, c.id DESC')->fetchAll();
?>
<p style="color:var(--muted);font-size:13px;line-height:1.8;margin-bottom:14px">نظرهایی که «نمایش در خانه» دارند روی صفحه اصلی می‌آیند. بقیه فقط اینجاست.</p>
<div class="admin-table-wrap">
<table class="data-table">
  <thead><tr><th>نام</th><th>نظر</th><th>تاریخ</th><th>خانه</th><th></th></tr></thead>
  <tbody>
  <?php if (!$list): ?>
    <tr><td colspan="5"><p class="empty">هنوز نظری نیامده.</p></td></tr>
  <?php endif; ?>
  <?php foreach ($list as $c): ?>
    <tr>
      <td><?= e($c['name']) ?><?php if (!empty($c['username'])): ?><br><span style="color:var(--muted);font-size:12px;direction:ltr">@<?= e($c['username']) ?></span><?php endif; ?></td>
      <td style="white-space:normal;max-width:360px;line-height:1.8"><?= e($c['body']) ?></td>
      <td><?= jdate_format($c['created_at'], 'short') ?></td>
      <td>
        <form method="post"><?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button class="btn btn-sm <?= (int)$c['featured'] ? 'btn-ok' : '' ?>" name="act" value="feature" type="submit">
            <?= (int)$c['featured'] ? 'روی خانه' : 'انتخاب برای خانه' ?>
          </button>
        </form>
      </td>
      <td>
        <form method="post"><?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button class="btn btn-sm btn-danger" name="act" value="delete" type="submit" data-confirm="این نظر حذف شود؟">حذف</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
