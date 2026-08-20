<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\Auth;
use App\Auth\AuthAudit;
use App\Auth\AuthSessionStore;
use App\Auth\PasswordPolicy;
use App\Notify\AuthMailer;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\RateLimiter;
use App\Support\Response;
use App\Support\Url;
use App\Support\View;

final class PasswordResetController
{
    public function showForgot(): void
    {
        View::make('auth/forgot-password', ['title' => 'Forgot password'], 'layouts/auth');
    }

    public function sendForgot(): void
    {
        Csrf::requireValid();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        RateLimiter::attempt('reset:ip:' . $ip, 5, 600);
        RateLimiter::attempt('reset:email:' . $email, 3, 600);

        $neutral = 'If that email is registered, a reset link is on its way.';
        $user = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? Database::fetch('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL', [$email])
            : null;
        if ($user) {
            $token = bin2hex(random_bytes(32));
            Database::query(
                'INSERT INTO password_reset_tokens (email, token_hash, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE), NOW())',
                [$email, hash('sha256', $token)]
            );
            AuthMailer::resetPassword($email, Url::to('/password/reset?token=' . $token));
            AuthAudit::record('auth.password.reset_request', (int) $user['id']);
        } else {
            AuthAudit::record('auth.password.reset_request_unknown', null, ['email' => $email]);
        }
        Response::flash('success', $neutral);
        Response::redirect('/password/forgot');
    }

    public function showReset(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        $row = $this->findValidToken($token);
        if (!$row) {
            View::make('auth/reset-invalid', ['title' => 'Reset link invalid'], 'layouts/auth');
            return;
        }
        View::make('auth/reset-password', ['title' => 'Reset password', 'token' => $token], 'layouts/auth');
    }

    public function reset(): void
    {
        Csrf::requireValid();
        $token = (string) ($_POST['token'] ?? '');
        $row = $this->findValidToken($token);
        if (!$row) {
            Response::flash('error', 'That reset link is invalid or expired.');
            Response::redirect('/password/forgot');
        }
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirmation'] ?? '');
        if ($password !== $confirm) {
            Response::flash('error', 'Password confirmation does not match.');
            Response::redirect('/password/reset?token=' . urlencode($token));
        }
        $errors = PasswordPolicy::validate($password);
        if ($errors) {
            Response::flash('error', $errors[0]);
            Response::redirect('/password/reset?token=' . urlencode($token));
        }
        $user = Database::fetch('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL', [$row['email']]);
        if (!$user) {
            Response::flash('error', 'That reset link is invalid or expired.');
            Response::redirect('/password/forgot');
        }
        Database::query(
            'UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $user['id']]
        );
        Database::query('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?', [$row['id']]);
        try {
            AuthSessionStore::revokeAll((int) $user['id'], false);
        } catch (\Throwable $e) {
        }
        AuthAudit::record('auth.password.reset_complete', (int) $user['id']);
        AuthMailer::passwordChanged((string) $user['email'], (string) $user['first_name']);
        Response::flash('success', 'Password updated. Sign in with your new password.');
        Response::redirect('/login.php');
    }

    private function findValidToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        return Database::fetch(
            'SELECT * FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
            [hash('sha256', $token)]
        );
    }
}
