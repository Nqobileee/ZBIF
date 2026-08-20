<?php
declare(strict_types=1);

namespace App\Auth;

use App\Support\Database;

final class AuthSessionStore
{
    public static function register(int $userId, bool $remember = false): void
    {
        $hash = hash('sha256', session_id());
        Database::query(
            'INSERT INTO auth_sessions (user_id, session_id_hash, ip, user_agent, remember, last_seen_at, created_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $userId,
                $hash,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250),
                $remember ? 1 : 0,
            ]
        );
        $_SESSION['auth_session_hash'] = $hash;
        $_SESSION['remember_me'] = $remember;
    }

    public static function touch(): void
    {
        $hash = $_SESSION['auth_session_hash'] ?? null;
        if (!$hash) {
            return;
        }
        Database::query(
            'UPDATE auth_sessions SET last_seen_at = NOW() WHERE session_id_hash = ? AND revoked_at IS NULL',
            [$hash]
        );
    }

    public static function revokeCurrent(): void
    {
        $hash = $_SESSION['auth_session_hash'] ?? null;
        if ($hash) {
            Database::query('UPDATE auth_sessions SET revoked_at = NOW() WHERE session_id_hash = ?', [$hash]);
        }
    }

    public static function revokeAll(int $userId, bool $keepCurrent = false): void
    {
        $current = $_SESSION['auth_session_hash'] ?? '';
        if ($keepCurrent && $current !== '') {
            Database::query(
                'UPDATE auth_sessions SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL AND session_id_hash <> ?',
                [$userId, $current]
            );
            return;
        }
        Database::query(
            'UPDATE auth_sessions SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL',
            [$userId]
        );
    }

    public static function revokeById(int $userId, int $sessionRowId): bool
    {
        $row = Database::fetch(
            'SELECT id FROM auth_sessions WHERE id = ? AND user_id = ? AND revoked_at IS NULL',
            [$sessionRowId, $userId]
        );
        if (!$row) {
            return false;
        }
        Database::query('UPDATE auth_sessions SET revoked_at = NOW() WHERE id = ?', [$sessionRowId]);
        return true;
    }

    /** @return list<array<string,mixed>> */
    public static function listForUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT id, ip, user_agent, remember, last_seen_at, created_at, session_id_hash
             FROM auth_sessions WHERE user_id = ? AND revoked_at IS NULL ORDER BY last_seen_at DESC LIMIT 20',
            [$userId]
        );
    }

    public static function isCurrentRevoked(): bool
    {
        $hash = $_SESSION['auth_session_hash'] ?? null;
        if (!$hash) {
            return false;
        }
        $row = Database::fetch(
            'SELECT revoked_at FROM auth_sessions WHERE session_id_hash = ? ORDER BY id DESC LIMIT 1',
            [$hash]
        );
        return $row && $row['revoked_at'] !== null;
    }
}
