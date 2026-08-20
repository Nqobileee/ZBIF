<?php
declare(strict_types=1);

/**
 * Replace public partners with ZB, POTRAZ, and NVCCZ.
 * Usage: php scripts/seed-partners-zb-potraz-nvccz.php
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Domain\EventContext;
use App\Support\Database;
use App\Support\FileCache;

$eventId = EventContext::id();
if (!$eventId) {
    fwrite(STDERR, "No active event.\n");
    exit(1);
}

Database::query('DELETE FROM partners WHERE event_id = ?', [$eventId]);

$partners = [
    ['financial', 'ZB', 'img/partners/zb-financial.png'],
    ['government_agencies', 'POTRAZ', 'img/partners/potraz.png'],
    ['financial', 'NVCCZ', 'img/partners/nvccz.png'],
];

foreach ($partners as $i => [$cat, $name, $logo]) {
    Database::query(
        'INSERT INTO partners (event_id, name, category, description, logo_path, sort_order, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())',
        [$eventId, $name, $cat, 'Strategic partner for ZBIF 2026', $logo, $i]
    );
}

try {
    FileCache::forget('home_partners_' . $eventId);
} catch (Throwable $e) {
}

foreach (Database::fetchAll('SELECT name, logo_path FROM partners WHERE event_id = ? ORDER BY sort_order', [$eventId]) as $p) {
    echo " - {$p['name']} => {$p['logo_path']}\n";
}
echo "Partners updated for event #{$eventId}\n";
