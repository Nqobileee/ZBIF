<?php
declare(strict_types=1);

namespace App\Domain;

use App\Support\Database;

/** Provisional venue / booth access badge for signed-in users. */
final class AccessBadge
{
    /** @param array<string,mixed> $user */
    public static function ensureForUser(array $user): string
    {
        $token = (string) ($user['qr_badge_token'] ?? '');
        if ($token !== '') {
            return $token;
        }
        $token = bin2hex(random_bytes(16));
        Database::query(
            'UPDATE users SET qr_badge_token = ?, updated_at = NOW() WHERE id = ? AND (qr_badge_token IS NULL OR qr_badge_token = \'\')',
            [$token, (int) $user['id']]
        );
        $row = Database::fetch('SELECT qr_badge_token FROM users WHERE id = ?', [(int) $user['id']]);
        return (string) ($row['qr_badge_token'] ?? $token);
    }

    public static function payload(string $token): string
    {
        return 'ZBIF-ACCESS:' . $token;
    }
}
