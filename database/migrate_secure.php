<?php
declare(strict_types=1);

/**
 * Security / normalization hardening migration.
 * Usage: php database/migrate_secure.php
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Support\Database;
use App\Support\Env;
use App\Support\Settings;

function columnExists(string $table, string $column): bool
{
    $db = Env::get('DB_DATABASE', 'zbif');
    $row = Database::fetch(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$db, $table, $column]
    );
    return (int) ($row['c'] ?? 0) > 0;
}

function indexExists(string $table, string $index): bool
{
    $db = Env::get('DB_DATABASE', 'zbif');
    $row = Database::fetch(
        'SELECT COUNT(*) AS c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
        [$db, $table, $index]
    );
    return (int) ($row['c'] ?? 0) > 0;
}

Settings::ensureTable();
echo "OK: system_settings\n";

$alters = [];
if (!columnExists('users', 'password_changed_at')) {
    $alters[] = 'ALTER TABLE users ADD COLUMN password_changed_at DATETIME NULL AFTER password';
}
if (!columnExists('users', 'failed_login_count')) {
    $alters[] = 'ALTER TABLE users ADD COLUMN failed_login_count INT NOT NULL DEFAULT 0 AFTER last_login_ip';
}
if (!columnExists('users', 'locked_until')) {
    $alters[] = 'ALTER TABLE users ADD COLUMN locked_until DATETIME NULL AFTER failed_login_count';
}

foreach ($alters as $sql) {
    Database::query($sql);
    echo "OK: {$sql}\n";
}

$indexes = [
    ['users', 'idx_users_email_active', 'CREATE INDEX idx_users_email_active ON users (email, is_active)'],
    ['model_has_roles', 'idx_mhr_user', 'CREATE INDEX idx_mhr_user ON model_has_roles (user_id)'],
    ['deal_rooms', 'idx_deal_stage', 'CREATE INDEX idx_deal_stage ON deal_rooms (stage)'],
    ['participation_profiles', 'idx_pp_event_status', 'CREATE INDEX idx_pp_event_status ON participation_profiles (event_id, status)'],
    ['programme_sessions', 'idx_prog_event_day', 'CREATE INDEX idx_prog_event_day ON programme_sessions (event_id, day_number, starts_at)'],
];

foreach ($indexes as [$table, $name, $sql]) {
    try {
        if (!indexExists($table, $name)) {
            Database::query($sql);
            echo "OK: {$sql}\n";
        }
    } catch (Throwable $e) {
        echo "SKIP index {$name}: " . $e->getMessage() . "\n";
    }
}

$key = Env::get('AUTH_APP_KEY', '');
if ($key === '' || strlen($key) < 16) {
    echo "WARN: Set AUTH_APP_KEY in .env to a long random secret (used for AES encryption of 2FA secrets).\n";
} else {
    echo "OK: AUTH_APP_KEY present\n";
}

echo "migrate_secure done.\n";
