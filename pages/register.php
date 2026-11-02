<?php
if (current_user()) redirect('/account');
if (is_post()) {
    csrf_check();
    $errs = auth_register($_POST + ['_login' => 1]);
    if ($errs) {
        flash('x', implode(' ', $errs), 'err');
        redirect('/register');
    }
    flash('x','پروفایل ساخته شد. خوش آمدی.', 'ok');
    redirect('/account');
}
$title = 'ساخت پروفایل';
require ROOT . '/includes/header.php';
?>
<section class="page-hero">
  <div class="wrap">
    <div class="kicker">PROFILE</div>
    <h1>ساخت پروفایل بازیکن</h1>
    <p class="sub">همین الان ثبت‌نام کن. نام کاربری انگلیسی است؛ بقیه را فارسی بنویس.</p>
  </div>
</section>
<section class="section" style="padding-top:0">
  <div class="wrap">
    <div class="card auth-box">
      <form class="form" method="post">
        <?= csrf_field() ?>
        <label>نام نمایشی</label>
        <input name="display_name" required placeholder="مثلاً آرین کاظمی" autocomplete="name">
        <label>نام کاربری</label>
        <input name="username" required placeholder="مثلاً arian" autocomplete="username" dir="ltr" class="ltr-input">
        <label>ایمیل</label>
        <input name="email" type="email" required placeholder="مثلاً arian@rokad.ir" autocomplete="email" dir="ltr" class="ltr-input">
        <label>موبایل</label>
        <input name="phone" placeholder="مثلاً 09332881180" autocomplete="tel" dir="ltr" class="ltr-input">
        <p class="field-lab">پسر هستی یا دختر؟</p>
        <div class="gender-pick" role="radiogroup" aria-label="پسر یا دختر">
          <label class="gender-card">
            <input type="radio" name="gender" value="boy" required>
            <b>پسر</b>
          </label>
          <label class="gender-card">
            <input type="radio" name="gender" value="girl" required>
            <b>دختر</b>
          </label>
        </div>
        <label for="regPass">رمز عبور</label>
        <div class="pass-wrap">
          <input id="regPass" name="password" type="password" required minlength="6" placeholder="رمز عبور خود را وارد کنید" autocomplete="new-password" data-pass-meter>
          <?= pass_eye_btn() ?>
        </div>
        <div class="pass-meter" data-pass-bar hidden>
          <div class="pass-meter-track"><i></i></div>
          <small data-pass-lab>قدرت رمز</small>
        </div>
        <label for="regPass2">تکرار رمز</label>
        <div class="pass-wrap">
          <input id="regPass2" name="password_confirm" type="password" required minlength="6" placeholder="تکرار رمز" autocomplete="new-password" data-pass-match="#regPass">
          <?= pass_eye_btn() ?>
        </div>
        <p class="pass-mismatch" data-pass-mismatch hidden>دو رمز یکی نیستند.</p>
        <button class="btn btn-solid" type="submit">ساخت پروفایل</button>
      </form>
    </div>
  </div>
</section>
<?php require ROOT . '/includes/footer.php'; ?>
