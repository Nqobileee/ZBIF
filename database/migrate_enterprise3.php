<?php
declare(strict_types=1);

/**
 * Enterprise wave 3: sponsors, events, payments.
 * Usage: php database/migrate_enterprise3.php
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Support\Database;
use App\Support\Env;

function columnExists(string $table, string $column): bool
{
    $db = Env::get('DB_DATABASE', 'zbif');
    $row = Database::fetch(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$db, $table, $column]
    );
    return (int) ($row['c'] ?? 0) > 0;
}

$alters = [];
if (!columnExists('sponsorship_applications', 'tier_requested')) {
    $alters[] = "ALTER TABLE sponsorship_applications ADD COLUMN tier_requested ENUM('platinum','gold','silver','deal_room','innovation','university') NULL AFTER pitch";
}
if (!columnExists('sponsorship_applications', 'budget_usd')) {
    $alters[] = 'ALTER TABLE sponsorship_applications ADD COLUMN budget_usd DECIMAL(12,2) NULL AFTER tier_requested';
}
if (!columnExists('sponsorship_applications', 'company_name')) {
    $alters[] = 'ALTER TABLE sponsorship_applications ADD COLUMN company_name VARCHAR(255) NULL AFTER budget_usd';
}
if (!columnExists('sponsorship_applications', 'logo_path')) {
    $alters[] = 'ALTER TABLE sponsorship_applications ADD COLUMN logo_path VARCHAR(255) NULL AFTER company_name';
}
if (!columnExists('sponsorship_applications', 'website')) {
    $alters[] = 'ALTER TABLE sponsorship_applications ADD COLUMN website VARCHAR(255) NULL AFTER logo_path';
}
if (!columnExists('sponsorship_applications', 'sponsor_id')) {
    $alters[] = 'ALTER TABLE sponsorship_applications ADD COLUMN sponsor_id BIGINT UNSIGNED NULL AFTER reviewed_by';
}
if (!columnExists('participation_profiles', 'payment_reference')) {
    $alters[] = 'ALTER TABLE participation_profiles ADD COLUMN payment_reference VARCHAR(100) NULL AFTER payment_status';
}
if (!columnExists('events', 'registration_fee')) {
    $alters[] = 'ALTER TABLE events ADD COLUMN registration_fee DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER status';
}

foreach ($alters as $sql) {
    Database::query($sql);
    echo "OK alter: " . substr($sql, 0, 72) . "...\n";
}

Database::query(
    "CREATE TABLE IF NOT EXISTS payment_transactions (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id BIGINT UNSIGNED NOT NULL,
      participation_profile_id BIGINT UNSIGNED NULL,
      gateway VARCHAR(50) NOT NULL DEFAULT 'paynow',
      reference VARCHAR(100) NOT NULL,
      amount DECIMAL(12,2) NOT NULL,
      currency VARCHAR(10) NOT NULL DEFAULT 'USD',
      status ENUM('pending','paid','failed','cancelled','waived') NOT NULL DEFAULT 'pending',
      poll_url VARCHAR(255) NULL,
      raw_json JSON NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      UNIQUE KEY uq_pay_ref (reference),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "OK payment_transactions\n";

Database::query(
    "CREATE TABLE IF NOT EXISTS sponsor_entitlements (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      sponsor_id BIGINT UNSIGNED NOT NULL,
      entitlement_key VARCHAR(100) NOT NULL,
      entitlement_value TEXT NULL,
      created_at DATETIME NOT NULL,
      UNIQUE KEY uq_sponsor_ent (sponsor_id, entitlement_key),
      FOREIGN KEY (sponsor_id) REFERENCES sponsors(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);
echo "OK sponsor_entitlements\n";

echo "Enterprise wave 3 migration complete.\n";
