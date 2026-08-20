<?php
declare(strict_types=1);

namespace App\Auth;

use App\Notify\AuthMailer;
use App\Support\Database;
use App\Support\Env;
use App\Support\RateLimiter;
use App\Support\Url;

final class EmailVerification
{
    public static function mode(): string
    {
        $mode = strtolower((string) Env::get('AUTH_VERIFICATION_MODE', 'otp'));
        return $mode === 'link' ? 'link' : 'otp';
    }

    public static function send(int $userId): void
    {
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user || $user['email_verified_at']) {
            return;
        }
        if (self::mode() === 'otp') {
            self::sendOtp($user);
            return;
        }
        self::sendLink($user);
    }

    /** @param array<string,mixed> $user */
    public static function sendLink(array $user): void
    {
        $token = bin2hex(random_bytes(24));
        Database::query(
            "INSERT INTO magic_links (email, token, purpose, payload_json, expires_at, created_at)
             VALUES (?, ?, 'email_verify', ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE), NOW())",
            [$user['email'], $token, json_encode(['user_id' => (int) $user['id']])]
        );
        $url = Url::to('/verify-email?token=' . $token);
        AuthMailer::verifyLink((string) $user['email'], (string) $user['first_name'], $url);
        AuthAudit::record('auth.verify.send_link', (int) $user['id']);
    }

    /** @param array<string,mixed> $user */
    public static function sendOtp(array $user): void
    {
        $code = (string) random_int(100000, 999999);
        Database::query(
            "UPDATE otp_codes SET used_at = NOW() WHERE email = ? AND purpose = 'email_verify' AND used_at IS NULL",
            [$user['email']]
        );
        Database::query(
            "INSERT INTO otp_codes (phone, email, purpose, code_hash, attempts, expires_at, created_at)
             VALUES (?, ?, 'email_verify', ?, 0, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())",
            [(string) ($user['phone'] ?: 'n/a'), $user['email'], password_hash($code, PASSWORD_DEFAULT)]
        );
        try {
            AuthMailer::verifyOtp((string) $user['email'], (string) $user['first_name'], $code);
            AuthAudit::record('auth.verify.send_otp', (int) $user['id']);
        } catch (\Throwable $e) {
            AuthAudit::record('auth.verify.send_otp_fail', (int) $user['id'], ['error' => $e->getMessage()]);
            throw new \RuntimeException(
                'We could not send your verification email. Please try again in a minute, or contact support if it continues.'
            );
        }
    }

    public static function canResend(int $userId): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        return RateLimiter::attempt('verify_resend:u:' . $userId, 5, 3600)
            && RateLimiter::attempt('verify_resend:ip:' . $ip, 10, 3600)
            && RateLimiter::attempt('verify_resend_cd:u:' . $userId, 1, 60);
    }

    public static function resend(int $userId): bool
    {
        if (!self::canResend($userId)) {
            return false;
        }
        self::send($userId);
        return true;
    }

    public static function verifyLinkToken(string $token): array
    {
        $row = Database::fetch(
            "SELECT * FROM magic_links WHERE token = ? AND purpose = 'email_verify' LIMIT 1",
            [$token]
        );
        if (!$row) {
            return ['ok' => false, 'reason' => 'invalid'];
        }
        $payload = json_decode($row['payload_json'] ?: '{}', true) ?: [];
        $uid = (int) ($payload['user_id'] ?? 0);
        if ($uid < 1) {
            return ['ok' => false, 'reason' => 'invalid'];
        }
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$uid]);
        if (!$user) {
            return ['ok' => false, 'reason' => 'invalid'];
        }
        if ($user['email_verified_at']) {
            return ['ok' => true, 'reason' => 'already', 'user_id' => $uid];
        }
        if ($row['used_at'] !== null || strtotime((string) $row['expires_at']) < time()) {
            return ['ok' => false, 'reason' => 'expired', 'user_id' => $uid];
        }
        self::markVerified($uid);
        Database::query('UPDATE magic_links SET used_at = NOW() WHERE id = ?', [$row['id']]);
        AuthAudit::record('auth.verify.success_link', $uid);
        return ['ok' => true, 'reason' => 'verified', 'user_id' => $uid];
    }

    public static function verifyOtpCode(int $userId, string $code): array
    {
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return ['ok' => false, 'reason' => 'invalid'];
        }
        if ($user['email_verified_at']) {
            return ['ok' => true, 'reason' => 'already', 'user_id' => $userId];
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        if (!RateLimiter::attempt('verify_otp:' . $userId, 5, 600)) {
            AuthAudit::record('auth.verify.otp_lockout', $userId);
            return ['ok' => false, 'reason' => 'locked'];
        }
        RateLimiter::attempt('verify_otp_ip:' . $ip, 20, 600);

        $row = Database::fetch(
            "SELECT * FROM otp_codes WHERE email = ? AND purpose = 'email_verify' AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1",
            [$user['email']]
        );
        if (!$row) {
            AuthAudit::record('auth.verify.otp_fail', $userId);
            return ['ok' => false, 'reason' => 'invalid'];
        }
        if ((int) $row['attempts'] >= 5) {
            return ['ok' => false, 'reason' => 'locked'];
        }
        if (!password_verify($code, $row['code_hash'])) {
            Database::query('UPDATE otp_codes SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);
            AuthAudit::record('auth.verify.otp_fail', $userId);
            return ['ok' => false, 'reason' => 'invalid'];
        }
        Database::query('UPDATE otp_codes SET used_at = NOW() WHERE id = ?', [$row['id']]);
        self::markVerified($userId);
        AuthAudit::record('auth.verify.success_otp', $userId);
        return ['ok' => true, 'reason' => 'verified', 'user_id' => $userId];
    }

    public static function markVerified(int $userId): void
    {
        Database::query(
            "UPDATE users SET email_verified_at = NOW(), status = 'active', updated_at = NOW() WHERE id = ?",
            [$userId]
        );
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$userId]);
        if ($user) {
            $persona = Database::fetch(
                'SELECT persona FROM participation_profiles WHERE user_id = ? ORDER BY id DESC LIMIT 1',
                [$userId]
            );
            AuthMailer::welcome(
                (string) $user['email'],
                (string) $user['first_name'],
                (string) ($persona['persona'] ?? 'attendee')
            );
        }
    }
}
