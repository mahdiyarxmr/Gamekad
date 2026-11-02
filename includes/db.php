<?php
declare(strict_types=1);

require_once __DIR__ . '/rokad_book.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    if (!is_dir(dirname(DB_PATH))) mkdir(dirname(DB_PATH), 0775, true);
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    return $pdo;
}

function db_init(): void
{
    $pdo = db();
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT UNIQUE NOT NULL,
  email TEXT UNIQUE NOT NULL,
  password TEXT NOT NULL,
  display_name TEXT NOT NULL,
  phone TEXT DEFAULT '',
  avatar TEXT DEFAULT '',
  bio TEXT DEFAULT '',
  role TEXT DEFAULT 'user',
  created_at TEXT
);
CREATE TABLE IF NOT EXISTS categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  slug TEXT UNIQUE NOT NULL,
  description TEXT DEFAULT '',
  image TEXT DEFAULT '',
  color TEXT DEFAULT '#c9a227',
  sort_order INTEGER DEFAULT 0,
  has_roles INTEGER DEFAULT 0
);
CREATE TABLE IF NOT EXISTS roles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  side TEXT DEFAULT 'citizen',
  color TEXT DEFAULT '#c9a227',
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS scenarios (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  description TEXT DEFAULT '',
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS leaderboard (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  points INTEGER DEFAULT 0,
  wins INTEGER DEFAULT 0,
  losses INTEGER DEFAULT 0,
  UNIQUE(category_id, user_id),
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS game_sessions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  description TEXT DEFAULT '',
  session_date TEXT NOT NULL,
  start_time TEXT NOT NULL,
  end_time TEXT DEFAULT '',
  location TEXT DEFAULT 'کافه گیم‌کاد',
  total_seats INTEGER DEFAULT 12,
  price INTEGER DEFAULT 100000,
  status TEXT DEFAULT 'upcoming',
  cover TEXT DEFAULT '',
  created_at TEXT,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);
CREATE TABLE IF NOT EXISTS reservations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  session_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  amount_paid INTEGER DEFAULT 0,
  payment_status TEXT DEFAULT 'pending',
  attendance TEXT DEFAULT 'pending',
  created_at TEXT,
  paid_at TEXT,
  UNIQUE(session_id, user_id),
  FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  reservation_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  amount INTEGER NOT NULL,
  type TEXT DEFAULT 'deposit',
  status TEXT DEFAULT 'pending',
  authority TEXT DEFAULT '',
  created_at TEXT,
  FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS blog_posts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  slug TEXT UNIQUE NOT NULL,
  excerpt TEXT DEFAULT '',
  content TEXT DEFAULT '',
  cover TEXT DEFAULT '',
  category_id INTEGER,
  type TEXT DEFAULT 'tutorial',
  video_url TEXT DEFAULT '',
  author_id INTEGER,
  published INTEGER DEFAULT 1,
  created_at TEXT
);
CREATE TABLE IF NOT EXISTS mvp (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  position INTEGER NOT NULL,
  category_id INTEGER,
  title TEXT DEFAULT 'MVP',
  week_label TEXT DEFAULT '',
  active INTEGER DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS player_stats (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  category_id INTEGER NOT NULL,
  scenario_id INTEGER,
  role_id INTEGER,
  result TEXT NOT NULL,
  session_id INTEGER,
  notes TEXT DEFAULT '',
  created_at TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS settings (
  key TEXT PRIMARY KEY,
  value TEXT
);
SQL);

    $n = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($n === 0) {
        seed_all();
    }
    ensure_role_meta();
    ensure_app_meta();
    seed_rokad_book();
}

function ensure_app_meta(): void
{
    $uCols = array_column(q('PRAGMA table_info(users)')->fetchAll(), 'name');
    if (!in_array('wallet', $uCols, true)) {
        db()->exec('ALTER TABLE users ADD COLUMN wallet INTEGER DEFAULT 0');
    }
    if (!in_array('origin', $uCols, true)) {
        db()->exec('ALTER TABLE users ADD COLUMN origin TEXT DEFAULT \'rokad\'');
        q('UPDATE users SET origin=\'rokad\' WHERE origin IS NULL OR origin=\'\'');
        q('UPDATE users SET origin=\'guest\' WHERE username IN (\'elnaz\',\'mahdi\',\'yasamin\',\'amir\',\'donya\')');
    }
    $uCols = array_column(q('PRAGMA table_info(users)')->fetchAll(), 'name');
    if (!in_array('gender', $uCols, true)) {
        db()->exec('ALTER TABLE users ADD COLUMN gender TEXT DEFAULT \'\'');
        q("UPDATE users SET gender='girl' WHERE username IN ('sara','nika','elnaz','yasamin','donya')");
        q("UPDATE users SET gender='boy' WHERE gender IS NULL OR gender=''");
    }
    q("UPDATE users SET role='user' WHERE role='blogger'");

    $sCols = array_column(q('PRAGMA table_info(game_sessions)')->fetchAll(), 'name');
    if (!in_array('audience', $sCols, true)) {
        db()->exec("ALTER TABLE game_sessions ADD COLUMN audience TEXT DEFAULT 'boys'");
        q("UPDATE game_sessions SET audience='boys' WHERE audience IS NULL OR audience=''");
    }
    $sCols = array_column(q('PRAGMA table_info(game_sessions)')->fetchAll(), 'name');
    if (!in_array('pay_type', $sCols, true)) {
        db()->exec("ALTER TABLE game_sessions ADD COLUMN pay_type TEXT DEFAULT 'full'");
        q("UPDATE game_sessions SET pay_type='full' WHERE pay_type IS NULL OR pay_type=''");
    }
    if (!in_array('online_amount', $sCols, true)) {
        db()->exec('ALTER TABLE game_sessions ADD COLUMN online_amount INTEGER DEFAULT 0');
    }
    if (!in_array('discount_price', $sCols, true)) {
        db()->exec('ALTER TABLE game_sessions ADD COLUMN discount_price INTEGER DEFAULT 0');
    }
    $sCols = array_column(q('PRAGMA table_info(game_sessions)')->fetchAll(), 'name');
    if (!in_array('venue_note', $sCols, true)) {
        db()->exec("ALTER TABLE game_sessions ADD COLUMN venue_note TEXT DEFAULT ''");
    }
    seed_badges();
    seed_matches();
    seed_arian_roles();

    $catCols = array_column(q('PRAGMA table_info(categories)')->fetchAll(), 'name');
    if (!in_array('kind', $catCols, true)) {
        db()->exec('ALTER TABLE categories ADD COLUMN kind TEXT DEFAULT \'table\'');
        q('UPDATE categories SET kind=\'table\' WHERE kind IS NULL OR kind=\'\'');
    }
    seed_egames();

    db()->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS wallet_tx (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  amount INTEGER NOT NULL,
  type TEXT DEFAULT 'charge',
  status TEXT DEFAULT 'success',
  note TEXT DEFAULT '',
  created_at TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS blog_editors (
  post_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  PRIMARY KEY (post_id, user_id),
  FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL);

    $addr = setting('cafe_address');
    if ($addr === '' || str_contains($addr, 'تهران') || str_contains($addr, 'گیم‌کاد، پلاک')) {
        setting('cafe_address', 'مشهد، بلوار شهید فرامرز عباسی، رسالت ۳۲، رسالت، بین رسالت ۴۵ و ۴۷، پلاک ۲۳ (محلهٔ شهید فرامرز عباسی)');
        setting('cafe_phone', '09332881180');
        setting('cafe_hours', '۱۶:۰۰ تا ۲۲:۰۰');
        setting('about', 'رکاد هنرستان پسرانه استارتاپی است. میزهای بازی گیم‌کاد اینجاست: مافیا، گل یا پوچ، شطرنج و بردگیم. صندلی با پیش‌پرداخت نصف رزرو می‌شود؛ اگر نیایی همان نصف می‌ماند.');
    }

    db()->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS god_nights (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  god_id INTEGER NOT NULL,
  title TEXT DEFAULT '',
  category_id INTEGER,
  player_count INTEGER NOT NULL,
  created_at TEXT,
  FOREIGN KEY (god_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS god_night_roles (
  night_id INTEGER NOT NULL,
  role_id INTEGER NOT NULL,
  qty INTEGER NOT NULL DEFAULT 1,
  PRIMARY KEY (night_id, role_id),
  FOREIGN KEY (night_id) REFERENCES god_nights(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS god_night_seats (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  night_id INTEGER NOT NULL,
  seat_no INTEGER NOT NULL,
  player_name TEXT NOT NULL DEFAULT '',
  role_id INTEGER,
  FOREIGN KEY (night_id) REFERENCES god_nights(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS comments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER,
  name TEXT NOT NULL,
  body TEXT NOT NULL,
  avatar TEXT DEFAULT '',
  featured INTEGER DEFAULT 0,
  sort_order INTEGER DEFAULT 0,
  created_at TEXT,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
SQL);

    $cCols = array_column(q('PRAGMA table_info(comments)')->fetchAll(), 'name');
    if ($cCols && !in_array('avatar', $cCols, true)) {
        db()->exec('ALTER TABLE comments ADD COLUMN avatar TEXT DEFAULT ""');
    }

    seed_voice_comments();

    if (setting('pay_provider') === '') {
        setting('pay_provider', 'simulated');
        setting('pay_mode', 'sandbox');
        setting('pay_enabled', '1');
        setting('pay_merchant', '');
        setting('pay_extra', '');
    }

    $seedAdmin = q('SELECT id, role FROM users WHERE username=?', ['admin'])->fetch();
    if ($seedAdmin && $seedAdmin['role'] === 'admin') {
        q('UPDATE users SET role=? WHERE id=?', ['administrator', (int)$seedAdmin['id']]);
    }
}

function ensure_role_meta(): void
{
    $cols = array_column(q('PRAGMA table_info(roles)')->fetchAll(), 'name');
    if (!in_array('description', $cols, true)) {
        db()->exec('ALTER TABLE roles ADD COLUMN description TEXT DEFAULT ""');
    }
    if (!in_array('symbol', $cols, true)) {
        db()->exec('ALTER TABLE roles ADD COLUMN symbol TEXT DEFAULT ""');
    }
    if (!in_array('scenario_id', $cols, true)) {
        db()->exec('ALTER TABLE roles ADD COLUMN scenario_id INTEGER DEFAULT 0');
    }
    if (!in_array('night_wake', $cols, true)) {
        db()->exec('ALTER TABLE roles ADD COLUMN night_wake INTEGER DEFAULT 0');
    }

    $scCols = array_column(q('PRAGMA table_info(scenarios)')->fetchAll(), 'name');
    if (!in_array('slug', $scCols, true)) {
        db()->exec('ALTER TABLE scenarios ADD COLUMN slug TEXT DEFAULT ""');
    }
    if (!in_array('howto', $scCols, true)) {
        db()->exec('ALTER TABLE scenarios ADD COLUMN howto TEXT DEFAULT ""');
    }
    if (!in_array('cover', $scCols, true)) {
        db()->exec('ALTER TABLE scenarios ADD COLUMN cover TEXT DEFAULT ""');
    }

    q('UPDATE categories SET has_roles=0 WHERE slug=?', ['werewolf']);
    q('UPDATE categories SET has_roles=1 WHERE slug=?', ['mafia']);
}

function seed_rokad_book(): void
{
    if (!function_exists('rokad_scenarios')) {
        require_once __DIR__ . '/rokad_book.php';
    }
    if (setting('rokad_book_version') === '4') {
        return;
    }

    $mafia = q('SELECT id FROM categories WHERE slug=?', ['mafia'])->fetch();
    if (!$mafia) {
        return;
    }
    $cid = (int)$mafia['id'];

    db()->exec('PRAGMA foreign_keys = OFF');
    db()->exec('DELETE FROM god_night_seats');
    db()->exec('DELETE FROM god_night_roles');
    db()->exec('DELETE FROM god_nights');
    db()->exec('DELETE FROM roles');
    db()->exec('DELETE FROM scenarios');
    db()->exec('PRAGMA foreign_keys = ON');

    $insS = db()->prepare('INSERT INTO scenarios (category_id,name,description,slug,howto,cover) VALUES (?,?,?,?,?,?)');
    $insR = db()->prepare('INSERT INTO roles (category_id,scenario_id,name,side,color,description,symbol) VALUES (?,?,?,?,?,?,?)');
    foreach (rokad_scenarios() as $sc) {
        $insS->execute([$cid, $sc['name'], $sc['description'], $sc['slug'], $sc['howto'], $sc['cover']]);
        $sid = (int)db()->lastInsertId();
        foreach ($sc['roles'] as $r) {
            $insR->execute([$cid, $sid, $r['name'], $r['side'], $r['color'], $r['desc'], $r['symbol']]);
        }
    }
    setting('rokad_book_version', '4');
}

function seed_badges(): void
{
    db()->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS badges (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  slug TEXT UNIQUE NOT NULL,
  name TEXT NOT NULL,
  hint TEXT DEFAULT '',
  color TEXT DEFAULT '#58bdaf',
  icon TEXT DEFAULT '★'
);
CREATE TABLE IF NOT EXISTS user_badges (
  user_id INTEGER NOT NULL,
  badge_id INTEGER NOT NULL,
  granted_by INTEGER,
  created_at TEXT,
  last_at TEXT,
  level INTEGER DEFAULT 1,
  wins INTEGER DEFAULT 1,
  PRIMARY KEY (user_id, badge_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
);
SQL);
    $bCols = array_column(q('PRAGMA table_info(badges)')->fetchAll(), 'name');
    if (!in_array('image', $bCols, true)) {
        db()->exec("ALTER TABLE badges ADD COLUMN image TEXT DEFAULT ''");
    }
    $ubCols = array_column(q('PRAGMA table_info(user_badges)')->fetchAll(), 'name');
    if (!in_array('level', $ubCols, true)) {
        db()->exec('ALTER TABLE user_badges ADD COLUMN level INTEGER DEFAULT 1');
    }
    if (!in_array('wins', $ubCols, true)) {
        db()->exec('ALTER TABLE user_badges ADD COLUMN wins INTEGER DEFAULT 1');
    }
    if (!in_array('last_at', $ubCols, true)) {
        db()->exec('ALTER TABLE user_badges ADD COLUMN last_at TEXT');
        q('UPDATE user_badges SET last_at=created_at WHERE last_at IS NULL OR last_at=""');
    }
    q('UPDATE user_badges SET wins=1 WHERE wins IS NULL OR wins<1');
    $rows = [
        ['mvp', 'ستاره میز', 'بهترین بازیکن آن شب', '#f4971f', '★'],
        ['clutch', 'کلاچ', 'برگشت در لحظهٔ آخر', '#e0195b', '⚡'],
        ['host', 'میزبان شب', 'گرداندگی تمیز', '#58bdaf', '♠'],
        ['fair', 'بازی‌تمیز', 'احترام به میز', '#21295a', '✦'],
        ['wolf', 'گرگ شب', 'کشتار تمیز و بی‌سروصدا', '#4f215a', '🐺'],
        ['detective', 'بازپرس', 'کشف درست نقش', '#347e75', '🔍'],
        ['speech', 'سخنور', 'نطق و دفاع قوی', '#ce1754', '🎤'],
        ['survivor', 'بازمانده', 'تا صبح سر میز ماند', '#59bbaf', '🛡'],
        ['shield', 'محافظ', 'جان کسی را خرید', '#21295a', '🕊'],
        ['jester', 'لوده', 'برد نقش مستقل', '#f4971f', '🎭'],
        ['veteran', 'کهنه‌کار', 'شب‌های زیاد روی میز', '#8a5a10', '🏛'],
        ['spark', 'تازه‌نفس', 'شروع درخشان', '#58bdaf', '✨'],
        ['duo', 'هماهنگی', 'بازی تیمی تمیز', '#e0195b', '🤝'],
        ['night', 'بیدارشب', 'نقش شب را خوب زد', '#202a5a', '🌙'],
    ];
    foreach ($rows as $r) {
        if (!q('SELECT id FROM badges WHERE slug=?', [$r[0]])->fetch()) {
            q('INSERT INTO badges (slug,name,hint,color,icon) VALUES (?,?,?,?,?)', $r);
        }
    }
    if (setting('badge_curve_v2') !== '1') {
        foreach (q('SELECT user_id, badge_id, wins FROM user_badges')->fetchAll() as $ub) {
            $lv = badge_level_from_wins((int)($ub['wins'] ?? 0));
            q('UPDATE user_badges SET level=? WHERE user_id=? AND badge_id=?',
                [$lv, (int)$ub['user_id'], (int)$ub['badge_id']]);
        }
        setting('badge_curve_v2', '1');
    }
    if (setting('badge_mvp_migrate') !== '1') {
        $bid = q("SELECT id FROM badges WHERE slug='mvp'")->fetch();
        if ($bid) {
            try {
                foreach (q('SELECT user_id FROM mvp WHERE active=1')->fetchAll() as $m) {
                    q('INSERT OR IGNORE INTO user_badges (user_id,badge_id,granted_by,created_at) VALUES (?,?,NULL,datetime("now"))',
                        [(int)$m['user_id'], (int)$bid['id']]);
                }
            } catch (Throwable $e) {
            }
        }
        setting('badge_mvp_migrate', '1');
    }
}

function seed_matches(): void
{
    db()->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS matches (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  category_id INTEGER NOT NULL,
  scenario_id INTEGER,
  session_id INTEGER,
  winner TEXT NOT NULL DEFAULT '',
  title TEXT DEFAULT '',
  created_at TEXT,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS match_players (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  match_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  role_id INTEGER,
  result TEXT NOT NULL,
  points INTEGER DEFAULT 0,
  FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
SQL);
    $ps = array_column(q('PRAGMA table_info(player_stats)')->fetchAll(), 'name');
    if (!in_array('match_id', $ps, true)) {
        db()->exec('ALTER TABLE player_stats ADD COLUMN match_id INTEGER');
    }
    $mCols = array_column(q('PRAGMA table_info(matches)')->fetchAll(), 'name');
    if (!in_array('scored', $mCols, true)) {
        db()->exec('ALTER TABLE matches ADD COLUMN scored INTEGER DEFAULT 1');
    }
    if (!in_array('god_id', $mCols, true)) {
        db()->exec('ALTER TABLE matches ADD COLUMN god_id TEXT DEFAULT \'\'');
    }
}

function seed_arian_roles(): void
{
    if (setting('arian_role_demo_v1') === '1') {
        return;
    }
    $u = q("SELECT id FROM users WHERE username='arian'")->fetch();
    if (!$u) {
        return;
    }
    $uid = (int)$u['id'];
    $rows = [
        [39, 9, 8, 3],
        [40, 9, 5, 4],
        [41, 9, 4, 2],
        [37, 9, 3, 2],
        [45, 9, 6, 5],
        [35, 9, 2, 3],
        [36, 9, 1, 2],
        [19, 7, 4, 2],
        [20, 7, 3, 3],
        [16, 7, 2, 2],
    ];
    foreach ($rows as [$rid, $scen, $w, $l]) {
        $role = q('SELECT id FROM roles WHERE id=?', [$rid])->fetch();
        if (!$role) {
            continue;
        }
        for ($i = 0; $i < $w; $i++) {
            q('INSERT INTO player_stats (user_id,category_id,scenario_id,role_id,result,notes,created_at)
               VALUES (?,?,?,?,?,?,datetime("now"))',
                [$uid, 1, $scen, $rid, 'win', 'نمونه میز']);
        }
        for ($i = 0; $i < $l; $i++) {
            q('INSERT INTO player_stats (user_id,category_id,scenario_id,role_id,result,notes,created_at)
               VALUES (?,?,?,?,?,?,datetime("now"))',
                [$uid, 1, $scen, $rid, 'loss', 'نمونه میز']);
        }
    }
    q('INSERT INTO matches (category_id,scenario_id,winner,title,created_at) VALUES (1,9,?,?,datetime("now"))',
        ['citizen', 'نمونه بازپرس · آرین کاظمی']);
    $mid = (int)db()->lastInsertId();
    if ($mid) {
        q('INSERT INTO match_players (match_id,user_id,role_id,result,points) VALUES (?,?,?,?,?)',
            [$mid, $uid, 39, 'win', 12]);
        if (function_exists('lb_bump')) {
            lb_bump(1, $uid, 12, 'win');
        }
    }
    setting('arian_role_demo_v1', '1');
}

function seed_egames(): void
{
    if (setting('egames_seed') === '1') {
        return;
    }
    $rows = [
        ['فیفا', 'fifa', 'مسابقات فیفا روی کنسول. لیگ دوستانه و کاپ یک‌حذفی.', '/assets/img/egame-fifa.png', '#58bdaf', 21],
        ['پلی‌استیشن', 'playstation', 'نشست‌های PS: کوپ، فایت و مهمانی کنسول.', '/assets/img/egame-ps.png', '#21295a', 22],
        ['آرکید و کنسول', 'arcade', 'بازی‌های ویدئویی آزاد؛ هر عنوان جدیدی که اضافه کنیم همین‌جاست.', '/assets/img/egame-arcade.png', '#e0195b', 23],
    ];
    $ins = db()->prepare('INSERT INTO categories (name,slug,description,image,color,sort_order,has_roles,kind) VALUES (?,?,?,?,?,?,0,\'electronic\')');
    foreach ($rows as $r) {
        $has = q('SELECT id FROM categories WHERE slug=?', [$r[1]])->fetch();
        if ($has) {
            q('UPDATE categories SET kind=?, image=?, description=? WHERE id=?', ['electronic', $r[3], $r[2], (int)$has['id']]);
            continue;
        }
        $ins->execute([$r[0], $r[1], $r[2], $r[3], $r[4], $r[5]]);
    }
    setting('egames_seed', '1');
}

function seed_voice_comments(): void
{
    if (setting('comments_seed') === '4voices') {
        return;
    }
    $presets = ['/assets/img/presets/p2.jpg', '/assets/img/presets/p4.jpg', '/assets/img/presets/p7.jpg', '/assets/img/presets/p11.jpg'];
    $rows = [
        ['آرین کاظمی', 'میز کاپو دیشب کامل بود. نقش‌ها روشن، گرداننده دقیق، قهوه هم سرجاش.', $presets[0]],
        ['سارا محمدی', 'گل یا پوچ کنار پنجره هنوز بهترین دورهمی رکاده. دست‌ها دروغ نمی‌گن.', $presets[1]],
        ['کیان رضایی', 'اکوسیستم کسب‌وکار نقش‌هاش باحاله. هوش مصنوعی میز را به هم ریخت، به معنی خوبش.', $presets[2]],
        ['نیکا احمدی', 'خدای بازی روی گوشی یعنی شب بدون کاغذ. نقش را نشان بده و برو.', $presets[3]],
    ];
    db()->exec('DELETE FROM comments');
    $ins = db()->prepare('INSERT INTO comments (user_id,name,body,avatar,featured,sort_order,created_at) VALUES (NULL,?,?,?,1,?,datetime("now"))');
    foreach ($rows as $i => $r) {
        $ins->execute([$r[0], $r[1], $r[2], $i]);
    }
    setting('comments_seed', '4voices');
}

function q(string $sql, array $p = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st;
}

function setting(string $k, ?string $v = null): string
{
    if ($v !== null) {
        q('INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value', [$k, $v]);
        return $v;
    }
    $st = q('SELECT value FROM settings WHERE key=?', [$k]);
    $row = $st->fetch();
    return $row ? (string)$row['value'] : '';
}

function seed_all(): void
{
    $pdo = db();
    $hashAdmin = password_hash('admin123', PASSWORD_DEFAULT);
    $hashUser = password_hash('gamekad', PASSWORD_DEFAULT);

    $people = [
        ['admin', 'nina.v@example.com', $hashAdmin, 'مدیر گیم‌کاد', '09120000000', 'admin', 'میزبان شب‌های بازی و قهوه تلخ گیم‌کاد.', '#2a1810'],
        ['arian', 'arian@gamekad.ir', $hashUser, 'آرین کاظمی', '09121111111', 'user', 'بازپرس همیشگی میز مافیا. قهوه بدون شکر، نقش بدون رحم.', '#4a1c14'],
        ['sara', 'sara@gamekad.ir', $hashUser, 'سارا محمدی', '09122222222', 'user', 'ملکه گل یا پوچ. دست‌های آرام، چشم‌های تیز.', '#3d2412'],
        ['kian', 'kian@gamekad.ir', $hashUser, 'کیان رضایی', '09123333333', 'user', 'پدرخوانده میزهای شلوغ. کم حرف، زیاد برنده.', '#1f2a22'],
        ['nika', 'nika@gamekad.ir', $hashUser, 'نیکا احمدی', '09124444444', 'user', 'دکتر نجات‌دهنده. عاشق سناریو آخرین حرکت.', '#2c1a28'],
        ['pouya', 'pouya@gamekad.ir', $hashUser, 'پویا کریمی', '09125555555', 'user', 'اسنایپر بی‌صدا. شطرنج‌باز قدیمی کافه.', '#3a2410'],
        ['elnaz', 'elnaz@gamekad.ir', $hashUser, 'الناز حسینی', '09126666666', 'user', 'شهروند نما و دروغ‌گوی حرفه‌ای. قهوه لاته.', '#24160e'],
        ['mahdi', 'mahdi@gamekad.ir', $hashUser, 'مهدی نوری', '09127777777', 'user', 'بردگیم‌باز شب‌های یکشنبه.', '#4a2c18'],
        ['yasamin', 'yasamin@gamekad.ir', $hashUser, 'یاسمن فتحی', '09128888888', 'user', 'گرگینه و مافیا، هر دو را دوست دارد.', '#1a2428'],
        ['amir', 'amir@gamekad.ir', $hashUser, 'امیرحسین جعفری', '09129999999', 'user', 'تازه‌وارد پرانرژی میز کلاسیک.', '#3d1a14'],
        ['donya', 'donya@gamekad.ir', $hashUser, 'دنیا مرادی', '09121234567', 'user', 'روان‌شناس میز. خواندن چهره تخصص اوست.', '#2a2210'],
        ['navid', 'navid@gamekad.ir', $hashUser, 'نوید صالحی', '09127654321', 'user', 'ساقی سناریوها و پادشاه شوخی‌های نیمه‌شب.', '#301810'],
    ];

    $insU = $pdo->prepare('INSERT INTO users (username,email,password,display_name,phone,avatar,bio,role,created_at) VALUES (?,?,?,?,?,?,?,?,datetime("now"))');
    $ids = [];
    foreach ($people as $p) {
        $rel = make_avatar($p[0], mb_substr($p[3], 0, 1), $p[7]);
        $insU->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $rel, $p[6], $p[5]]);
        $ids[$p[0]] = (int)$pdo->lastInsertId();
    }

    $copy = function (string $src, string $destRel) {
        $dest = ROOT . $destRel;
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0775, true);
        if (is_file($src) && !is_file($dest)) copy($src, $dest);
        return $destRel;
    };

    $cats = [
        ['مافیا', 'mafia', 'شب‌های تاریک، نقش‌های پنهان، قهوه تلخ. کلاسیک گیم‌کاد.', '/assets/img/cat-mafia.jpg', '#8b2942', 1, 1],
        ['گل یا پوچ', 'gol-ya-pooch', 'دو مشت، یک سکه، یک دروغ. خواندن حریف هنر این میز است.', '/assets/img/cat-golyapooch.jpg', '#c9a227', 2, 0],
        ['شطرنج', 'chess', 'روکاد واقعی. شاه، رخ و سکوت چوبی میزهای گوشه کافه.', '/assets/img/cat-chess.jpg', '#d4af37', 3, 0],
        ['بردگیم', 'boardgame', 'شب‌های جمعی با نقشه، تاس و قهوه دمی.', '/assets/img/cat-boardgame.jpg', '#6f4e37', 4, 0],
        ['گرگینه', 'werewolf', 'ماه پشت پنجره و گرگ میان جمع. نسخه روستایی مافیا.', '/assets/img/cat-werewolf.jpg', '#5c3d2e', 5, 0],
    ];
    $insC = $pdo->prepare('INSERT INTO categories (name,slug,description,image,color,sort_order,has_roles) VALUES (?,?,?,?,?,?,?)');
    $catIds = [];
    foreach ($cats as $c) {
        $insC->execute($c);
        $catIds[$c[1]] = (int)$pdo->lastInsertId();
    }

    $mafiaRoles = [
        ['شهروند', 'citizen', '#cbbba6'],
        ['مافیا', 'mafia', '#8b2942'],
        ['پدرخوانده', 'mafia', '#5a1018'],
        ['دکتر', 'citizen', '#3d6b4f'],
        ['بازپرس', 'citizen', '#c9a227'],
        ['اسنایپر', 'citizen', '#6a5a3a'],
        ['روانشناس', 'citizen', '#7a5a88'],
        ['ساقی', 'citizen', '#8a6a3a'],
        ['ناتاشا', 'mafia', '#7a3060'],
        ['شهردار', 'citizen', '#d4af37'],
    ];
    $insR = $pdo->prepare('INSERT INTO roles (category_id,name,side,color) VALUES (?,?,?,?)');
    $roleIds = [];
    foreach ($mafiaRoles as $r) {
        $insR->execute([$catIds['mafia'], $r[0], $r[1], $r[2]]);
        $roleIds[$r[0]] = (int)$pdo->lastInsertId();
    }
    $wolfRoles = [['روستایی','citizen','#cbbba6'],['گرگ','mafia','#5c3d2e'],['پیشگو','citizen','#c9a227'],['جادوگر','citizen','#7a5a88'],['شکارچی','citizen','#6a5a3a']];
    $wolfRoleIds = [];
    foreach ($wolfRoles as $r) {
        $insR->execute([$catIds['werewolf'], $r[0], $r[1], $r[2]]);
        $wolfRoleIds[$r[0]] = (int)$pdo->lastInsertId();
    }

    $insS = $pdo->prepare('INSERT INTO scenarios (category_id,name,description) VALUES (?,?,?)');
    $sc = [];
    foreach ([
        ['mafia', 'کلاسیک', 'نسخه استاندارد مافیا با نقش‌های پایه و شب‌های طولانی.'],
        ['mafia', 'آخرین حرکت', 'هر کس یک حرکت آخر دارد؛ تنش تا آخرین نفس.'],
        ['mafia', 'مافیا ناتو', 'نقش‌های ناتو، هویت پنهان و ائتلاف‌های خطرناک.'],
        ['mafia', 'گردن‌زنی', 'رأی مستقیم، بدون بحث اضافه. سریع و بی‌رحم.'],
        ['mafia', 'خداحافظی', 'حذف‌شده‌ها قبل از رفتن یک جمله می‌گویند. نمایش و فریب.'],
        ['werewolf', 'روستای مه', 'گرگینه کلاسیک با مه و سوءظن.'],
    ] as $row) {
        $insS->execute([$catIds[$row[0]], $row[1], $row[2]]);
        $sc[$row[0] . ':' . $row[1]] = (int)$pdo->lastInsertId();
    }

    $lb = $pdo->prepare('INSERT INTO leaderboard (category_id,user_id,points,wins,losses) VALUES (?,?,?,?,?)');
    $board = [
        ['mafia','arian',980,18,6],['mafia','kian',910,16,7],['mafia','nika',870,15,8],
        ['mafia','elnaz',820,14,9],['mafia','sara',760,12,8],['mafia','pouya',720,11,9],
        ['mafia','donya',690,10,8],['mafia','navid',640,9,10],['mafia','yasamin',610,9,11],
        ['mafia','amir',540,7,10],['mafia','mahdi',480,6,11],
        ['gol-ya-pooch','sara',1020,22,5],['gol-ya-pooch','donya',880,18,7],
        ['gol-ya-pooch','arian',740,14,8],['gol-ya-pooch','navid',700,13,9],
        ['gol-ya-pooch','elnaz',650,12,10],['gol-ya-pooch','kian',590,10,9],
        ['chess','pouya',1100,20,4],['chess','mahdi',860,15,7],['chess','arian',720,12,8],
        ['chess','kian',640,10,9],['chess','navid',500,8,10],
        ['boardgame','mahdi',900,14,4],['boardgame','yasamin',820,12,5],
        ['boardgame','amir',700,10,6],['boardgame','nika',660,9,6],
        ['werewolf','yasamin',940,16,6],['werewolf','kian',860,14,7],
        ['werewolf','nika',790,13,8],['werewolf','sara',710,11,8],
    ];
    foreach ($board as $b) {
        $lb->execute([$catIds[$b[0]], $ids[$b[1]], $b[2], $b[3], $b[4]]);
    }

    $insSess = $pdo->prepare('INSERT INTO game_sessions (category_id,title,description,session_date,start_time,end_time,location,total_seats,price,status,cover,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,datetime("now"))');
    $sessions = [
        [$catIds['mafia'], 'شب مافیا کلاسیک', '۱۲ نفره، نقش‌های کامل، قهوه مهمان میز. پیش‌پرداخت نیمی از هزینه برای رزرو صندلی.', '2026-09-04', '20:00', '23:30', 'سالن اصلی گیم‌کاد', 12, 150000, 'upcoming', '/assets/img/cat-mafia.jpg'],
        [$catIds['gol-ya-pooch'], 'میز گل یا پوچ', 'دورهمی سریع و پرهیجان. مناسب تازه‌واردها.', '2026-09-05', '18:30', '21:00', 'میز کنار پنجره', 8, 80000, 'upcoming', '/assets/img/cat-golyapooch.jpg'],
        [$catIds['mafia'], 'مافیا آخرین حرکت', 'سناریو ویژه آخر هفته. فقط بازیکنان با حداقل ۳ بازی.', '2026-09-06', '21:00', '00:30', 'سالن VIP چوبی', 10, 180000, 'upcoming', '/assets/img/blog-lastmove.jpg'],
        [$catIds['boardgame'], 'بردگیم نایت', 'سه عنوان روی میز، انتخاب جمعی، قهوه دمی.', '2026-09-11', '19:00', '22:30', 'سالن اصلی گیم‌کاد', 14, 100000, 'upcoming', '/assets/img/cat-boardgame.jpg'],
        [$catIds['werewolf'], 'گرگینه در مه', 'روستای مه، گرگ پنهان، ماه پشت شیشه.', '2026-09-12', '20:00', '23:00', 'سالن VIP چوبی', 12, 140000, 'upcoming', '/assets/img/cat-werewolf.jpg'],
        [$catIds['chess'], 'شب رخ و روکاد', 'رقابت ریتد کافه. ساعت شطرنج و سکوت.', '2026-09-13', '17:00', '21:00', 'گوشه شطرنج', 8, 60000, 'upcoming', '/assets/img/cat-chess.jpg'],
        [$catIds['mafia'], 'مافیا ناتو', 'نسخه ناتو برای بازیکنان حرفه‌ای.', '2026-09-18', '20:30', '00:00', 'سالن اصلی گیم‌کاد', 12, 160000, 'upcoming', '/assets/img/cat-mafia.jpg'],
        [$catIds['mafia'], 'شب مافیا مرداد', 'جلسه‌ی برگزارشده برای آرشیو آمار.', '2026-08-21', '20:00', '23:00', 'سالن اصلی گیم‌کاد', 12, 150000, 'finished', '/assets/img/cat-mafia.jpg'],
    ];
    $sessIds = [];
    foreach ($sessions as $i => $s) {
        $insSess->execute($s);
        $sessIds[$i] = (int)$pdo->lastInsertId();
    }

    $insRes = $pdo->prepare('INSERT INTO reservations (session_id,user_id,amount_paid,payment_status,attendance,created_at,paid_at) VALUES (?,?,?,?,?,datetime("now"),datetime("now"))');
    foreach (['arian','sara','kian','nika','pouya','elnaz'] as $u) {
        $insRes->execute([$sessIds[0], $ids[$u], 75000, 'paid_half', 'pending']);
    }
    foreach (['sara','donya','navid'] as $u) {
        $insRes->execute([$sessIds[1], $ids[$u], 40000, 'paid_half', 'pending']);
    }

    $insM = $pdo->prepare('INSERT INTO mvp (user_id,position,category_id,title,week_label,active) VALUES (?,?,?,?,?,1)');
    $insM->execute([$ids['arian'], 1, $catIds['mafia'], 'MVP مافیا', 'هفته اول شهریور']);
    $insM->execute([$ids['sara'], 2, $catIds['gol-ya-pooch'], 'MVP گل یا پوچ', 'هفته اول شهریور']);
    $insM->execute([$ids['kian'], 3, $catIds['mafia'], 'بهترین پدرخوانده', 'هفته اول شهریور']);

    $posts = [
        [
            'آموزش مافیا کلاسیک؛ نقش‌ها، شب و روز',
            'amozesh-mafia-classic',
            'از شهروند خاموش تا پدرخوانده: قوانین میز کلاسیک گیم‌کاد را یک‌بار برای همیشه یاد بگیر.',
            '<p>مافیا کلاسیک در گیم‌کاد روی میز چوبی ۱۲ نفره بازی می‌شود. گرداننده شب را اعلام می‌کند، نقش‌ها پخش می‌شود و از آن لحظه به بعد هر نگاه، یک مدرک است.</p>
            <h3>نقش‌های پایه</h3>
            <ul>
              <li><strong>شهروند:</strong> رأی، منطق و شنیدن لحن دیگران.</li>
              <li><strong>مافیا:</strong> شب‌ها حذف می‌کند، روزها خودش را شهروند جا می‌زند.</li>
              <li><strong>پدرخوانده:</strong> استعلام بازپرس روی او معمولاً بی‌نتیجه است.</li>
              <li><strong>دکتر:</strong> هر شب یک نفر را نجات می‌دهد.</li>
              <li><strong>بازپرس:</strong> هر شب استعلام می‌گیرد؛ ستون فقرات شهر.</li>
            </ul>
            <h3>ریتم شب</h3>
            <p>چشم‌ها بسته. مافیا بیدار می‌شود، هدف را نشان می‌دهد. دکتر بیدار می‌شود. بازپرس بیدار می‌شود. صبح، یک صندلی خالی و یک داستان تازه.</p>
            <p>در گیم‌کاد، بعد از هر بازی آمار نقش و برد در پروفایل ثبت می‌شود تا نمودار عنکبوتی مهارت شکل بگیرد.</p>',
            '/assets/img/blog-mafia-classic.jpg',
            $catIds['mafia'], 'tutorial', '', $ids['admin'],
        ],
        [
            'سناریو آخرین حرکت؛ وقتی هر کس یک تیر آخر دارد',
            'senario-akharin-harekat',
            'آخرین حرکت، مافیا را از یک بازی اجتماعی به یک دوئل تبدیل می‌کند. راهنمای کامل نقش و زمان‌بندی.',
            '<p>در سناریو آخرین حرکت، حذف‌شده‌ها بی‌صدا نمی‌روند. هر نقش یک توانایی نهایی دارد که می‌تواند نتیجه کل میز را عوض کند.</p>
            <h3>چرا این سناریو سخت است؟</h3>
            <p>اطلاعات دیرتر فاش می‌شود، بلوف گران‌تر تمام می‌شود و اسنایپر یا ناتاشا در لحظه مرگ تبدیل به برگ برنده می‌شوند.</p>
            <h3>توصیه میز گیم‌کاد</h3>
            <p>اگر بازپرس هستی، استعلام‌هایت را خرج آخر بازی نکن. اگر مافیا هستی، شهروندِ پرحرف را زنده نگه ندار. قهوه را قبل از شروع تمام کن؛ این سناریو طولانی است.</p>',
            '/assets/img/blog-lastmove.jpg',
            $catIds['mafia'], 'scenario', '', $ids['admin'],
        ],
        [
            'گل یا پوچ؛ خواندن دست، نه شانس',
            'gol-ya-pooch-ravesh',
            'سکه طلا، دو مشت بسته، یک حدس. تکنیک‌های کافه برای خواندن حریف بدون حرف زدن.',
            '<p>گل یا پوچ ساده‌ترین بازی کافه و در عین حال بی‌رحم‌ترین است. هیچ نقشی در کار نیست؛ فقط تو، مشت‌ها و دروغ.</p>
            <h3>سه عادت برندگان گیم‌کاد</h3>
            <ol>
              <li>ریتم ثابت: عجله یعنی لو رفتن.</li>
              <li>چشم روی شانه و فک، نه روی مشت.</li>
              <li>یک الگوی دروغ و شکستن همان الگو در دور پنجم.</li>
            </ol>
            <p>میز گل یا پوچ پنجشنبه‌ها کنار پنجره چوبی برپا می‌شود. پیش‌پرداخت نصف هزینه، صندلی مال توست.</p>',
            '/assets/img/blog-golyapooch.jpg',
            $catIds['gol-ya-pooch'], 'tutorial', '', $ids['admin'],
        ],
        [
            'گالری شب مافیا؛ دود، کارت، قهوه',
            'gallery-mafia-night',
            'چند قاب از سالن اصلی؛ نور شمع، کارت‌های سیاه و صندلی‌های پر.',
            '<p>این پست گالری است؛ عکس‌های شب‌های برگزارشده در سالن اصلی گیم‌کاد. اگر در عکس هستی و نمی‌خواهی بمانی، به ادمین پیام بده.</p>
            <p><img src="/assets/img/blog-mafia-classic.jpg" alt="میز مافیا"></p>
            <p><img src="/assets/img/hero-cafe.jpg" alt="فضای کافه"></p>',
            '/assets/img/hero-cafe.jpg',
            $catIds['mafia'], 'gallery', '', $ids['admin'],
        ],
        [
            'چطور بازپرس خوبی باشیم',
            'chetor-bazpors-khob',
            'استعلام، زمان‌بندی و هنر ساکت ماندن. درس‌های آرین کاظمی، MVP مافیا.',
            '<p>بازپرس بودن یعنی اطلاعات داری و همه می‌دانند که داری. هنر این است که اطلاعات را خرج کنی، نه اینکه خودت را خرج کنی.</p>
            <h3>قانون‌های میز</h3>
            <ul>
              <li>شب اول معمولاً استعلام شانسی است؛ روی آن قسم نخور.</li>
              <li>اگر مافیا شدی در بازی بعد، عادت‌های بازپرست را تکرار نکن.</li>
              <li>دکتر را لو نده مگر این‌که میز در حال فروپاشی باشد.</li>
            </ul>
            <p>آمار بازپرس در پروفایل‌ها با نمودار عنکبوتی مشخص است. اگر می‌خواهی رشد کنی، بعد از هر نشست نقش و نتیجه را در پنل ثبت می‌کنیم.</p>',
            '/assets/img/cat-mafia.jpg',
            $catIds['mafia'], 'tutorial', '', $ids['admin'],
        ],
        [
            'روکاد در شطرنج کافه؛ چرا اسم‌مان از رخ می‌آید',
            'rokad-chess-name',
            'گیم‌کاد از ترکیب Game و رخِ روکاد ساخته شده. یک یادداشت کوتاه درباره میز شطرنج گوشه سالن.',
            '<p>روکاد تنها حرکتی است که دو مهره با هم جابه‌جا می‌شوند؛ شاه به امنیت، رخ به میدان. کافه ما هم همین است: بازی جمعی، جای امن، و کمی جنگ.</p>
            <p>پنجشنبه‌ها میز شطرنج با ساعت آنالوگ باز است. ریتینگ کافه جدا از فیده نگه داشته می‌شود و در لیدربورد شطرنج می‌آید.</p>',
            '/assets/img/cat-chess.jpg',
            $catIds['chess'], 'news', '', $ids['admin'],
        ],
    ];
    $insB2 = $pdo->prepare('INSERT INTO blog_posts (title,slug,excerpt,content,cover,category_id,type,video_url,author_id,published,created_at) VALUES (?,?,?,?,?,?,?,?,?,1,?)');
    foreach ($posts as $i => $p) {
        $day = 18 - $i * 2;
        $insB2->execute([$p[0],$p[1],$p[2],$p[3],$p[4],$p[5],$p[6],$p[7],$p[8], sprintf('2026-08-%02d 18:00:00', max(1, $day))]);
    }

    setting('cafe_address', 'مشهد، بلوار شهید فرامرز عباسی، رسالت ۳۲، رسالت، بین رسالت ۴۵ و ۴۷، پلاک ۲۳ (محلهٔ شهید فرامرز عباسی)');
    setting('cafe_phone', '09332881180');
    setting('cafe_hours', '۱۶:۰۰ تا ۲۲:۰۰');
    setting('about', 'رکاد هنرستان پسرانه استارتاپی است. میزهای بازی گیم‌کاد اینجاست: مافیا، گل یا پوچ، شطرنج و بردگیم. صندلی با پیش‌پرداخت نصف رزرو می‌شود؛ اگر نیایی همان نصف می‌ماند.');
    setting('mvp_label', 'هفته اول شهریور ۱۴۰۵');
}
