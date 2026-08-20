<?php
declare(strict_types=1);

namespace App\Auth;

use App\Support\Env;

final class PasswordPolicy
{
    public const MIN_LENGTH = 10;

    /** @return list<string> */
    public static function validate(string $password): array
    {
        $errors = [];
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = 'Use at least ' . self::MIN_LENGTH . ' characters.';
        }
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Include both letters and numbers.';
        }
        if (self::isCommon($password)) {
            $errors[] = 'That password is too common. Choose something stronger.';
        }
        if (Env::get('AUTH_PASSWORD_BREACH_CHECK', 'false') === 'true' && self::isBreached($password)) {
            $errors[] = 'That password appears in known breaches. Choose another.';
        }
        return $errors;
    }

    public static function strength(string $password): int
    {
        $score = 0;
        $len = strlen($password);
        if ($len >= 10) {
            $score += 1;
        }
        if ($len >= 14) {
            $score += 1;
        }
        if (preg_match('/[a-z]/', $password) && preg_match('/[A-Z]/', $password)) {
            $score += 1;
        }
        if (preg_match('/[0-9]/', $password)) {
            $score += 1;
        }
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 1;
        }
        return min(4, $score);
    }

    public static function isCommon(string $password): bool
    {
        $path = ZBIF_ROOT . '/storage/common-passwords.txt';
        if (!is_file($path)) {
            return false;
        }
        $lower = strtolower($password);
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            if (strtolower(trim($line)) === $lower) {
                return true;
            }
        }
        return false;
    }

    /** Have I Been Pwned k-anonymity. Fail-open if unreachable. */
    public static function isBreached(string $password): bool
    {
        try {
            $sha1 = strtoupper(sha1($password));
            $prefix = substr($sha1, 0, 5);
            $suffix = substr($sha1, 5);
            $ctx = stream_context_create(['http' => ['timeout' => 2, 'header' => "User-Agent: ZBIF-Auth\r\n"]]);
            $body = @file_get_contents('https://api.pwnedpasswords.com/range/' . $prefix, false, $ctx);
            if ($body === false) {
                return false;
            }
            foreach (explode("\n", $body) as $line) {
                $parts = explode(':', trim($line));
                if (isset($parts[0]) && strtoupper($parts[0]) === $suffix) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }
        return false;
    }
}
