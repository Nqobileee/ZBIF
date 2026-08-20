<?php
declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\Auth;
use App\Auth\EmailVerification;
use App\Domain\RegistrationService;
use App\Support\Csrf;
use App\Support\RateLimiter;
use App\Support\Response;
use App\Support\View;

final class RegisterController
{
    public function show(): void
    {
        $draft = null;
        $step = 0;
        if (!empty($_GET['draft'])) {
            $draft = RegistrationService::loadDraft((string) $_GET['draft']);
            if ($draft) {
                $map = ['organisation' => 0, 'organization' => 0, 'type' => 0, 'account' => 1, 'capabilities' => 2, 'profile' => 1, 'details' => 2, 'done' => 2];
                $step = $map[$draft['_current_step'] ?? 'organisation'] ?? 0;
                if (!empty($draft['_user_id']) && !Auth::check()) {
                    Response::flash('success', 'Sign in to continue your registration.');
                    Response::redirect('/login.php?redirect=' . urlencode('/register?draft=' . $_GET['draft']));
                }
                if (Auth::check() && !empty($draft['account_created'])) {
                    $step = max($step, 2);
                }
            }
        } elseif (Auth::check()) {
            Response::flash('success', 'You are already signed in. Continue in your dashboard, or sign out to register a new account.');
            Response::redirect('/app');
        }
        $persona = $_GET['persona'] ?? ($draft['persona'] ?? 'innovator');
        if ($persona === 'government' || $persona === 'company') {
            $persona = 'corporate';
        }
        if (!in_array($persona, ['innovator', 'corporate'], true)) {
            $persona = 'innovator';
        }
        View::make('auth/register', [
            'title' => 'Register',
            'metaDescription' => 'Register for ZBIF as an innovator or as a company, organisation, or government body.',
            'draft' => $draft,
            'step' => $step,
            'persona' => $persona,
        ], 'layouts/auth');
    }

    public function chatPage(): void
    {
        Response::redirect('/register?assist=1');
    }

    public function saveDraft(): void
    {
        Csrf::requireValid();
        $data = $_POST;
        unset($data['_csrf'], $data['_wizard_action']);
        $emailLink = !empty($data['email_magic_link']);
        unset($data['email_magic_link']);
        $step = (string) ($data['current_step'] ?? 'profile');
        unset($data['current_step']);
        $token = RegistrationService::saveDraft(
            $data['email'] ?? null,
            $data['persona'] ?? null,
            $data,
            $emailLink,
            Auth::id(),
            $step
        );
        Response::json([
            'ok' => true,
            'draft_token' => $token,
            'resume_url' => '/register?draft=' . $token,
            'message' => $emailLink ? 'Draft saved. Check your email for the resume link.' : 'Draft saved.',
        ]);
    }

    public function createAccount(): void
    {
        Csrf::requireValid();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        if (!RateLimiter::attempt('register:' . $ip, 10, 600)) {
            Response::json(['error' => ['code' => 'rate_limited', 'message' => 'Too many attempts. Please wait.', 'details' => []]], 429);
        }
        $data = $_POST;
        unset($data['_csrf'], $data['_wizard_action'], $data['persona_radio'], $data['persona_toggle']);
        if (!empty($_POST['persona_toggle'])) {
            $data['persona'] = $_POST['persona_toggle'];
        }
        $data = RegistrationService::normalizeRegistrationPayload($data);
        try {
            $result = RegistrationService::createAccount($data);
            Response::json([
                'ok' => true,
                'draft_token' => $result['draft_token'],
                'verification_mode' => $result['verification_mode'],
                'message' => 'Account created. Enter the code we emailed you to verify.',
            ]);
        } catch (\Throwable $e) {
            Response::json(['error' => ['code' => 'validation_error', 'message' => $e->getMessage(), 'details' => []]], 422);
        }
    }

    public function submit(): void
    {
        Csrf::requireValid();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        if (!RateLimiter::attempt('register:' . $ip, 10, 600)) {
            Response::flash('error', 'Too many registration attempts.');
            Response::redirect('/register');
        }
        $data = $_POST;
        unset($data['_csrf'], $data['_wizard_action'], $data['persona_toggle']);
        if (!empty($_POST['persona_toggle'])) {
            $data['persona'] = $_POST['persona_toggle'];
        }
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $data['_avatar_tmp'] = $_FILES['avatar']['tmp_name'];
            $data['_avatar_name'] = $_FILES['avatar']['name'] ?? 'avatar.jpg';
        }
        if (!empty($_FILES['pitch_deck']['tmp_name'])) {
            $data['_deck_tmp'] = $_FILES['pitch_deck']['tmp_name'];
            $data['_deck_name'] = $_FILES['pitch_deck']['name'] ?? 'deck.pdf';
        }
        if (!empty($_FILES['logo']['tmp_name'])) {
            $data['_logo_tmp'] = $_FILES['logo']['tmp_name'];
            $data['_logo_name'] = $_FILES['logo']['name'] ?? 'logo.png';
        }
        if (!empty($_FILES['brochure']['tmp_name'])) {
            $data['_brochure_tmp'] = $_FILES['brochure']['tmp_name'];
            $data['_brochure_name'] = $_FILES['brochure']['name'] ?? 'brochure.pdf';
        }

        $data = RegistrationService::normalizeRegistrationPayload($data);

        try {
            if (Auth::check()) {
                $result = RegistrationService::finalizeProfile((int) Auth::id(), $data);
            } else {
                if (empty($data['consent_privacy']) && !empty($data['consent_data'])) {
                    $data['consent_privacy'] = 1;
                    $data['consent_conduct'] = 1;
                }
                $result = RegistrationService::completeFromSlots($data);
            }

            $userId = (int) ($result['user_id'] ?? 0);
            if ($userId > 0 && empty($result['verified'])) {
                Auth::beginPendingVerify($userId);
                if (Auth::check() && Auth::id() === $userId) {
                    unset($_SESSION['user_id']);
                }
                Response::flash('success', 'Account created. Enter the code we sent to your email.');
                Response::redirect('/verify-email/otp');
            }

            Response::flash('success', 'Registration complete. Sign in to continue.');
            Response::redirect('/login.php');
        } catch (\Throwable $e) {
            Response::flash('error', $e->getMessage());
            Response::redirect('/register');
        }
    }

    public function done(): void
    {
        if (Auth::pendingVerifyId() !== null) {
            Response::redirect('/verify-email/otp');
        }
        if (Auth::check()) {
            Response::redirect('/app');
        }
        Response::redirect('/login.php');
    }

    public function verifyEmail(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        if ($token === '') {
            View::make('auth/verify-result', [
                'title' => 'Verify email',
                'state' => 'invalid',
            ], 'layouts/auth');
            return;
        }
        $result = EmailVerification::verifyLinkToken($token);
        if (!empty($result['ok']) && ($result['reason'] ?? '') === 'already') {
            View::make('auth/verify-result', ['title' => 'Already verified', 'state' => 'already'], 'layouts/auth');
            return;
        }
        if (!empty($result['ok'])) {
            Auth::clearPendingVerify();
            if (Auth::check()) {
                unset($_SESSION['user_id']);
            }
            View::make('auth/verify-result', ['title' => 'Email verified', 'state' => 'verified'], 'layouts/auth');
            return;
        }
        View::make('auth/verify-result', [
            'title' => 'Link expired',
            'state' => $result['reason'] ?? 'expired',
            'user_id' => $result['user_id'] ?? null,
        ], 'layouts/auth');
    }

    public function showOtp(): void
    {
        $user = Auth::requirePendingVerifyUser();
        View::make('auth/verify-otp', [
            'title' => 'Verify email',
            'email' => (string) $user['email'],
        ], 'layouts/auth');
    }

    public function submitOtp(): void
    {
        Csrf::requireValid();
        $user = Auth::requirePendingVerifyUser();
        $code = preg_replace('/\D/', '', (string) ($_POST['code'] ?? '')) ?? '';
        $result = EmailVerification::verifyOtpCode((int) $user['id'], $code);
        if (!empty($result['ok'])) {
            Auth::clearPendingVerify();
            if (Auth::check()) {
                unset($_SESSION['user_id']);
            }
            Response::flash('success', 'Email verified. Sign in with your email and password.');
            Response::redirect('/login.php');
        }
        $msg = match ($result['reason'] ?? '') {
            'locked' => 'Too many attempts. Please wait and try again.',
            default => 'That code is invalid or expired.',
        };
        Response::flash('error', $msg);
        Response::redirect('/verify-email/otp');
    }

    public function resend(): void
    {
        Csrf::requireValid();
        $user = Auth::requirePendingVerifyUser();
        try {
            if (!EmailVerification::resend((int) $user['id'])) {
                Response::flash('error', 'Please wait before requesting another code.');
                Response::redirect('/verify-email/otp');
            }
            Response::flash('success', 'A new code was sent to your email.');
        } catch (\Throwable $e) {
            Response::flash('error', $e->getMessage());
        }
        Response::redirect('/verify-email/otp');
    }
}
