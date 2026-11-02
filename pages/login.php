<?php
if (current_user()) redirect('/account');
if (is_post()) {
    csrf_check();
    if (auth_login(trim((string)post('user')), (string)post('password'))) {
        $go = $_SESSION['after_login'] ?? '/account';
        unset($_SESSION['after_login']);
        flash('x','خوش آمدی به رکاد.', 'ok');
        redirect($go);
    }
    flash('x','نام کاربری یا رمز اشتباه است.','err');
    redirect('/login');
}
$title = 'ورود';
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">ENTER</div>
    <h1>ورود به رکاد</h1>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap">
    <div class="card auth-box">
      <form class="form" method="post">
        <?= csrf_field() ?>
        <label>نام کاربری یا ایمیل</label>
        <input name="user" required autocomplete="username" placeholder="مثلاً arian" dir="ltr" class="ltr-input">
        <label>رمز عبور</label>
        <div class="pass-wrap">
          <input name="password" type="password" required autocomplete="current-password" placeholder="رمز عبور خود را وارد کنید">
          <?= pass_eye_btn() ?>
        </div>
        <button class="btn btn-solid" type="submit">ورود</button>
      </form>
      <p style="margin-top:14px;color:var(--muted);font-size:13px">حساب نداری؟ <a href="<?= e(url('register')) ?>">پروفایل بساز</a></p>
    </div>
  </div>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
