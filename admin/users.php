<?php
$adminTitle = 'کاربران';
if (is_post()) {
    csrf_check();
    $act = (string)post('act');
    $id = (int)post('id');
    $me = (int)current_user()['id'];

    if ($act === 'create') {
        $errs = auth_register([
            'display_name' => post('display_name'),
            'username' => post('username'),
            'email' => post('email'),
            'phone' => post('phone'),
            'password' => post('password'),
            'role' => post('role'),
            'origin' => post('origin'),
            'gender' => post('gender'),
            '_admin_create' => 1,
        ]);
        if ($errs) {
            flash('x', implode(' ', $errs), 'err');
            redirect('admin/users?tab=add');
        }
        flash('x', 'حساب ساخته شد.', 'ok');
        redirect('admin/users?tab=list');
    }

    if ($act === 'role' && $id && $id !== $me) {
        $want = (string)post('role');
        $role = in_array($want, assignable_roles(), true) ? $want : 'user';
        $target = q('SELECT role FROM users WHERE id=?', [$id])->fetch();
        $from = $target['role'] ?? '';
        if ($from === 'administrator' && !is_administrator()) {
            flash('x', 'ادمین عادی نمی‌تواند Administrator را تغییر دهد.', 'err');
            redirect('admin/users');
        }
        if ($from === 'administrator' && $role !== 'administrator') {
            $supers = (int)q('SELECT COUNT(*) FROM users WHERE role="administrator"')->fetchColumn();
            if ($supers <= 1) {
                flash('x', 'نمی‌شود آخرین Administrator را پایین آورد.', 'err');
                redirect('admin/users');
            }
        }
        if (in_array($from, ['admin', 'administrator'], true) && !in_array($role, ['admin', 'administrator'], true)) {
            $staff = (int)q('SELECT COUNT(*) FROM users WHERE role IN ("admin","administrator")')->fetchColumn();
            if ($staff <= 1) {
                flash('x', 'نمی‌شود آخرین ادمین را به بازیکن تبدیل کرد.', 'err');
                redirect('admin/users');
            }
        }
        q('UPDATE users SET role=? WHERE id=?', [$role, $id]);
        flash('x', 'نقش به‌روز شد.', 'ok');
    }
    if ($act === 'origin' && $id) {
        $origin = (string)post('origin') === 'guest' ? 'guest' : 'rokad';
        q('UPDATE users SET origin=? WHERE id=?', [$origin, $id]);
        flash('x', 'محل بازیکن به‌روز شد.', 'ok');
    }
    if ($act === 'gender' && $id) {
        $g = (string)post('gender') === 'girl' ? 'girl' : 'boy';
        q('UPDATE users SET gender=? WHERE id=?', [$g, $id]);
        flash('x', 'جنسیت به‌روز شد.', 'ok');
    }
    if ($act === 'wallet' && $id) {
        $delta = (int)post('delta');
        if ($delta !== 0) {
            db_immediate(function () use ($id, $delta) {
                q('UPDATE users SET wallet = COALESCE(wallet,0) + ? WHERE id=?', [$delta, $id]);
                q('INSERT INTO wallet_tx (user_id,amount,type,status,note,created_at) VALUES (?,?,?,?,?,datetime("now"))',
                    [$id, $delta, 'admin_adjust', 'success', 'تنظیم از پنل']);
            });
            flash('x', 'کیف پول به‌روز شد.', 'ok');
        }
    }

    if ($act === 'delete' && $id && $id !== $me) {
        $target = q('SELECT * FROM users WHERE id=?', [$id])->fetch();
        if ($target) {
            if ($target['role'] === 'administrator' && !is_administrator()) {
                flash('x', 'ادمین عادی نمی‌تواند Administrator را حذف کند.', 'err');
                redirect('admin/users');
            }
            if ($target['role'] === 'administrator') {
                $supers = (int)q('SELECT COUNT(*) FROM users WHERE role="administrator"')->fetchColumn();
                if ($supers <= 1) {
                    flash('x', 'آخرین Administrator را نمی‌شود پاک کرد.', 'err');
                    redirect('admin/users');
                }
            } elseif ($target['role'] === 'admin') {
                $staff = (int)q('SELECT COUNT(*) FROM users WHERE role IN ("admin","administrator")')->fetchColumn();
                if ($staff <= 1) {
                    flash('x', 'آخرین ادمین را نمی‌شود پاک کرد.', 'err');
                    redirect('admin/users');
                }
            }
            q('DELETE FROM users WHERE id=?', [$id]);
            flash('x', 'حساب «' . $target['display_name'] . '» حذف شد.', 'ok');
        }
    }

    redirect('admin/users?tab=list');
}
$users = q('SELECT * FROM users ORDER BY id')->fetchAll();
$tab = admin_current_tab('list');
admin_tabs('admin/users', ['add' => 'افزودن کاربر', 'list' => 'کاربران'], $tab);
?>
<div class="admin-pane"<?= $tab === 'add' ? '' : ' hidden' ?>>
<div class="card" style="padding:18px;margin-bottom:18px">
  <h3>ساخت حساب جدید</h3>
  <form class="form" method="post" style="margin-top:12px">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="create">
    <div class="grid-2">
      <div><label>نام نمایشی</label><input name="display_name" required placeholder="مثلاً سارا محمدی"></div>
      <div><label>نام کاربری</label><input name="username" required placeholder="مثلاً sara" dir="ltr" class="ltr-input"></div>
      <div><label>ایمیل</label><input name="email" type="email" required placeholder="مثلاً sara@rokad.ir" dir="ltr" class="ltr-input"></div>
      <div><label>موبایل</label><input name="phone" placeholder="مثلاً 09332881180" dir="ltr" class="ltr-input"></div>
      <div><label>رمز عبور</label><input name="password" type="password" required minlength="6" placeholder="رمز عبور خود را وارد کنید"></div>
      <div>
        <label>نوع حساب</label>
        <select name="role">
          <option value="user">بازیکن</option>
          <option value="godgame">گادگیم (خدای بازی)</option>
          <option value="admin">ادمین</option>
          <?php if (is_administrator()): ?><option value="administrator">ادمین سطح ۲ (Administrator)</option><?php endif; ?>
        </select>
      </div>
      <div>
        <label>از کجاست</label>
        <select name="origin">
          <option value="rokad">رکاد (هنرستان)</option>
          <option value="guest">خارج از رکاد</option>
        </select>
      </div>
      <div>
        <label>پسر / دختر</label>
        <select name="gender">
          <option value="boy">پسر</option>
          <option value="girl">دختر</option>
        </select>
      </div>
    </div>
    <button class="btn btn-solid" type="submit">ساخت حساب</button>
  </form>
</div>
</div>

<div class="admin-pane"<?= $tab === 'list' ? '' : ' hidden' ?>>
<div class="admin-table-wrap">
<table class="data-table">
  <thead><tr><th>#</th><th>بازیکن</th><th>ایمیل</th><th>کیف پول</th><th>از</th><th>جنسیت</th><th>نقش</th><th>عملیات</th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): $isMe = (int)$u['id'] === (int)current_user()['id']; ?>
    <tr>
      <td><?= fa_num($u['id']) ?></td>
      <td><?= player_who_html($u, url('player/' . (int)$u['id'])) ?></td>
      <td><?= e($u['email']) ?></td>
      <td>
        <?= money((int)($u['wallet'] ?? 0)) ?>
        <form method="post" class="inline-form" style="margin-top:6px">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="number" name="delta" placeholder="+/− تومان" style="width:110px">
          <button class="btn btn-sm" name="act" value="wallet" type="submit">کیف پول</button>
        </form>
      </td>
      <td>
        <form method="post" class="inline-form">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="hidden" name="act" value="origin">
          <select name="origin" onchange="this.form.submit()">
            <option value="rokad" <?= origin_of($u)==='rokad'?'selected':'' ?>>رکاد</option>
            <option value="guest" <?= origin_of($u)==='guest'?'selected':'' ?>>خارج</option>
          </select>
        </form>
      </td>
      <td>
        <form method="post" class="inline-form">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <input type="hidden" name="act" value="gender">
          <select name="gender" onchange="this.form.submit()">
            <option value="boy" <?= gender_of($u)==='boy'?'selected':'' ?>>پسر</option>
            <option value="girl" <?= gender_of($u)==='girl'?'selected':'' ?>>دختر</option>
          </select>
        </form>
      </td>
      <td><?= e(status_fa($u['role'])) ?></td>
      <td>
        <?php if (!$isMe && ($u['role'] !== 'administrator' || is_administrator())): ?>
        <form method="post" class="inline-form">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
          <select name="role">
            <option value="user" <?= $u['role']==='user'?'selected':'' ?>>بازیکن</option>
            <option value="godgame" <?= $u['role']==='godgame'?'selected':'' ?>>گادگیم</option>
            <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>ادمین</option>
          </select>
          <button class="btn btn-sm" name="act" value="role" type="submit">ثبت</button>
          <button class="btn btn-sm btn-danger" name="act" value="delete" type="submit" data-confirm="این حساب برای همیشه حذف شود؟">حذف</button>
        </form>
        <?php else: ?>
          <span style="color:var(--muted);font-size:12px">حساب خودت</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
