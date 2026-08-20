# ZBIF Auth

Account registration, login, verification, and session security for InnovaMatch.

## Stack mapping

The product brief assumed Laravel Sanctum + React. This codebase uses **raw PHP + MySQL session cookies**:

| Brief | ZBIF implementation |
|---|---|
| Sanctum SPA cookies | PHP `zbif_session` cookie (httpOnly, SameSite=Lax, Secure in production) |
| Redis rate limits | MySQL `rate_limits` via `App\Support\RateLimiter` |
| Redis OTP | MySQL `otp_codes` with hashed codes |
| React wizard | Server-rendered `views/auth/*` + `public/assets/js/auth.js` |
| Signed URLs | Opaque tokens in `magic_links` / `password_reset_tokens` |

## Login portals

| Audience | URL | Lands on |
|---|---|---|
| Innovators / companies | `/login.php` | `/app` (never grants admin portal) |
| Organisers / super admin | `/admin/login.php` | `/admin` (requires `admin.access` + sets admin portal session) |

`/admin/*` requires an active **admin portal** session from `/admin/login.php`. Signing in at `/login.php` as a privileged user does **not** unlock admin. There is no public-site link to the organiser login.

Run all migrations: `php database/migrate_all.php`

## Config (`.env`)

- `AUTH_VERIFICATION_MODE=otp` (default; use `link` for magic-link emails)
- `AUTH_PASSWORD_BREACH_CHECK=false` (HIBP k-anonymity when true; fail-open)
- `AUTH_REMEMBER_DAYS=30`
- `AUTH_SESSION_IDLE_MINUTES=120`
- `AUTH_2FA_REQUIRED_ROLES=super_admin,organizer,technical_committee`
- `AUTH_SOCIAL_ENABLED=false` // TODO(review)
- `AUTH_APP_KEY=` used to encrypt TOTP secrets and recovery codes

Run: `php database/migrate_auth.php`

## Registration flow

1. Choose persona (radio cards)
2. Organisation + account + capabilities (single submit)
3. Account created as `status=unverified` — **not** signed in
4. Six-digit OTP emailed → `/verify-email/otp`
5. On success → `/login.php` (sign in with registered email and password)

Drafts: `registration_drafts` (+ `user_id`, `current_step`). Resume: `/register?draft={token}`.

## Email verification

- **OTP (default):** six-digit hashed code, 10 minutes, 5 attempts, resend 60s cooldown / 5 per hour
- **Link:** 60-minute single-use `magic_links.purpose=email_verify` (set `AUTH_VERIFICATION_MODE=link`)
- Unverified users cannot sign in or open `/app`
- `Auth::requireVerified()` remains a second gate for marketplace actions

## Login

- Password + remember-me
- Magic link: `POST /login/magic`, consume `GET /login/magic?token=`
- Enumeration-safe errors and reset/magic responses
- 2FA challenge when `totp_confirmed_at` is set

## Password reset

- `GET/POST /password/forgot` and `/password/reset`
- On success: revoke other sessions, notify via email

## 2FA

- TOTP under `/app/security/2fa`
- Recovery codes (hashed)
- Required roles listed in `AUTH_2FA_REQUIRED_ROLES`

## Audit events

Logged via `App\Auth\AuthAudit` into `audit_logs`: register, verify, login success/failure, magic, password reset, logout, 2FA, session revoke.

## Key classes

- `App\Auth\Auth`, `PasswordPolicy`, `EmailVerification`, `AuthSessionStore`, `Totp`
- `App\Notify\AuthMailer`
- `App\Domain\RegistrationService`
- Controllers under `App\Http\Controllers\Auth\` and `App\Http\Controllers\App\SecurityController`
