<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
db_init();
echo 'users=' . db()->query('SELECT COUNT(*) FROM users')->fetchColumn() . PHP_EOL;
echo 'cats=' . db()->query('SELECT COUNT(*) FROM categories')->fetchColumn() . PHP_EOL;
echo 'posts=' . db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn() . PHP_EOL;
echo 'stats=' . db()->query('SELECT COUNT(*) FROM player_stats')->fetchColumn() . PHP_EOL;
echo 'mvp=' . db()->query('SELECT COUNT(*) FROM mvp')->fetchColumn() . PHP_EOL;
echo 'sess=' . db()->query('SELECT COUNT(*) FROM game_sessions')->fetchColumn() . PHP_EOL;
echo 'roles=' . db()->query('SELECT COUNT(*) FROM roles')->fetchColumn() . PHP_EOL;
echo 'ok';
