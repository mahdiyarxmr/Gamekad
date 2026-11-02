<?php
$title = SITE_NAME_FA . ' — ' . SITE_TAGLINE;
$desc = 'رکاد؛ هنرستان استارتاپی و میزهای بازی گیم‌کاد — مافیا، گل یا پوچ، الکترونیک، لیدربورد و آموزش.';

$sessions = q('SELECT s.*, c.name AS cat_name,
              (SELECT COUNT(*) FROM reservations r WHERE r.session_id=s.id AND r.payment_status IN ("paid_half","paid_full")) AS reserved
              FROM game_sessions s JOIN categories c ON c.id=s.category_id
              WHERE s.status="upcoming" AND s.session_date >= date("now","localtime")
                AND ' . session_has_seat_sql() . '
              ORDER BY s.session_date, s.start_time LIMIT 3')->fetchAll();
$allCats = q('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$tableCats = array_values(array_filter($allCats, static fn($c) => cat_kind($c) !== 'electronic'));
$eCats = array_values(array_filter($allCats, static fn($c) => cat_kind($c) === 'electronic'));
$eIds = array_map(static fn($c) => (int)$c['id'], $eCats);
$eSessions = [];
if ($eIds) {
    $in = implode(',', array_fill(0, count($eIds), '?'));
    $eSessions = q('SELECT s.*, c.name AS cat_name,
              (SELECT COUNT(*) FROM reservations r WHERE r.session_id=s.id AND r.payment_status IN ("paid_half","paid_full")) AS reserved
              FROM game_sessions s JOIN categories c ON c.id=s.category_id
              WHERE s.status="upcoming" AND s.session_date >= date("now","localtime")
                AND s.category_id IN (' . $in . ') AND ' . session_has_seat_sql() . '
              ORDER BY s.session_date, s.start_time LIMIT 3', $eIds)->fetchAll();
}
$top = q('SELECT l.*, u.display_name, u.avatar, u.username, u.origin, u.gender, c.name AS cat_name
          FROM leaderboard l JOIN users u ON u.id=l.user_id JOIN categories c ON c.id=l.category_id
          WHERE c.slug="mafia" ORDER BY l.points DESC LIMIT 8')->fetchAll();
$posts = q('SELECT p.*, c.name AS cat_name FROM blog_posts p LEFT JOIN categories c ON c.id=p.category_id
            WHERE p.published=1 AND p.type!="gallery" ORDER BY p.created_at DESC LIMIT 3')->fetchAll();
$guideScens = q('SELECT s.*, c.name AS cat_name, c.slug AS cat_slug, c.image AS cat_image
                 FROM scenarios s JOIN categories c ON c.id=s.category_id
                 ORDER BY c.sort_order, s.id LIMIT 4')->fetchAll();

$data = compact('sessions', 'tableCats', 'eCats', 'eSessions', 'top', 'posts', 'guideScens');

require ROOT . '/includes/header.php';
require ROOT . '/includes/home_blocks.php';
foreach (home_layout() as $sec) {
    if (empty($sec['on'])) {
        continue;
    }
    render_home_block((string)$sec['id'], $data);
}
require ROOT . '/includes/footer.php';
