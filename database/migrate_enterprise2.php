<?php
declare(strict_types=1);

/**
 * Enterprise wave 2 tables/columns.
 * Usage: php database/migrate_enterprise2.php
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Support\Database;

function columnExists(string $table, string $column): bool
{
    $db = \App\Support\Env::get('DB_DATABASE', 'zbif');
    $row = Database::fetch(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$db, $table, $column]
    );
    return (int) ($row['c'] ?? 0) > 0;
}

$alters = [];
if (!columnExists('deal_rooms', 'checklist_json')) {
    $alters[] = 'ALTER TABLE deal_rooms ADD COLUMN checklist_json JSON NULL AFTER agreement_summary';
}
if (!columnExists('deal_room_participants', 'nda_accepted_at')) {
    $alters[] = 'ALTER TABLE deal_room_participants ADD COLUMN nda_accepted_at DATETIME NULL AFTER last_seen_at';
}
if (!columnExists('leads', 'status')) {
    $alters[] = "ALTER TABLE leads ADD COLUMN status ENUM('new','contacted','qualified','meeting','won','lost') NOT NULL DEFAULT 'new' AFTER notes";
}
if (!columnExists('leads', 'tags')) {
    $alters[] = 'ALTER TABLE leads ADD COLUMN tags VARCHAR(255) NULL AFTER status';
}
if (!columnExists('leads', 'follow_up_at')) {
    $alters[] = 'ALTER TABLE leads ADD COLUMN follow_up_at DATETIME NULL AFTER tags';
}
if (!columnExists('investor_watchlist', 'pipeline_stage')) {
    $alters[] = "ALTER TABLE investor_watchlist ADD COLUMN pipeline_stage ENUM('interest','diligence','term_sheet','closed','passed') NOT NULL DEFAULT 'interest' AFTER solution_id";
}
if (!columnExists('notification_preferences', 'digest_enabled')) {
    $alters[] = 'ALTER TABLE notification_preferences ADD COLUMN digest_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER in_app_enabled';
}

foreach ($alters as $sql) {
    Database::query($sql);
    echo "OK alter: " . substr($sql, 0, 70) . "...\n";
}

$creates = [
    "CREATE TABLE IF NOT EXISTS lead_magnet_captures (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      email VARCHAR(255) NOT NULL,
      name VARCHAR(150) NULL,
      magnet_type VARCHAR(50) NOT NULL DEFAULT 'foresight_pdf',
      meta_json JSON NULL,
      created_at DATETIME NOT NULL,
      INDEX idx_magnet_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS deal_checklist_items (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      deal_room_id BIGINT UNSIGNED NOT NULL,
      label VARCHAR(255) NOT NULL,
      is_done TINYINT(1) NOT NULL DEFAULT 0,
      done_by BIGINT UNSIGNED NULL,
      done_at DATETIME NULL,
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL,
      FOREIGN KEY (deal_room_id) REFERENCES deal_rooms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($creates as $sql) {
    Database::query($sql);
    echo "OK create: " . substr($sql, 0, 60) . "...\n";
}

echo "Enterprise wave 2 migration complete.\n";
