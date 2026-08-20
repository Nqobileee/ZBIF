<?php
declare(strict_types=1);

namespace App\Auth;

use App\Rbac\Gate;
use App\Support\Database;
use App\Support\Env;
use App\Support\RateLimiter;

final class Auth
{
    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $emailNorm = strtolower(trim($email));
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';

        if (!RateLimiter::attempt('login:ip:' . $ip, 5, 60)) {
            AuthAudit::record('auth.login.lockout_ip', null, ['email' => $emailNorm]);
            return false;
        }
        if (!RateLimiter::attempt('login:email:' . $emailNorm, 5, 60)) {
            AuthAudit::record('auth.login.lockout_email', null, ['email' => $emailNorm]);
            return false;
        }

        $user = Database::fetch('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1', [$emailNorm]);
        if (!$user || !password_verify($password, $user['password'])) {
            AuthAudit::record('auth.login.failure', $user ? (int) $user['id'] : null, ['email' => $emailNorm]);
            return false;
        }
        if (!(int) $user['is_active'] || ($user['status'] ?? '') === 'suspended') {
            AuthAudit::record('auth.login.suspended', (int) $user['id']);
            return false;
        }
        if (empty($user['email_verified_at'])) {
            AuthAudit::record('auth.login.unverified', (int) $user['id']);
            return false;
        }

        self::login((int) $user['id'], $remember);
        AuthAudit::record('auth.login.success', (int) $user['id']);
        return true;
    }

    public static function login(int $userId, bool $remember = false): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = $userId;
        unset($_SESSION['pending_2fa_user_id']);
        self::clearPendingVerify();

        $days = max(1, (int) Env::get('AUTH_REMEMBER_DAYS', '30'));
        $idle = max(30, (int) Env::get('AUTH_SESSION_IDLE_MINUTES', '120'));
        $lifetime = $remember ? ($days * 86400) : ($idle * 60);
        if (!headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires' => time() + $lifetime,
                'path' => $params['path'] ?: '/',
                'domain' => $params['domain'] ?: '',
                'secure' => (bool) $params['secure'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        try {
            AuthSessionStore::register($userId, $remember);
        } catch (\Throwable $e) {
            // Table may not exist before migrate_auth
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        Database::query(
            'UPDATE users SET last_login_at = NOW(), last_login_ip = ?, updated_at = NOW() WHERE id = ?',
            [$ip, $userId]
        );
    }

    /** After password check, if 2FA required, stash pending user and return true. */
    public static function needsTwoFactor(int $userId): bool
    {
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user || empty($user['totp_confirmed_at'])) {
            return false;
        }
        $required = array_filter(array_map('trim', explode(',', (string) Env::get(
            'AUTH_2FA_REQUIRED_ROLES',
            'super_admin,organizer,technical_committee'
        ))));
        $roles = Gate::rolesFor($userId);
        foreach ($required as $role) {
            if (in_array($role, $roles, true)) {
                return true;
            }
        }
        // Also challenge if user enabled 2FA even when not required
        return true;
    }

    public static function beginTwoFactor(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['pending_2fa_user_id'] = $userId;
        unset($_SESSION['user_id']);
    }

    public static function pendingTwoFactorId(): ?int
    {
        return isset($_SESSION['pending_2fa_user_id']) ? (int) $_SESSION['pending_2fa_user_id'] : null;
    }

    public static function completeTwoFactor(bool $remember = false): void
    {
        $uid = self::pendingTwoFactorId();
        if ($uid === null) {
            return;
        }
        unset($_SESSION['pending_2fa_user_id']);
        self::login($uid, $remember);
        AuthAudit::record('auth.2fa.success', $uid);
    }

    public static function logout(): void
    {
        $uid = self::id();
        try {
            AuthSessionStore::revokeCurrent();
        } catch (\Throwable $e) {
        }
        if ($uid) {
            AuthAudit::record('auth.logout', $uid);
        }
        self::clearAdminPortal();
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (ini_get('session.use_cookies') && !headers_sent()) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
            }
            session_destroy();
        }
    }

    public static function logoutAll(): void
    {
        $uid = self::id();
        if ($uid) {
            try {
                AuthSessionStore::revokeAll($uid, false);
            } catch (\Throwable $e) {
            }
            AuthAudit::record('auth.logout_all', $uid);
        }
        self::logout();
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }
        try {
            if (AuthSessionStore::isCurrentRevoked()) {
                self::logout();
                return null;
            }
            AuthSessionStore::touch();
        } catch (\Throwable $e) {
        }
        return Database::fetch('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if (!$user) {
            \App\Support\Response::redirect('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/app'));
        }
        if (empty($user['email_verified_at'])) {
            $uid = (int) $user['id'];
            unset($_SESSION['user_id']);
            self::beginPendingVerify($uid);
            \App\Support\Response::flash('error', 'Verify your email before continuing.');
            \App\Support\Response::redirect('/verify-email/otp');
        }
        return $user;
    }

    public static function beginPendingVerify(int $userId): void
    {
        $_SESSION['pending_verify_user_id'] = $userId;
    }

    public static function pendingVerifyId(): ?int
    {
        return isset($_SESSION['pending_verify_user_id']) ? (int) $_SESSION['pending_verify_user_id'] : null;
    }

    public static function clearPendingVerify(): void
    {
        unset($_SESSION['pending_verify_user_id']);
    }

    /** User awaiting email OTP (guest) or logged-in unverified. */
    public static function requirePendingVerifyUser(): array
    {
        $uid = self::pendingVerifyId() ?? self::id();
        if ($uid === null) {
            \App\Support\Response::flash('error', 'Create an account or sign in to verify your email.');
            \App\Support\Response::redirect('/register');
        }
        $user = Database::fetch('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$uid]);
        if (!$user) {
            self::clearPendingVerify();
            \App\Support\Response::flash('error', 'Account not found. Please register again.');
            \App\Support\Response::redirect('/register');
        }
        if (!empty($user['email_verified_at'])) {
            self::clearPendingVerify();
            if (self::id() === $uid) {
                unset($_SESSION['user_id']);
            }
            \App\Support\Response::flash('success', 'Your email is already verified. Sign in to continue.');
            \App\Support\Response::redirect('/login.php');
        }
        self::beginPendingVerify($uid);
        return $user;
    }

    /** Admin area: must authenticate via /admin/login.php and hold admin.access. */
    public static function requireAdmin(): array
    {
        $user = self::user();
        if (!$user) {
            \App\Support\Response::redirect('/admin/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/dashboard'));
        }
        if (!Gate::allows('admin.access', (int) $user['id'])) {
            http_response_code(403);
            \App\Support\View::make('public/403', ['title' => 'Forbidden'], 'layouts/public');
            exit;
        }
        if (empty($_SESSION['admin_portal'])) {
            self::clearAdminPortal();
            \App\Support\Response::flash('error', 'Sign in through the organiser portal to access admin.');
            \App\Support\Response::redirect('/admin/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/dashboard'));
        }
        return $user;
    }

    public static function grantAdminPortal(): void
    {
        $_SESSION['admin_portal'] = true;
        $_SESSION['admin_portal_at'] = time();
    }

    public static function clearAdminPortal(): void
    {
        unset($_SESSION['admin_portal'], $_SESSION['admin_portal_at']);
    }

    public static function hasAdminPortal(): bool
    {
        return !empty($_SESSION['admin_portal']);
    }

    public static function isVerified(?array $user = null): bool
    {
        $user = $user ?? self::user();
        return $user && !empty($user['email_verified_at']);
    }

    public static function requireVerified(): array
    {
        $user = self::requireLogin();
        if (empty($user['email_verified_at'])) {
            self::beginPendingVerify((int) $user['id']);
            unset($_SESSION['user_id']);
            \App\Support\Response::flash('error', 'Verify your email before continuing.');
            \App\Support\Response::redirect('/verify-email/otp');
        }
        return $user;
    }
}
