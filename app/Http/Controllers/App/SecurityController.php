<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Auth\AuthAudit;
use App\Auth\Totp;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Crypto;
use App\Support\Database;
use App\Support\Env;
use App\Support\Response;
use App\Support\View;

final class SecurityController
{
    public function showTwoFactor(): void
    {
        $user = Auth::requireLogin();
        View::make('app/security-2fa', [
            'title' => 'Two-factor authentication',
            'user' => $user,
            'enabled' => !empty($user['totp_confirmed_at']),
            'required' => $this->roleRequires2fa((int) $user['id']),
        ]);
    }

    public function enable(): void
    {
        Csrf::requireValid();
        $user = Auth::requireLogin();
        $secret = Totp::generateSecret();
        Database::query(
            'UPDATE users SET totp_secret = ?, totp_confirmed_at = NULL, updated_at = NOW() WHERE id = ?',
            [Crypto::encrypt($secret), $user['id']]
        );
        $_SESSION['pending_totp_secret'] = $secret;
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }
        $_SESSION['pending_recovery_codes'] = $codes;
        AuthAudit::record('auth.2fa.enable_start', (int) $user['id']);
        View::make('app/security-2fa-setup', [
            'title' => 'Set up 2FA',
            'secret' => $secret,
            'uri' => Totp::provisioningUri($secret, (string) $user['email']),
            'recovery' => $codes,
        ]);
    }

    public function confirm(): void
    {
        Csrf::requireValid();
        $user = Auth::requireLogin();
        $secret = $_SESSION['pending_totp_secret'] ?? null;
        $codes = $_SESSION['pending_recovery_codes'] ?? [];
        if (!$secret) {
            Response::flash('error', 'Start 2FA setup again.');
            Response::redirect('/app/security/2fa');
        }
        $code = (string) ($_POST['code'] ?? '');
        if (!Totp::verify($secret, $code)) {
            Response::flash('error', 'Invalid code. Try again.');
            Response::redirect('/app/security/2fa');
        }
        $hashed = array_map(static fn ($c) => hash('sha256', $c), $codes);
        Database::query(
            'UPDATE users SET totp_secret = ?, totp_confirmed_at = NOW(), totp_recovery_codes = ?, updated_at = NOW() WHERE id = ?',
            [Crypto::encrypt($secret), Crypto::encrypt(json_encode($hashed)), $user['id']]
        );
        unset($_SESSION['pending_totp_secret'], $_SESSION['pending_recovery_codes']);
        AuthAudit::record('auth.2fa.confirmed', (int) $user['id']);
        Response::flash('success', 'Two-factor authentication is enabled.');
        Response::redirect('/app/security/2fa');
    }

    public function disable(): void
    {
        Csrf::requireValid();
        $user = Auth::requireLogin();
        if ($this->roleRequires2fa((int) $user['id'])) {
            Response::flash('error', 'Two-factor authentication is required for your role.');
            Response::redirect('/app/security/2fa');
        }
        Database::query(
            'UPDATE users SET totp_secret = NULL, totp_confirmed_at = NULL, totp_recovery_codes = NULL, updated_at = NOW() WHERE id = ?',
            [$user['id']]
        );
        AuthAudit::record('auth.2fa.disabled', (int) $user['id']);
        Response::flash('success', 'Two-factor authentication disabled.');
        Response::redirect('/app/security/2fa');
    }

    private function roleRequires2fa(int $userId): bool
    {
        $required = array_filter(array_map('trim', explode(',', (string) Env::get(
            'AUTH_2FA_REQUIRED_ROLES',
            'super_admin,organizer,technical_committee'
        ))));
        foreach (Gate::rolesFor($userId) as $role) {
            if (in_array($role, $required, true)) {
                return true;
            }
        }
        return false;
    }
}
