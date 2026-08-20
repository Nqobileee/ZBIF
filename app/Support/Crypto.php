<?php
declare(strict_types=1);

namespace App\Support;

/** AES-256-CBC encryption for secrets at rest (TOTP, recovery codes, etc.). */
final class Crypto
{
    private static function key(): string
    {
        $raw = (string) Env::get('AUTH_APP_KEY', '');
        if ($raw === '') {
            $raw = (string) Env::get('APP_URL', 'zbif-insecure-fallback');
        }
        // Derive a binary 32-byte key; prefer explicit app key
        return hash('sha256', $raw, true);
    }

    public static function encrypt(string $plain): string
    {
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new \RuntimeException('Encryption failed.');
        }
        $mac = hash_hmac('sha256', $iv . $cipher, self::key(), true);
        return base64_encode($mac . $iv . $cipher);
    }

    public static function decrypt(string $payload): ?string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 48) {
            // Legacy format: iv(16) + cipher (pre-HMAC)
            return self::decryptLegacy($payload);
        }
        $mac = substr($raw, 0, 32);
        $iv = substr($raw, 32, 16);
        $cipher = substr($raw, 48);
        $calc = hash_hmac('sha256', $iv . $cipher, self::key(), true);
        if (!hash_equals($mac, $calc)) {
            return self::decryptLegacy($payload);
        }
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);
        return $plain === false ? null : $plain;
    }

    private static function decryptLegacy(string $payload): ?string
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 17) {
            return null;
        }
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', self::key(), OPENSSL_RAW_DATA, $iv);
        return $plain === false ? null : $plain;
    }
}
