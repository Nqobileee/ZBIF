<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\Auth;
use App\Auth\AuthAudit;
use App\Auth\Totp;
use App\Notify\AuthMailer;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Crypto;
use App\Support\Database;
use App\Support\RateLimiter;
use App\Support\Response;
use App\Support\Url;
use App\Support\View;

final class LoginController
{
    public function show(): void
    {
        if (Auth::check()) {
            Response::redirect('/app');
        }
        View::make('auth/login', ['title' => 'Sign in'], 'layouts/auth');
    }

    public function showAdmin(): void
    {
        if (Auth::check() && Auth::hasAdminPortal() && Gate::allows('admin.access')) {
            Response::redirect('/dashboard');
        }
        View::make('auth/admin-login', ['title' => 'Admin sign in'], 'layouts/admin-auth');
    }

    public function login(): void
    {
        Csrf::requireValid();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $remember = !empty($_POST['remember']);

        if (!RateLimiter::attempt('login:ip:' . $ip, 5, 60) || !RateLimiter::attempt('login:email:' . $email, 5, 60)) {
            Response::flash('error', 'Too many attempts. Please wait and try again.');
            Response::redirect('/login.php');
        }

        $user = Database::fetch('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1', [$email]);
        if ($user && !empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time()) {
            AuthAudit::record('auth.login.locked', (int) $user['id']);
            Response::flash('error', 'This account is temporarily locked. Try again later.');
            Response::redirect('/login.php');
        }
        if (!$user || !password_verify($password, $user['password']) || !(int) $user['is_active'] || ($user['status'] ?? '') === 'suspended') {
            if ($user) {
                Database::query(
                    'UPDATE users SET failed_login_count = failed_login_count + 1, locked_until = IF(failed_login_count + 1 >= 8, DATE_ADD(NOW(), INTERVAL 15 MINUTE), locked_until), updated_at = NOW() WHERE id = ?',
                    [(int) $user['id']]
                );
            }
            AuthAudit::record('auth.login.failure', $user ? (int) $user['id'] : null, ['email' => $email]);
            Response::flash('error', 'Those details do not match our records.');
            Response::redirect('/login.php');
        }

        if (empty($user['email_verified_at'])) {
            $uid = (int) $user['id'];
            Auth::beginPendingVerify($uid);
            AuthAudit::record('auth.login.unverified', $uid);
            Response::flash('error', 'Verify your email before signing in. Enter the code we sent, or request a new one.');
            Response::redirect('/verify-email/otp');
        }

        $uid = (int) $user['id'];
        Database::query('UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE id = ?', [$uid]);
        // Participant portal never opens the admin gate
        Auth::clearAdminPortal();

        if (Auth::needsTwoFactor($uid)) {
            Auth::beginTwoFactor($uid);
            $_SESSION['pending_2fa_remember'] = $remember;
            $_SESSION['pending_2fa_context'] = 'app';
            Response::redirect('/login/2fa');
        }

        Auth::login($uid, $remember);
        AuthAudit::record('auth.login.success', $uid, ['portal' => 'app']);
        $this->maybeNewDeviceNotice($user);

        $redirect = $_POST['redirect'] ?? '/app';
        if (!is_string($redirect) || !str_starts_with($redirect, '/') || str_starts_with($redirect, '/admin')) {
            $redirect = '/app';
        }
        Response::redirect($redirect);
    }

    public function loginAdmin(): void
    {
        Csrf::requireValid();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $remember = !empty($_POST['remember']);

        if (!RateLimiter::attempt('adminlogin:ip:' . $ip, 5, 60) || !RateLimiter::attempt('adminlogin:email:' . $email, 5, 60)) {
            Response::flash('error', 'Too many attempts. Please wait and try again.');
            Response::redirect('/admin/login.php');
        }

        $user = Database::fetch('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1', [$email]);
        if ($user && !empty($user['locked_until']) && strtotime((string) $user['locked_until']) > time()) {
            AuthAudit::record('auth.admin_login.locked', (int) $user['id']);
            Response::flash('error', 'This account is temporarily locked. Try again later.');
            Response::redirect('/admin/login.php');
        }
        $uid = $user ? (int) $user['id'] : 0;
        $ok = $user
            && password_verify($password, $user['password'])
            && (int) $user['is_active']
            && ($user['status'] ?? '') !== 'suspended'
            && Gate::allows('admin.access', $uid);

        if (!$ok) {
            if ($user) {
                Database::query(
                    'UPDATE users SET failed_login_count = failed_login_count + 1, locked_until = IF(failed_login_count + 1 >= 8, DATE_ADD(NOW(), INTERVAL 15 MINUTE), locked_until), updated_at = NOW() WHERE id = ?',
                    [$uid]
                );
            }
            AuthAudit::record('auth.admin_login.failure', $uid ?: null, ['email' => $email]);
            // Same message whether user exists or lacks privilege (no account enumeration)
            Response::flash('error', 'Those details do not match organiser records.');
            Response::redirect('/admin/login.php');
        }

        Database::query('UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE id = ?', [$uid]);

        if (Auth::needsTwoFactor($uid)) {
            Auth::beginTwoFactor($uid);
            $_SESSION['pending_2fa_remember'] = $remember;
            $_SESSION['pending_2fa_context'] = 'admin';
            Response::redirect('/admin/login/2fa');
            return;
        }

        Auth::login($uid, $remember);
        Auth::grantAdminPortal();
        AuthAudit::record('auth.admin_login.success', $uid);
        $this->maybeNewDeviceNotice($user);

        $redirect = $_POST['redirect'] ?? '/dashboard';
        if (!is_string($redirect) || !self::isSafeAdminRedirect($redirect)) {
            $redirect = '/dashboard';
        }
        Response::redirect($redirect);
    }

    public function requestMagic(): void
    {
        Csrf::requireValid();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        RateLimiter::attempt('magic:ip:' . $ip, 5, 600);
        RateLimiter::attempt('magic:email:' . $email, 3, 600);

        $neutral = 'If that email is registered, a link is on its way.';
        $user = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? Database::fetch('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL AND is_active = 1', [$email])
            : null;
        if ($user) {
            $token = bin2hex(random_bytes(24));
            Database::query(
                "INSERT INTO magic_links (email, token, purpose, payload_json, expires_at, created_at)
                 VALUES (?, ?, 'login', ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW())",
                [$email, $token, json_encode(['user_id' => (int) $user['id']])]
            );
            AuthMailer::loginLink($email, Url::to('/login/magic?token=' . $token));
            AuthAudit::record('auth.magic.send', (int) $user['id']);
        } else {
            AuthAudit::record('auth.magic.send_unknown', null, ['email' => $email]);
        }
        Response::flash('success', $neutral);
        Response::redirect('/login.php');
    }

    public function consumeMagic(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        $row = Database::fetch(
            "SELECT * FROM magic_links WHERE token = ? AND purpose = 'login' AND used_at IS NULL AND expires_at > NOW()",
            [$token]
        );
        if (!$row) {
            Response::flash('error', 'That login link is invalid or expired.');
            Response::redirect('/login.php');
        }
        $payload = json_decode($row['payload_json'] ?: '{}', true) ?: [];
        $uid = (int) ($payload['user_id'] ?? 0);
        $user = $uid ? Database::fetch('SELECT * FROM users WHERE id = ? AND is_active = 1 AND deleted_at IS NULL', [$uid]) : null;
        if (!$user) {
            Response::flash('error', 'That login link is invalid or expired.');
            Response::redirect('/login.php');
        }
        if (empty($user['email_verified_at'])) {
            Auth::beginPendingVerify($uid);
            Database::query('UPDATE magic_links SET used_at = NOW() WHERE id = ?', [$row['id']]);
            Response::flash('error', 'Verify your email before signing in.');
            Response::redirect('/verify-email/otp');
        }
        Database::query('UPDATE magic_links SET used_at = NOW() WHERE id = ?', [$row['id']]);
        Auth::clearAdminPortal();
        if (Auth::needsTwoFactor($uid) && !empty($user['totp_confirmed_at'])) {
            Auth::beginTwoFactor($uid);
            $_SESSION['pending_2fa_context'] = 'app';
            Response::redirect('/login/2fa');
        }
        Auth::login($uid, false);
        AuthAudit::record('auth.magic.consume', $uid);
        Response::flash('success', 'Signed in.');
        Response::redirect('/app');
    }

    public function showTwoFactor(): void
    {
        if (Auth::pendingTwoFactorId() === null) {
            Response::redirect('/login.php');
        }
        View::make('auth/two-factor-challenge', [
            'title' => 'Two-factor authentication',
            'action' => Url::to('/login/2fa'),
        ], 'layouts/auth');
    }

    public function showAdminTwoFactor(): void
    {
        if (Auth::pendingTwoFactorId() === null) {
            Response::redirect('/admin/login.php');
        }
        View::make('auth/two-factor-challenge', [
            'title' => 'Admin two-factor authentication',
            'action' => Url::to('/admin/login/2fa'),
        ], 'layouts/admin-auth');
    }

    public function challengeTwoFactor(): void
    {
        $this->completeTwoFactorChallenge('app');
    }

    public function challengeAdminTwoFactor(): void
    {
        $this->completeTwoFactorChallenge('admin');
    }

    private function completeTwoFactorChallenge(string $context): void
    {
        Csrf::requireValid();
        $uid = Auth::pendingTwoFactorId();
        $failTo = $context === 'admin' ? '/admin/login/2fa' : '/login/2fa';
        $loginTo = $context === 'admin' ? '/admin/login.php' : '/login.php';
        if ($uid === null) {
            Response::redirect($loginTo);
        }
        $code = trim((string) ($_POST['code'] ?? ''));
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$uid]);
        $ok = false;
        if ($user && !empty($user['totp_secret'])) {
            $secret = Crypto::decrypt((string) $user['totp_secret']);
            if ($secret && Totp::verify($secret, $code)) {
                $ok = true;
            }
        }
        if (!$ok && $user && !empty($user['totp_recovery_codes'])) {
            $codes = json_decode(Crypto::decrypt((string) $user['totp_recovery_codes']) ?: '[]', true) ?: [];
            $hash = hash('sha256', $code);
            $idx = array_search($hash, $codes, true);
            if ($idx !== false) {
                unset($codes[$idx]);
                Database::query(
                    'UPDATE users SET totp_recovery_codes = ?, updated_at = NOW() WHERE id = ?',
                    [Crypto::encrypt(json_encode(array_values($codes))), $uid]
                );
                $ok = true;
                AuthAudit::record('auth.2fa.recovery_used', $uid);
            }
        }
        if (!$ok) {
            AuthAudit::record('auth.2fa.failure', $uid, ['context' => $context]);
            Response::flash('error', 'Invalid authentication code.');
            Response::redirect($failTo);
        }
        $remember = !empty($_SESSION['pending_2fa_remember']);
        Auth::completeTwoFactor($remember);
        if ($context === 'admin') {
            if (!Gate::allows('admin.access', $uid)) {
                Auth::clearAdminPortal();
                Auth::logout();
                Response::flash('error', 'Those details do not match organiser records.');
                Response::redirect('/admin/login.php');
            }
            Auth::grantAdminPortal();
            AuthAudit::record('auth.admin_login.success', $uid, ['2fa' => true]);
            Response::redirect('/dashboard');
        }
        Auth::clearAdminPortal();
        Response::redirect('/app');
    }

    public function logout(): void
    {
        Csrf::requireValid();
        $wasAdmin = Auth::hasAdminPortal();
        Auth::logout();
        Response::redirect($wasAdmin ? '/admin/login.php' : '/');
    }

    public function logoutAll(): void
    {
        Csrf::requireValid();
        Auth::logoutAll();
        Response::flash('success', 'Signed out of all devices.');
        Response::redirect('/login.php');
    }

    /** @param array<string,mixed> $user */
    private function maybeNewDeviceNotice(array $user): void
    {
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
        $prev = Database::fetch(
            'SELECT user_agent FROM auth_sessions WHERE user_id = ? AND revoked_at IS NULL ORDER BY id DESC LIMIT 1 OFFSET 1',
            [$user['id']]
        );
        if ($prev && ($prev['user_agent'] ?? '') !== $ua) {
            AuthMailer::newSignIn((string) $user['email'], (string) $user['first_name'], $ua . ' · ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
        }
    }

    private static function isSafeAdminRedirect(string $redirect): bool
    {
        if (!str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return false;
        }
        $path = parse_url($redirect, PHP_URL_PATH) ?: $redirect;
        return $path === '/dashboard'
            || str_starts_with($path, '/dashboard/')
            || (str_starts_with($path, '/admin') && !str_starts_with($path, '/admin/login'));
    }
}
