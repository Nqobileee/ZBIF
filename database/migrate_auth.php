<?php
declare(strict_types=1);

/**
 * Auth UX migration: status, sessions, password reset, 2FA, draft steps, OTP email.
 * Usage: php database/migrate_auth.php
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

function tableExists(string $table): bool
{
    $db = Env::get('DB_DATABASE', 'zbif');
    $row = Database::fetch(
        'SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
        [$db, $table]
    );
    return (int) ($row['c'] ?? 0) > 0;
}

$alters = [];

if (!columnExists('users', 'status')) {
    $alters[] = "ALTER TABLE users ADD COLUMN status ENUM('unverified','active','suspended') NOT NULL DEFAULT 'unverified' AFTER is_active";
}
if (!columnExists('users', 'last_login_ip')) {
    $alters[] = 'ALTER TABLE users ADD COLUMN last_login_ip VARCHAR(45) NULL AFTER last_login_at';
}
if (!columnExists('users', 'locale')) {
    $alters[] = "ALTER TABLE users ADD COLUMN locale VARCHAR(10) NOT NULL DEFAULT 'en' AFTER last_login_ip";
}
if (!columnExists('users', 'totp_secret')) {
    $alters[] = 'ALTER TABLE users ADD COLUMN totp_secret VARCHAR(255) NULL AFTER locale';
}
if (!columnExists('users', 'totp_confirmed_at')) {
    $alters[] = 'ALTER TABLE users ADD COLUMN totp_confirmed_at DATETIME NULL AFTER totp_secret';
}
if (!columnExists('users', 'totp_recovery_codes')) {
    $alters[] = 'ALTER TABLE users ADD COLUMN totp_recovery_codes TEXT NULL AFTER totp_confirmed_at';
}

if (!columnExists('registration_drafts', 'user_id')) {
    $alters[] = 'ALTER TABLE registration_drafts ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id';
}
if (!columnExists('registration_drafts', 'current_step')) {
    $alters[] = "ALTER TABLE registration_drafts ADD COLUMN current_step VARCHAR(40) NOT NULL DEFAULT 'type' AFTER persona";
}

if (!columnExists('otp_codes', 'email')) {
    $alters[] = 'ALTER TABLE otp_codes ADD COLUMN email VARCHAR(255) NULL AFTER phone';
}
if (!columnExists('otp_codes', 'purpose')) {
    $alters[] = "ALTER TABLE otp_codes ADD COLUMN purpose VARCHAR(40) NOT NULL DEFAULT 'phone' AFTER email";
}
if (!columnExists('otp_codes', 'attempts')) {
    $alters[] = 'ALTER TABLE otp_codes ADD COLUMN attempts INT NOT NULL DEFAULT 0 AFTER code_hash';
}

if (!columnExists('audit_logs', 'user_agent')) {
    $alters[] = 'ALTER TABLE audit_logs ADD COLUMN user_agent VARCHAR(255) NULL AFTER ip';
}

foreach ($alters as $sql) {
    Database::query($sql);
    echo "OK: {$sql}\n";
}

if (!tableExists('password_reset_tokens')) {
    Database::query(
        "CREATE TABLE password_reset_tokens (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          email VARCHAR(255) NOT NULL,
          token_hash VARCHAR(255) NOT NULL,
          expires_at DATETIME NOT NULL,
          used_at DATETIME NULL,
          created_at DATETIME NOT NULL,
          INDEX idx_prt_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "OK: created password_reset_tokens\n";
}

if (!tableExists('auth_sessions')) {
    Database::query(
        "CREATE TABLE auth_sessions (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_id BIGINT UNSIGNED NOT NULL,
          session_id_hash VARCHAR(64) NOT NULL,
          ip VARCHAR(45) NULL,
          user_agent VARCHAR(255) NULL,
          remember TINYINT(1) NOT NULL DEFAULT 0,
          last_seen_at DATETIME NOT NULL,
          revoked_at DATETIME NULL,
          created_at DATETIME NOT NULL,
          INDEX idx_auth_sess_user (user_id),
          INDEX idx_auth_sess_hash (session_id_hash),
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "OK: created auth_sessions\n";
}

// Backfill verified users as active
Database::query("UPDATE users SET status = 'active' WHERE email_verified_at IS NOT NULL AND status = 'unverified'");
Database::query("UPDATE users SET status = 'suspended' WHERE is_active = 0 AND status <> 'suspended'");

echo "Auth migration complete.\n";
