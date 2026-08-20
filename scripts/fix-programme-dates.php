<?php
require dirname(__DIR__) . '/bootstrap.php';

use App\Support\Database;
use App\Support\FileCache;

Database::query(
    "UPDATE programme_sessions
     SET starts_at = DATE_ADD(starts_at, INTERVAL 34 DAY),
         ends_at = DATE_ADD(ends_at, INTERVAL 34 DAY)
     WHERE event_id = 1 AND starts_at LIKE '2026-09-%'"
);

Database::query(
    "UPDATE faqs SET answer = ?
     WHERE question LIKE '%When is ZBIF%'",
    ['ZBIF takes place 19–22 October 2026 at the Zimbabwe International Trade Fair (ZITF) in Bulawayo.']
);

Database::query(
    "UPDATE news_posts SET body = REPLACE(body, 'September forum', 'October forum') WHERE body LIKE '%September%'"
);

FileCache::flush();
echo "OK\n";
$s = Database::fetch('SELECT starts_at, ends_at FROM programme_sessions WHERE event_id = 1 ORDER BY starts_at LIMIT 1');
print_r($s);
