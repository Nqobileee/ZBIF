<?php
require dirname(__DIR__) . '/bootstrap.php';

use App\Support\Database;
use App\Support\FileCache;
use App\Support\Str;

Database::query(
    "UPDATE events SET
        starts_at = '2026-10-19 09:00:00',
        ends_at = '2026-10-22 17:00:00',
        venue = 'Zimbabwe International Trade Fair (ZITF)',
        city = 'Bulawayo',
        country = 'Zimbabwe',
        updated_at = NOW()
     WHERE slug = 'zbif-2026' OR id = 1"
);

$eventId = (int) (Database::fetch("SELECT id FROM events WHERE slug = 'zbif-2026'")['id'] ?? 1);

// Replace partner wall with curated public list.
Database::query('DELETE FROM partners WHERE event_id = ?', [$eventId]);
$partners = [
    ['financial', 'ZB', 'img/partners/zb-financial.png'],
    ['government_agencies', 'POTRAZ', 'img/partners/potraz.png'],
    ['financial', 'NVCCZ', 'img/partners/nvccz.png'],
];
foreach ($partners as $i => [$cat, $name, $logo]) {
    Database::query(
        'INSERT INTO partners (event_id, name, category, description, logo_path, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [$eventId, $name, $cat, 'Strategic partner for ZBIF 2026', $logo, $i]
    );
}

// Hide demo tier sponsors from public wall noise (keep rows for admin demos if needed, or rename).
Database::query("DELETE FROM sponsors WHERE event_id = ? AND name LIKE '%Demo%'", [$eventId]);
Database::query("DELETE FROM sponsors WHERE name LIKE '%Rilpix%' OR slug LIKE '%rilpix%'");

FileCache::flush();

$e = Database::fetch('SELECT id, starts_at, ends_at, venue, city FROM events WHERE id = ?', [$eventId]);
echo "Updated event {$e['id']}: {$e['starts_at']} → {$e['ends_at']} @ {$e['venue']}, {$e['city']}\n";
echo "Partners:\n";
foreach (Database::fetchAll('SELECT name FROM partners WHERE event_id = ? ORDER BY sort_order', [$eventId]) as $p) {
    echo ' - ' . $p['name'] . "\n";
}
