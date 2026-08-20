<?php
declare(strict_types=1);

namespace App\Domain;

use App\Auth\Auth;
use App\Auth\AuthAudit;
use App\Auth\EmailVerification;
use App\Auth\PasswordPolicy;
use App\Notify\AuthMailer;
use App\Notify\Notifier;
use App\Rbac\Gate;
use App\Support\Database;
use App\Support\Env;
use App\Support\Url;

final class RegistrationService
{
    public const STEPS = ['organisation', 'account', 'capabilities'];

    /** Normalize 3-step registration payload (contacts → names, offer → solution fields). */
    public static function normalizeRegistrationPayload(array $data): array
    {
        $persona = (string) ($data['persona'] ?? 'innovator');
        if (in_array($persona, ['company', 'government', 'corporate'], true)) {
            $persona = 'corporate';
        } else {
            $persona = 'innovator';
        }
        $data['persona'] = $persona;

        $contacts = $data['contacts'] ?? [];
        if (!is_array($contacts)) {
            $contacts = [];
        }
        $normalizedContacts = [];
        foreach ($contacts as $c) {
            if (!is_array($c)) {
                continue;
            }
            $name = trim((string) ($c['name'] ?? ''));
            $email = strtolower(trim((string) ($c['email'] ?? '')));
            $phone = trim((string) ($c['phone'] ?? ''));
            if ($name === '' && $email === '' && $phone === '') {
                continue;
            }
            $normalizedContacts[] = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'whatsapp' => !empty($c['whatsapp']) ? 1 : 0,
            ];
        }
        $data['contacts'] = $normalizedContacts;

        $primary = $normalizedContacts[0] ?? null;
        if ($primary) {
            $parts = preg_split('/\s+/', trim($primary['name']), 2) ?: [];
            if (empty($data['first_name'])) {
                $data['first_name'] = $parts[0] ?? 'Member';
            }
            if (empty($data['last_name'])) {
                $data['last_name'] = $parts[1] ?? ($parts[0] ?? 'User');
            }
            if (empty($data['phone']) && $primary['phone'] !== '') {
                $data['phone'] = $primary['phone'];
            }
        }

        $offer = trim((string) ($data['offer_text'] ?? ''));
        if ($offer !== '') {
            if ($persona === 'innovator') {
                $data['problem_solved'] = $offer;
                if (empty($data['solution_name'])) {
                    $org = trim((string) ($data['organization'] ?? 'Solution'));
                    $data['solution_name'] = $org . ' offering';
                }
            } else {
                $data['intent'] = $offer;
                if (empty($data['challenge_problem'])) {
                    $data['challenge_problem'] = $offer;
                }
            }
        }

        $focus = $data['focus_areas'] ?? [];
        if (!is_array($focus)) {
            $focus = [];
        }
        $data['focus_areas'] = array_values(array_filter(array_map('strval', $focus)));
        if (!empty($data['focus_areas'])) {
            $data['sector'] = $data['focus_areas'][0];
            $data['industry'] = $data['focus_areas'][0];
        }

        $orgTypeLabel = trim((string) ($data['organization_type'] ?? ''));
        if ($orgTypeLabel !== '') {
            $data['company_size'] = $data['team_size'] ?? ($data['company_size'] ?? null);
        }

        return $data;
    }

    public static function saveDraft(?string $email, ?string $persona, array $data, bool $emailLink = false, ?int $userId = null, string $step = 'organisation'): string
    {
        $existingToken = (string) ($data['_draft_token'] ?? '');
        unset($data['_draft_token'], $data['_csrf'], $data['password'], $data['password_confirmation']);

        if ($existingToken !== '') {
            $row = Database::fetch('SELECT id FROM registration_drafts WHERE token = ? AND expires_at > NOW()', [$existingToken]);
            if ($row) {
                Database::query(
                    'UPDATE registration_drafts SET email = ?, persona = ?, data_json = ?, current_step = ?, user_id = COALESCE(?, user_id), updated_at = NOW() WHERE id = ?',
                    [$email, $persona, json_encode($data), $step, $userId, $row['id']]
                );
                $token = $existingToken;
            } else {
                $token = self::insertDraft($email, $persona, $data, $userId, $step);
            }
        } else {
            $token = self::insertDraft($email, $persona, $data, $userId, $step);
        }

        if ($emailLink && $email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $url = Url::to('/register?draft=' . $token);
            AuthMailer::resumeRegistration($email, $url);
        }
        return $token;
    }

    private static function insertDraft(?string $email, ?string $persona, array $data, ?int $userId, string $step): string
    {
        $token = bin2hex(random_bytes(16));
        Database::query(
            'INSERT INTO registration_drafts (token, user_id, email, persona, current_step, data_json, expires_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 14 DAY), NOW(), NOW())',
            [$token, $userId, $email, $persona, $step, json_encode($data)]
        );
        return $token;
    }

    public static function loadDraft(string $token): ?array
    {
        $row = Database::fetch('SELECT * FROM registration_drafts WHERE token = ? AND expires_at > NOW()', [$token]);
        if (!$row) {
            return null;
        }
        $data = json_decode($row['data_json'] ?: '{}', true) ?: [];
        $data['_draft_token'] = $token;
        $data['persona'] = $row['persona'] ?? ($data['persona'] ?? null);
        $data['_current_step'] = $row['current_step'] ?? 'type';
        $data['_user_id'] = $row['user_id'] ?? null;
        return $data;
    }

    /** Create unverified account at Account step. */
    public static function createAccount(array $slots): array
    {
        $email = strtolower(trim((string) ($slots['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Enter a valid work email.');
        }
        $existing = Database::fetch('SELECT id FROM users WHERE email = ?', [$email]);
        if ($existing) {
            throw new \InvalidArgumentException('An account with this email already exists. Please log in.');
        }

        $password = (string) ($slots['password'] ?? '');
        $confirm = (string) ($slots['password_confirmation'] ?? '');
        if ($password !== $confirm) {
            throw new \InvalidArgumentException('Password confirmation does not match.');
        }
        $policyErrors = PasswordPolicy::validate($password);
        if ($policyErrors) {
            throw new \InvalidArgumentException($policyErrors[0]);
        }
        if (empty($slots['consent_privacy']) || empty($slots['consent_conduct'])) {
            throw new \InvalidArgumentException('Please accept the privacy notice and code of conduct.');
        }

        $first = self::cleanName((string) ($slots['first_name'] ?? ''));
        $last = self::cleanName((string) ($slots['last_name'] ?? ''));
        if ($first === '' || $last === '') {
            throw new \InvalidArgumentException('Enter your full name.');
        }

        $persona = (string) ($slots['persona'] ?? 'innovator');
        if (!in_array($persona, ['innovator', 'corporate'], true)) {
            $persona = $persona === 'government' ? 'corporate' : 'innovator';
        }
        $qr = bin2hex(random_bytes(16));

        Database::query(
            "INSERT INTO users (email, password, first_name, last_name, phone, country, city, title, qr_badge_token, email_verified_at, is_active, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, NULL, 1, 'unverified', NOW(), NOW())",
            [
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                $first,
                $last,
                self::normalizePhone((string) ($slots['phone'] ?? '')),
                $slots['country'] ?? 'Zimbabwe',
                $slots['city'] ?? null,
                $qr,
            ]
        );
        $userId = (int) Database::lastId();

        $roleMap = [
            'attendee' => 'attendee', 'corporate' => 'corporate', 'innovator' => 'innovator',
            'university' => 'university', 'innovation_hub' => 'innovation_hub', 'investor' => 'investor',
            'exhibitor' => 'exhibitor', 'government' => 'government', 'student' => 'student',
        ];
        Gate::assignRole($userId, $roleMap[$persona] ?? 'innovator');

        Database::query(
            'INSERT INTO consents (user_id, consent_type, granted, created_at) VALUES (?, \'privacy\', 1, NOW()), (?, \'code_of_conduct\', 1, NOW())',
            [$userId, $userId]
        );
        if (!empty($slots['consent_marketing'])) {
            Database::query(
                'INSERT INTO consents (user_id, consent_type, granted, created_at) VALUES (?, \'marketing\', 1, NOW())',
                [$userId]
            );
        }
        Database::query(
            'INSERT INTO notification_preferences (user_id, email_enabled, sms_enabled, whatsapp_enabled, in_app_enabled, updated_at)
             VALUES (?, 1, 1, 0, 1, NOW())',
            [$userId]
        );

        $eventId = EventContext::id();
        Database::query(
            "INSERT INTO participation_profiles (user_id, event_id, persona, profile_json, registration_fee, payment_status, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, 0, 'not_required', 'draft', NOW(), NOW())",
            [$userId, $eventId, $persona, json_encode(['persona' => $persona])]
        );

        $draftToken = self::saveDraft($email, $persona, array_merge($slots, ['account_created' => 1]), false, $userId, 'capabilities');

        // Do not auto-login. User must verify email OTP, then sign in.
        Auth::beginPendingVerify($userId);
        EmailVerification::send($userId);
        AuthAudit::record('auth.register', $userId, ['persona' => $persona]);

        return [
            'user_id' => $userId,
            'draft_token' => $draftToken,
            'qr' => $qr,
            'verification_mode' => EmailVerification::mode(),
        ];
    }

    /** Finalize profile after remaining steps. */
    public static function finalizeProfile(int $userId, array $slots): array
    {
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            throw new \InvalidArgumentException('Account not found.');
        }
        $persona = (string) ($slots['persona'] ?? 'innovator');
        if (!in_array($persona, ['innovator', 'corporate'], true)) {
            $persona = in_array($persona, ['government', 'company'], true) ? 'corporate' : 'innovator';
        }
        $orgName = trim((string) ($slots['organization'] ?? $slots['institution'] ?? $slots['fund_name'] ?? ''));
        if (strlen($orgName) < 2) {
            throw new \InvalidArgumentException('Organisation name is required.');
        }

        $avatarPath = self::storeAvatar($slots['_avatar_tmp'] ?? null, $slots['_avatar_name'] ?? null)
            ?: ($user['avatar_path'] ?? null);
        $deckPath = self::storeUpload($slots['_deck_tmp'] ?? null, $slots['_deck_name'] ?? null, ['pdf', 'ppt', 'pptx'], 10 * 1024 * 1024, 'decks');
        $logoPath = self::storeUpload($slots['_logo_tmp'] ?? null, $slots['_logo_name'] ?? null, ['png', 'jpg', 'jpeg', 'svg', 'webp'], 1572864, 'logos');
        $brochurePath = self::storeUpload($slots['_brochure_tmp'] ?? null, $slots['_brochure_name'] ?? null, ['pdf'], 10 * 1024 * 1024, 'brochures');

        if ($deckPath) {
            $slots['pitch_deck_path'] = $deckPath;
        }
        if ($logoPath) {
            $slots['logo_path'] = $logoPath;
        }
        if ($brochurePath) {
            $slots['brochure_path'] = $brochurePath;
        }

        Database::query(
            'UPDATE users SET title = ?, country = ?, city = ?, linkedin_url = ?, website_url = ?, avatar_path = ?, dietary_needs = ?, accessibility_needs = ?, phone = COALESCE(?, phone), updated_at = NOW() WHERE id = ?',
            [
                $slots['title'] ?? null,
                $slots['country'] ?? 'Zimbabwe',
                $slots['city'] ?? null,
                self::validUrl($slots['linkedin_url'] ?? null),
                self::validUrl($slots['website_url'] ?? null),
                $avatarPath,
                $slots['dietary_needs'] ?? null,
                $slots['accessibility_needs'] ?? null,
                self::normalizePhone((string) ($slots['phone'] ?? '')),
                $userId,
            ]
        );

        $orgId = self::ensureOrg($orgName, self::orgTypeFromLabel((string) ($slots['organization_type'] ?? ''), $persona), $logoPath);
        $link = Database::fetch('SELECT id FROM organization_user WHERE user_id = ? AND organization_id = ?', [$userId, $orgId]);
        if (!$link) {
            Database::query(
                'INSERT INTO organization_user (organization_id, user_id, org_role, is_primary, created_at) VALUES (?, ?, \'member\', 1, NOW())',
                [$orgId, $userId]
            );
        }

        $eventId = EventContext::id();
        $event = EventContext::current();
        $fee = (float) ($event['registration_fee'] ?? 0);
        if ($fee <= 0) {
            $fee = (float) (Env::get('REGISTRATION_FEE', '0') ?: 0);
        }
        $paymentsOn = (new \App\Payments\PayNowGateway())->isEnabled() || $fee > 0;
        $paymentStatus = $fee <= 0 ? 'not_required' : ($paymentsOn ? 'pending' : 'waived');

        $profile = Database::fetch(
            'SELECT id FROM participation_profiles WHERE user_id = ? AND event_id = ? ORDER BY id DESC LIMIT 1',
            [$userId, $eventId]
        );
        if ($profile) {
            Database::query(
                "UPDATE participation_profiles SET persona = ?, profile_json = ?, registration_fee = ?, payment_status = ?, status = 'submitted', updated_at = NOW() WHERE id = ?",
                [$persona, json_encode($slots), $fee, $paymentStatus, $profile['id']]
            );
            $profileId = (int) $profile['id'];
        } else {
            Database::query(
                "INSERT INTO participation_profiles (user_id, event_id, persona, profile_json, registration_fee, payment_status, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'submitted', NOW(), NOW())",
                [$userId, $eventId, $persona, json_encode($slots), $fee, $paymentStatus]
            );
            $profileId = (int) Database::lastId();
        }

        if ($fee > 0 && $paymentStatus === 'pending') {
            $charge = (new \App\Payments\PayNowGateway())->charge([
                'user_id' => $userId,
                'participation_profile_id' => $profileId,
                'amount' => $fee,
                'email' => $user['email'],
                'description' => 'ZBIF InnovaMatch registration',
            ]);
            if (($charge['status'] ?? '') === 'skipped') {
                (new \App\Payments\PayNowGateway())->markWaived($profileId, $userId, $fee);
            }
        }

        if ($persona === 'corporate' && !empty($slots['challenge_title'])) {
            self::createInlineChallenge($eventId, $orgId, $userId, $slots);
        }
        if ($persona === 'innovator' && !empty($slots['solution_name'])) {
            self::createInlineSolution($eventId, $orgId, $userId, $slots);
            if (!empty($slots['request_sponsorship'])) {
                Database::query(
                    'INSERT INTO sponsorship_applications (user_id, organization_id, pitch, status, created_at, updated_at)
                     VALUES (?, ?, ?, \'submitted\', NOW(), NOW())',
                    [$userId, $orgId, 'Requested at registration for ' . ($slots['solution_name'] ?? 'solution')]
                );
            }
        }

        self::saveDraft((string) $user['email'], $persona, $slots, false, $userId, 'done');
        AuditLog::record('registration.completed', 'user', $userId, ['persona' => $persona], $userId);
        Notifier::inApp($userId, 'Welcome to ZBIF', 'Your registration is submitted. Verify your email to unlock marketplace actions.', '/app');

        return [
            'user_id' => $userId,
            'qr' => $user['qr_badge_token'],
            'payment_status' => $paymentStatus,
            'registration_fee' => $fee,
            'profile_id' => $profileId,
            'verified' => !empty($user['email_verified_at']),
        ];
    }

    /** Legacy one-shot path (Nova chat / older clients). */
    public static function completeFromSlots(array $slots): array
    {
        if (empty($slots['password'])) {
            $slots['password'] = bin2hex(random_bytes(5)) . 'Aa1';
            $slots['password_confirmation'] = $slots['password'];
        }
        $slots['consent_privacy'] = $slots['consent_privacy'] ?? $slots['consent_data'] ?? 1;
        $slots['consent_conduct'] = $slots['consent_conduct'] ?? 1;
        $created = self::createAccount($slots);
        return array_merge($created, self::finalizeProfile((int) $created['user_id'], $slots));
    }

    public static function verifyEmail(string $token): bool
    {
        $result = EmailVerification::verifyLinkToken($token);
        return !empty($result['ok']) && in_array($result['reason'] ?? '', ['verified', 'already'], true);
    }

    public static function sendOtp(string $phone): void
    {
        $code = (string) random_int(100000, 999999);
        Database::query(
            "INSERT INTO otp_codes (phone, purpose, code_hash, expires_at, created_at) VALUES (?, 'phone', ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())",
            [$phone, password_hash($code, PASSWORD_DEFAULT)]
        );
        Notifier::sendSms($phone, 'ZBIF OTP: ' . $code . ' (valid 10 minutes)');
    }

    private static function cleanName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || strlen($name) > 80) {
            return '';
        }
        if (!preg_match("/^[\\p{L} .'\\-]{2,80}$/u", $name)) {
            return '';
        }
        return $name;
    }

    private static function normalizePhone(string $phone): ?string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }
        $digits = preg_replace('/[^\d+]/', '', $phone) ?? '';
        if (preg_match('/^0\d{9}$/', $digits)) {
            return '+263' . substr($digits, 1);
        }
        if (preg_match('/^\+?\d{8,15}$/', $digits)) {
            return str_starts_with($digits, '+') ? $digits : '+' . $digits;
        }
        return $phone;
    }

    private static function validUrl(mixed $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    private static function storeAvatar(?string $tmp, ?string $name): ?string
    {
        return self::storeUpload($tmp, $name, ['jpg', 'jpeg', 'png', 'webp', 'gif'], 2 * 1024 * 1024, 'avatars');
    }

    /** @param list<string> $exts */
    private static function storeUpload(?string $tmp, ?string $name, array $exts, int $maxBytes, string $folder): ?string
    {
        if (!$tmp || !is_uploaded_file($tmp)) {
            return null;
        }
        if (filesize($tmp) > $maxBytes) {
            throw new \InvalidArgumentException('Upload exceeds the maximum file size.');
        }
        $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION) ?: '');
        if (!in_array($ext, $exts, true)) {
            throw new \InvalidArgumentException('Upload type is not allowed.');
        }
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '', (string) $name) ?: 'file';
        $dir = ZBIF_ROOT . '/storage/uploads/' . $folder;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($tmp, $dir . '/' . $filename)) {
            return null;
        }
        return $folder . '/' . $filename;
    }

    private static function orgType(string $persona): string
    {
        return match ($persona) {
            'corporate' => 'corporate',
            'innovator' => 'startup',
            'university', 'student' => 'university',
            'innovation_hub' => 'hub',
            'investor' => 'investor',
            'government' => 'government',
            'exhibitor' => 'exhibitor',
            default => 'other',
        };
    }

    private static function orgTypeFromLabel(string $label, string $persona): string
    {
        $label = strtolower(trim($label));
        return match (true) {
            str_contains($label, 'startup') => 'startup',
            str_contains($label, 'university') => 'university',
            str_contains($label, 'hub') => 'hub',
            str_contains($label, 'government') => 'government',
            str_contains($label, 'ngo') || str_contains($label, 'cso') => 'other',
            str_contains($label, 'corporate') || str_contains($label, 'parastatal') => 'corporate',
            str_contains($label, 'sme') => 'startup',
            default => self::orgType($persona),
        };
    }

    private static function ensureOrg(string $name, string $type, ?string $logoPath = null): int
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?: 'org');
        $slug = trim($slug, '-') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        Database::query(
            'INSERT INTO organizations (name, slug, type, logo_path, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
            [$name, $slug, $type, $logoPath]
        );
        return (int) Database::lastId();
    }

    /** @param array<string, mixed> $slots */
    private static function createInlineChallenge(int $eventId, int $orgId, int $userId, array $slots): void
    {
        $title = (string) $slots['challenge_title'];
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title) ?: 'challenge') . '-' . $userId;
        Database::query(
            'INSERT INTO challenges (event_id, owner_org_id, created_by, title, slug, problem_statement, sector, category, engagement_type, visibility, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'partnership\', \'public\', \'submitted\', NOW(), NOW())',
            [
                $eventId, $orgId, $userId, $title, $slug,
                (string) ($slots['challenge_problem'] ?? $title),
                (string) ($slots['industry'] ?? 'General'),
                (string) ($slots['category'] ?? $slots['industry'] ?? 'Manufacturing'),
            ]
        );
        $cid = (int) Database::lastId();
        Database::query(
            'INSERT INTO challenge_state_events (challenge_id, from_status, to_status, actor_id, notes, created_at)
             VALUES (?, NULL, \'submitted\', ?, \'Created at registration\', NOW())',
            [$cid, $userId]
        );
    }

    /** @param array<string, mixed> $slots */
    private static function createInlineSolution(int $eventId, int $orgId, int $userId, array $slots): void
    {
        $name = (string) $slots['solution_name'];
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?: 'solution') . '-' . $userId;
        $stage = (string) ($slots['stage'] ?? 'prototype');
        if (!in_array($stage, ['idea', 'prototype', 'pilot', 'market_ready', 'scaling'], true)) {
            $stage = 'prototype';
        }
        Database::query(
            'INSERT INTO solutions (event_id, owner_org_id, created_by, name, slug, sector, stage, description, problem_solved, traction, is_published, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())',
            [
                $eventId, $orgId, $userId, $name, $slug,
                (string) ($slots['sector'] ?? 'General'),
                $stage,
                (string) ($slots['problem_solved'] ?? $name),
                (string) ($slots['problem_solved'] ?? ''),
                (string) ($slots['traction'] ?? self::validUrl($slots['demo_url'] ?? null) ?? ''),
            ]
        );
    }
}
