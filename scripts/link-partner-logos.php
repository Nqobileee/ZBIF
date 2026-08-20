<?php
require dirname(__DIR__) . '/bootstrap.php';

use App\Support\Database;
use App\Support\FileCache;

$eventId = (int) (Database::fetch("SELECT id FROM events WHERE slug = 'zbif-2026'")['id'] ?? 1);

Database::query('DELETE FROM partners WHERE event_id = ?', [$eventId]);

$map = [
    ['ZB', 'img/partners/zb-financial.png', 'financial'],
    ['POTRAZ', 'img/partners/potraz.png', 'government_agencies'],
    ['NVCCZ', 'img/partners/nvccz.png', 'financial'],
];

foreach ($map as $i => [$name, $logo, $cat]) {
    Database::query(
        'INSERT INTO partners (event_id, name, category, description, logo_path, sort_order, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [$eventId, $name, $cat, 'Strategic partner for ZBIF 2026', $logo, $i]
    );
}

FileCache::flush();
echo "Partner logos linked:\n";
foreach (Database::fetchAll('SELECT name, logo_path FROM partners WHERE event_id = ? ORDER BY sort_order', [$eventId]) as $p) {
    echo " - {$p['name']} => {$p['logo_path']}\n";
}
