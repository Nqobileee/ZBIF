<?php
declare(strict_types=1);

namespace App\Support;

/** Key/value settings persisted in DB (SMTP, etc.). */
final class Settings
{
    private static bool $ready = false;

    public static function ensureTable(): void
    {
        if (self::$ready) {
            return;
        }
        try {
            Database::query(
                "CREATE TABLE IF NOT EXISTS system_settings (
                  setting_key VARCHAR(100) PRIMARY KEY,
                  setting_value TEXT NULL,
                  updated_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            self::$ready = true;
        } catch (\Throwable $e) {
            self::$ready = false;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        self::ensureTable();
        try {
            $row = Database::fetch('SELECT setting_value FROM system_settings WHERE setting_key = ?', [$key]);
            if ($row && $row['setting_value'] !== null && $row['setting_value'] !== '') {
                return (string) $row['setting_value'];
            }
        } catch (\Throwable $e) {
        }
        return $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::ensureTable();
        Database::query(
            'INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()',
            [$key, $value]
        );
    }

    /** @param array<string, string|null> $pairs */
    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $k => $v) {
            self::set($k, $v);
        }
    }

    /** Prefer DB setting, then .env. */
    public static function mail(string $key, string $envKey, string $default = ''): string
    {
        $db = self::get($key);
        if ($db !== null && $db !== '') {
            return $db;
        }
        return (string) Env::get($envKey, $default);
    }
}
