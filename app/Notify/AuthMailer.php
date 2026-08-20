<?php
declare(strict_types=1);

namespace App\Notify;

use App\Support\Url;

/** Branded auth transactional emails. Inbox-friendly multipart content. No em-dashes. */
final class AuthMailer
{
    public static function verifyLink(string $email, string $firstName, string $url): void
    {
        $name = self::e($firstName);
        $safeUrl = self::e($url);
        self::send(
            $email,
            'Verify your ZBIF email address',
            self::wrap(
                'Verify your email',
                '<p style="margin:0 0 14px;line-height:1.55">Hi ' . $name . ',</p>'
                . '<p style="margin:0 0 14px;line-height:1.55">Confirm your email to unlock challenges, solutions, Deal Rooms, and meetings on the Zimbabwe Business Innovation Forum.</p>',
                $url,
                'Verify my email'
            ),
            "Hi {$firstName},\n\nConfirm your email for ZBIF.\n\nVerify: {$url}\n\nIf you did not create an account, you can ignore this message.\n\nZBIF"
        );
    }

    public static function verifyOtp(string $email, string $firstName, string $code): void
    {
        $name = self::e($firstName);
        $safeCode = self::e($code);
        $html = self::wrap(
            'Your verification code',
            '<p style="margin:0 0 14px;line-height:1.55">Hi ' . $name . ',</p>'
            . '<p style="margin:0 0 14px;line-height:1.55">Use this one-time code to verify your email for ZBIF. It expires in 10 minutes.</p>'
            . '<p style="margin:18px 0;text-align:center">'
            . '<span style="display:inline-block;font-size:28px;font-weight:700;letter-spacing:0.28em;color:#0F1C30;font-family:Consolas,Monaco,monospace;background:#F3F5F8;border:1px solid #E2E6EC;border-radius:12px;padding:14px 22px">'
            . $safeCode . '</span></p>'
            . '<p style="margin:0 0 8px;line-height:1.55;color:#5A6678;font-size:13px">Enter the code on the verification page. Do not share it with anyone.</p>'
            . '<p style="margin:0;line-height:1.55;color:#5A6678;font-size:13px">If you did not request this, you can ignore this email.</p>',
            null,
            null
        );
        $text = "Hi {$firstName},\n\n"
            . "Your ZBIF verification code is: {$code}\n\n"
            . "This code expires in 10 minutes.\n"
            . "Enter it on the verification page to activate your account.\n\n"
            . "If you did not request this, ignore this email.\n\n"
            . "ZBIF · Zimbabwe Business Innovation Forum";
        // Subject format helps Gmail/Outlook treat this as a security code, not promo mail.
        self::send($email, 'ZBIF verification code: ' . $code, $html, $text, true);
    }

    public static function welcome(string $email, string $firstName, string $persona): void
    {
        $cta = match ($persona) {
            'corporate' => ['Submit a challenge', '/challenges'],
            'innovator' => ['Complete your solution profile', '/app/solutions'],
            'investor' => ['Browse innovators', '/innovators'],
            default => ['Open your dashboard', '/app'],
        };
        $url = Url::to($cta[1]);
        self::send(
            $email,
            'Welcome to ZBIF',
            self::wrap(
                'You are verified',
                '<p style="margin:0 0 14px;line-height:1.55">Hi ' . self::e($firstName) . ',</p>'
                . '<p style="margin:0 0 14px;line-height:1.55">Your email is verified. You are ready to participate in the marketplace.</p>',
                $url,
                $cta[0]
            ),
            "Hi {$firstName},\n\nYour email is verified. Sign in at " . Url::to('/login.php') . "\n\nZBIF"
        );
    }

    public static function resumeRegistration(string $email, string $url): void
    {
        self::send(
            $email,
            'Resume your ZBIF registration',
            self::wrap(
                'Continue where you left off',
                '<p style="margin:0 0 14px;line-height:1.55">Your ZBIF registration is waiting. Use the link below to pick up at your next step.</p>',
                $url,
                'Resume registration'
            ),
            "Continue your ZBIF registration:\n{$url}\n\nZBIF"
        );
    }

    public static function loginLink(string $email, string $url): void
    {
        self::send(
            $email,
            'Your ZBIF sign-in link',
            self::wrap(
                'Sign in without a password',
                '<p style="margin:0 0 14px;line-height:1.55">Use this one-time link to sign in. It expires in 15 minutes.</p>',
                $url,
                'Sign in to ZBIF'
            ),
            "Sign in to ZBIF (expires in 15 minutes):\n{$url}\n\nIf you did not request this, ignore this email.\n\nZBIF"
        );
    }

    public static function resetPassword(string $email, string $url): void
    {
        self::send(
            $email,
            'Reset your ZBIF password',
            self::wrap(
                'Reset your password',
                '<p style="margin:0 0 14px;line-height:1.55">We received a request to reset your ZBIF password. This link expires in 60 minutes.</p>',
                $url,
                'Choose a new password'
            ),
            "Reset your ZBIF password (expires in 60 minutes):\n{$url}\n\nIf you did not request this, ignore this email.\n\nZBIF"
        );
    }

    public static function passwordChanged(string $email, string $firstName): void
    {
        self::send(
            $email,
            'Your ZBIF password was changed',
            self::wrap(
                'Password updated',
                '<p style="margin:0 0 14px;line-height:1.55">Hi ' . self::e($firstName) . ',</p>'
                . '<p style="margin:0 0 14px;line-height:1.55">Your ZBIF password was changed. If this was not you, contact the organising team immediately and reset your password again.</p>',
                Url::to('/login.php'),
                'Sign in'
            ),
            "Hi {$firstName},\n\nYour ZBIF password was changed. If this was not you, reset it immediately.\n\nZBIF"
        );
    }

    public static function newSignIn(string $email, string $firstName, string $meta): void
    {
        self::send(
            $email,
            'New sign-in on your ZBIF account',
            self::wrap(
                'New sign-in detected',
                '<p style="margin:0 0 14px;line-height:1.55">Hi ' . self::e($firstName) . ',</p>'
                . '<p style="margin:0 0 14px;line-height:1.55">We noticed a new sign-in on your ZBIF account.</p>'
                . '<p style="margin:0 0 14px;color:#5A6678">' . self::e($meta) . '</p>'
                . '<p style="margin:0;line-height:1.55">If this was you, no action is needed.</p>',
                Url::to('/app/profile'),
                'Review sessions'
            ),
            "Hi {$firstName},\n\nNew sign-in on your ZBIF account:\n{$meta}\n\nIf this was not you, change your password.\n\nZBIF"
        );
    }

    private static function send(string $email, string $subject, string $html, ?string $text = null, bool $required = false): void
    {
        $ok = Notifier::sendEmail($email, $subject, $html, $text);
        if (!$ok && $required) {
            $detail = Notifier::lastMailError() ?: 'Email could not be delivered.';
            throw new \RuntimeException($detail);
        }
    }

    private static function wrap(string $title, string $bodyHtml, ?string $ctaUrl, ?string $ctaLabel): string
    {
        $button = '';
        if ($ctaUrl && $ctaLabel) {
            $button = '<p style="margin:24px 0 8px;text-align:center">'
                . '<a href="' . self::e($ctaUrl) . '" style="display:inline-block;background:#D4AF37;color:#0F1C30;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:999px">'
                . self::e($ctaLabel) . '</a></p>';
        }
        return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width">'
            . '<title>' . self::e($title) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#EEF1F6;font-family:Arial,Helvetica,sans-serif;color:#0F1C30">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EEF1F6;padding:24px 12px">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #E2E6EC">'
            . '<tr><td style="background:#0F1C30;color:#ffffff;padding:18px 24px;font-size:17px;font-weight:700;letter-spacing:.02em">ZBIF</td></tr>'
            . '<tr><td style="padding:28px 24px">'
            . '<h1 style="font-size:20px;line-height:1.3;margin:0 0 14px;color:#0F1C30">' . self::e($title) . '</h1>'
            . $bodyHtml . $button
            . '<p style="margin:28px 0 0;font-size:12px;line-height:1.5;color:#5A6678">ZB Financial Holdings · Zimbabwe Business Innovation Forum</p>'
            . '<p style="margin:8px 0 0;font-size:12px;line-height:1.5;color:#5A6678">This is a transactional message about your account. Need help? Reply to this email or use Contact on the site.</p>'
            . '</td></tr></table>'
            . '</td></tr></table></body></html>';
    }

    private static function e(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }
}
