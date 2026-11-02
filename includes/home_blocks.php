<?php
declare(strict_types=1);

function render_home_block(string $id, array $d): void
{
    $fn = 'home_block_' . $id;
    if (function_exists($fn)) {
        $fn($d);
    }
}

function home_block_hero(array $d): void
{
    ?>
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-content">
      <img class="hero-emblem" src="<?= e(asset('img/brand/mark-teal.png')) ?>" alt="رکاد">
      <div class="trust-ribbon">اولین هنرستان استارتاپی ایران</div>
      <?php sticker_heading('آینده از میز شروع می‌شه!', 'h1', '', ['میز']); ?>
      <p class="lead">رکاد — هنرستان استارتاپی. گیم‌کاد میز بازی اینجاست: مافیا، گل یا پوچ، شطرنج، بردگیم و بازی‌های الکترونیک. صندلی با پیش‌پرداخت نصف؛ اگر نیایی همان نصف می‌ماند.</p>
      <div class="hero-actions">
        <a class="btn btn-solid" href="<?= e(url('events')) ?>">رویدادها</a>
        <a class="btn" href="<?= e(url('egames')) ?>">الکترونیک</a>
      </div>
    </div>
    <img class="hero-photo" src="<?= e(asset('img/hero-stickers.png')) ?>" alt="">
  </div>
</section>
    <?php
}

function home_block_stats(array $d): void
{
    return;
    // kept so old layout keys do not fatal
}

function home_block_stats_unused(array $d): void
{
    $nSessions = (int)($d['nSessions'] ?? 0);
    $nPlayers = (int)($d['nPlayers'] ?? 0);
    $nRoles = (int)($d['nRoles'] ?? 0);
    ?>
<section class="section" style="padding-top:1.5rem">
  <div class="wrap">
    <div class="stat-row">
      <article class="stat-card">
        <span class="eyebrow">شب بازی</span>
        <div class="num"><?= fa_num('+' . max(1, $nSessions)) ?></div>
        <div class="cap">نشست پیش‌رو<br>روی تقویم رکاد</div>
      </article>
      <article class="stat-card">
        <span class="eyebrow">بازیکن</span>
        <div class="num"><?= fa_num($nPlayers) ?></div>
        <div class="cap">پروفایل ساخته‌شده<br>روی میزهای گیم‌کاد</div>
      </article>
      <article class="stat-card">
        <span class="eyebrow">نقش</span>
        <div class="num"><?= fa_num($nRoles) ?></div>
        <div class="cap">نقش سناریوهای رکاد<br>کاپو تا اکوسیستم</div>
      </article>
      <article class="stat-card">
        <span class="eyebrow">ساعت میز</span>
        <div class="num" style="font-size:2.4rem"><?= e(setting('cafe_hours') ?: '۱۶–۲۲') ?></div>
        <div class="cap">هر روز عصر<br>هنرستان رکاد</div>
      </article>
    </div>
  </div>
</section>
    <?php
}

function home_block_mvp(array $d): void
{
    return;
}

function home_block_mvp_unused(array $d): void
{
    $mvps = $d['mvps'] ?? [];
    if (!$mvps) {
        return;
    }
    $byPos = [];
    foreach ($mvps as $m) {
        $byPos[(int)$m['position']] = $m;
    }
    ?>
<section class="section mvp-section">
  <div class="wrap">
    <div class="kicker">THIS WEEK</div>
    <?php sticker_heading('تالار قهرمانان', 'h2', 'as-h2'); ?>
    <p class="sub">تا سه نفر — از هر بازی که باشد در یک سکو. صندلی خالی نشان داده نمی‌شود.</p>
    <div class="mvp-stage">
      <div class="mvp-glow"></div>
      <?php foreach ([2, 1, 3] as $pos):
        $m = $byPos[$pos] ?? null;
        if (!$m) continue;
      ?>
      <article class="mvp-card pos-<?= (int)$pos ?>">
        <?php if ($pos === 1): ?><div class="crown">♛</div><?php endif; ?>
        <a class="frame" href="<?= e(url('player/' . (int)$m['user_id'])) ?>">
          <span class="mvp-badge"><?= fa_num($pos) ?></span>
          <img src="<?= e(media($m['avatar'])) ?>" alt="">
          <div class="meta">
            <h3><?= e($m['display_name']) ?></h3>
            <p><?= e($m['title']) ?><?= $m['cat_name'] ? ' · ' . e($m['cat_name']) : '' ?></p>
          </div>
        </a>
      </article>
      <?php endforeach; ?>
    </div>
    <p class="sub" style="margin-top:8px"><?= e(setting('mvp_label')) ?></p>
  </div>
</section>
    <?php
}

function home_block_sessions(array $d): void
{
    $sessions = $d['sessions'] ?? [];
    ?>
<section class="section">
  <div class="wrap">
    <div class="kicker">EVENTS</div>
    <?php sticker_heading('رویدادهای پیش‌رو', 'h2', 'as-h2'); ?>
    <p class="sub">هر بازی روز و ساعت مشخص دارد. بازی را فیلتر کن و جا را با پیش‌پرداخت نگه دار.</p>
    <div class="grid-3">
      <?php foreach ($sessions as $s):
        $left = max(0, (int)$s['total_seats'] - (int)$s['reserved']);
        $pct = (int)$s['total_seats'] ? (int)round(((int)$s['reserved'] / (int)$s['total_seats']) * 100) : 0;
      ?>
      <article class="card session-card">
        <img class="cover" src="<?= e(media($s['cover'], 'img/hero-cafe.jpg')) ?>" alt="">
        <div class="body">
            <div class="when"><?= e($s['cat_name']) ?> · <?= e(audience_fa(audience_of($s))) ?> · <?= jdate_format($s['session_date'] . ' ' . $s['start_time']) ?></div>
            <h3><?= e($s['title']) ?></h3>
            <p style="color:var(--muted);font-size:13px;line-height:1.8;margin-top:6px"><?= e(excerpt($s['description'], 90)) ?></p>
            <div class="seats"><span>جا باقی: <?= fa_num($left) ?></span><span><?= money((int)$s['price']) ?></span></div>
            <div class="seat-bar"><span style="width:<?= $pct ?>%"></span></div>
            <div style="margin-top:14px"><a class="btn btn-sm" href="<?= e(url('events/' . (int)$s['id'])) ?>">رزرو جا</a></div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php if (!$sessions): ?><p class="empty">نشست فعالی نیست. از پنل ادمین روز بازی را بساز.</p><?php endif; ?>
  </div>
</section>
    <?php
}

function home_block_games(array $d): void
{
    $cats = $d['tableCats'] ?? [];
    ?>
<section class="section" style="padding-top:0">
  <div class="wrap">
    <div class="kicker">GAMES</div>
    <?php sticker_heading('میزهای بازی', 'h2', 'as-h2'); ?>
    <p class="sub">هر بازی لیدربورد و آموزش جدا دارد.</p>
    <div class="grid-3">
      <?php foreach ($cats as $c): ?>
      <a class="card cat-card" href="<?= e(url('leaderboard/' . $c['slug'])) ?>">
        <img src="<?= e(media($c['image'])) ?>" alt="">
        <div class="body">
          <h3><?= e($c['name']) ?></h3>
          <p><?= e($c['description']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
    <?php
}

function home_block_egames(array $d): void
{
    $cats = $d['eCats'] ?? [];
    $sessions = $d['eSessions'] ?? [];
    ?>
<section class="section egames-section">
  <div class="wrap">
    <div class="kicker">CONSOLE</div>
    <?php sticker_heading('بازی‌های الکترونیک', 'h2', 'as-h2', ['الکترونیک']); ?>
    <p class="sub">فیفا، پلی‌استیشن و هر عنوان کنسولی که اضافه کنیم. مسابقه را رزرو کن.</p>
    <div class="grid-3">
      <?php foreach ($cats as $c): ?>
      <a class="card cat-card egame-card" href="<?= e(url('egames/' . $c['slug'])) ?>">
        <img src="<?= e(media($c['image'], 'img/egame-arcade.png')) ?>" alt="">
        <div class="body">
          <div class="type">الکترونیک</div>
          <h3><?= e($c['name']) ?></h3>
          <p><?= e($c['description']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php if ($sessions): ?>
      <h3 class="egame-next">مسابقه‌های پیش‌رو</h3>
      <div class="grid-3">
        <?php foreach ($sessions as $s):
          $left = max(0, (int)$s['total_seats'] - (int)$s['reserved']);
        ?>
        <article class="card session-card">
          <div class="body">
            <div class="when"><?= e($s['cat_name']) ?> · <?= jdate_format($s['session_date'] . ' ' . $s['start_time']) ?></div>
            <h3><?= e($s['title']) ?></h3>
            <div class="seats"><span>جا: <?= fa_num($left) ?></span><span><?= money((int)$s['price']) ?></span></div>
            <div style="margin-top:12px"><a class="btn btn-sm btn-solid" href="<?= e(url('events/' . (int)$s['id'])) ?>">ثبت‌نام مسابقه</a></div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <p style="text-align:center;margin-top:22px"><a class="btn" href="<?= e(url('egames')) ?>">همه بازی‌های الکترونیک</a></p>
  </div>
</section>
    <?php
}

function home_block_guide(array $d): void
{
    $guideScens = $d['guideScens'] ?? [];
    ?>
<section class="section">
  <div class="wrap">
    <div class="kicker">ACADEMY</div>
    <?php sticker_heading('آموزش نقش و سناریو', 'h2', 'as-h2', ['نقش']); ?>
    <p class="sub">اول سناریو را باز کن؛ داخلش نحوه بازی و کارت نقش‌هاست. کارت را بزن تا بچرخد.</p>
    <div class="grid-3">
      <?php foreach ($guideScens as $s): ?>
      <a class="card cat-card" href="<?= e(url('guide/' . $s['cat_slug'] . '/' . $s['slug'])) ?>">
        <img src="<?= e(media($s['cover'] ?: $s['cat_image'], 'img/cat-mafia.jpg')) ?>" alt="">
        <div class="body">
          <div class="type" style="font-size:11px;color:var(--gold)"><?= e($s['cat_name']) ?></div>
          <h3><?= e($s['name']) ?></h3>
          <p><?= e($s['description']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center;margin-top:22px"><a class="btn" href="<?= e(url('guide')) ?>">ورود به آموزش</a></p>
  </div>
</section>
    <?php
}

function home_block_leaderboard(array $d): void
{
    $top = $d['top'] ?? [];
    ?>
<section class="section" style="padding-top:0">
  <div class="wrap">
    <div class="kicker">BOARD</div>
    <?php sticker_heading('لیدربورد مافیا', 'h2', 'as-h2', ['مافیا']); ?>
    <p class="sub">امتیازها از پنل ادمین برای هر دسته جداگانه تنظیم می‌شود.</p>
    <div class="card table-card">
      <div class="table-scroll">
      <table class="lb-table">
        <thead><tr><th>رتبه</th><th>بازیکن</th><th>امتیاز</th><th class="hide-sm">برد</th><th class="hide-sm">باخت</th><th class="hide-sm">نرخ برد</th></tr></thead>
        <tbody>
        <?php foreach ($top as $i => $r): $wr = winrate((int)$r['wins'], (int)$r['losses']); ?>
          <tr>
            <td><span class="rank <?= $i<3?'r'.($i+1):'' ?>"><?= fa_num($i+1) ?></span></td>
            <td><?= player_who_html($r, url('player/' . (int)$r['user_id'])) ?></td>
            <td><?= fa_num($r['points']) ?></td>
            <td class="hide-sm"><?= fa_num($r['wins']) ?></td>
            <td class="hide-sm"><?= fa_num($r['losses']) ?></td>
            <td class="hide-sm"><?= fa_num($wr) ?>٪</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
    <p style="text-align:center;margin-top:18px"><a class="btn" href="<?= e(url('leaderboard')) ?>">همه دسته‌ها</a></p>
  </div>
</section>
    <?php
}

function home_block_blog(array $d): void
{
    $posts = $d['posts'] ?? [];
    ?>
<section class="section" style="padding-top:0">
  <div class="wrap">
    <div class="kicker">JOURNAL</div>
    <?php sticker_heading('آموزش میز', 'h2', 'as-h2'); ?>
    <p class="sub">سناریوها و نکته‌های بازی.</p>
    <div class="grid-3">
      <?php foreach ($posts as $p): ?>
      <a class="card blog-card" href="<?= e(url('blog/' . $p['slug'])) ?>">
        <img src="<?= e(media($p['cover'], 'img/hero-cafe.jpg')) ?>" alt="">
        <div class="body">
          <div class="type"><?= e(status_fa($p['type'])) ?><?= $p['cat_name'] ? ' · '.e($p['cat_name']) : '' ?></div>
          <h3><?= e($p['title']) ?></h3>
          <p><?= e($p['excerpt']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
    <?php
}

function home_block_voices(array $d): void
{
    return;
}

function home_block_voices_unused(array $d): void
{
    $quotes = $d['quotes'] ?? [];
    ?>
<section class="section voices-section" style="padding-top:0">
  <div class="wrap">
    <div class="kicker">VOICES</div>
    <h2>نظر بازیکن‌ها</h2>
    <p class="sub">فقط نظرهایی که میزبان انتخاب کرده این‌جا می‌آید.</p>
    <?php if ($quotes): ?>
      <?php render_quote_cards($quotes); ?>
    <?php else: ?>
      <p class="empty">هنوز نظری روی خانه نیست.</p>
    <?php endif; ?>
    <p style="text-align:center;margin-top:22px"><a class="btn btn-solid" href="<?= e(url('comments')) ?>">نظر بده</a></p>
  </div>
</section>
    <?php
}

function home_block_about(array $d): void
{
    ?>
<section class="section">
  <div class="wrap about-split">
    <img src="<?= e(asset('img/hero-cafe.jpg')) ?>" alt="فضای گیم‌کاد">
    <div class="about-copy">
      <div class="kicker" style="text-align:right;direction:ltr">ROKAD</div>
      <?php sticker_heading('هنرستان استارتاپی رکاد', 'h3', '', ['رکاد']); ?>
      <p><?= e(setting('about')) ?></p>
      <div class="facts">
        <div class="fact"><span>ساعت</span><b><?= e(setting('cafe_hours')) ?></b></div>
        <div class="fact"><span>پیش‌پرداخت</span><b><?= fa_num(50) ?>٪</b></div>
        <div class="fact"><span>تلفن</span><b><?= e(setting('cafe_phone')) ?></b></div>
      </div>
    </div>
  </div>
</section>
    <?php
}
