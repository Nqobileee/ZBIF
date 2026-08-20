<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Ai\LlmClient;
use App\Auth\Auth;
use App\Domain\AuditLog;
use App\Notify\Notifier;
use App\Notify\SmtpMailer;
use App\Payments\PayNowGateway;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\Settings;
use App\Support\View;

final class SettingsAdminController
{
    private const TABS = ['email', 'ai', 'payments', 'flags', 'jobs'];

    private function guard(): void
    {
        Auth::requireAdmin();
    }

    public function index(): void
    {
        $this->guard();
        Settings::ensureTable();
        $tab = $this->resolveTab($_GET['tab'] ?? 'email');
        $pay = new PayNowGateway();

        View::make('admin/settings', [
            'title' => 'Settings',
            'subtitle' => 'Email, AI, payments, feature flags, and system health in one place.',
            'tab' => $tab,
            'tabs' => [
                'email' => 'Email / SMTP',
                'ai' => 'AI providers',
                'payments' => 'Payments',
                'flags' => 'Feature flags',
                'jobs' => 'Failed jobs',
            ],
            'smtp' => [
                'host' => Settings::get('smtp_host', (string) \App\Support\Env::get('MAIL_HOST', '')),
                'port' => Settings::get('smtp_port', (string) \App\Support\Env::get('MAIL_PORT', '587')),
                'user' => Settings::get('smtp_user', (string) \App\Support\Env::get('MAIL_USERNAME', '')),
                'pass_set' => Settings::get('smtp_pass', '') !== null && Settings::get('smtp_pass', '') !== '',
                'encryption' => Settings::get('smtp_encryption', (string) \App\Support\Env::get('MAIL_ENCRYPTION', 'tls')),
                'from' => Settings::get('mail_from', (string) \App\Support\Env::get('MAIL_FROM_ADDRESS', '')),
                'from_name' => Settings::get('mail_from_name', (string) \App\Support\Env::get('MAIL_FROM_NAME', 'ZBIF')),
                'reply_to' => Settings::get('mail_reply_to', (string) \App\Support\Env::get('MAIL_REPLY_TO', '')),
            ],
            'ai' => LlmClient::health(),
            'payments' => $pay->health(),
            'queue' => (int) (Database::fetch('SELECT COUNT(*) AS c FROM jobs WHERE completed_at IS NULL AND failed_at IS NULL')['c'] ?? 0),
            'failedJobs' => Database::fetchAll('SELECT id, job_type, last_error, failed_at FROM jobs WHERE failed_at IS NOT NULL ORDER BY id DESC LIMIT 30'),
            'flags' => Database::fetchAll('SELECT * FROM feature_flags ORDER BY flag_key'),
        ], 'layouts/admin');
    }

    public function save(): void
    {
        $this->guard();
        Csrf::requireValid();
        $tab = $this->resolveTab($_POST['tab'] ?? 'email');
        $pass = (string) ($_POST['smtp_pass'] ?? '');
        $pairs = [
            'smtp_host' => trim($_POST['smtp_host'] ?? ''),
            'smtp_port' => trim($_POST['smtp_port'] ?? '587'),
            'smtp_user' => trim($_POST['smtp_user'] ?? ''),
            'smtp_encryption' => strtolower(trim($_POST['smtp_encryption'] ?? 'tls')),
            'mail_from' => trim($_POST['mail_from'] ?? ''),
            'mail_from_name' => trim($_POST['mail_from_name'] ?? 'ZBIF'),
            'mail_reply_to' => trim($_POST['mail_reply_to'] ?? ''),
        ];
        if (trim($pass) !== '') {
            $pairs['smtp_pass'] = $pass;
        }
        Settings::setMany($pairs);
        AuditLog::record('settings.smtp', 'system', null, ['host' => $pairs['smtp_host']]);
        Response::flash('success', 'SMTP settings saved.');
        Response::redirect('/admin/settings?tab=' . $tab);
    }

    public function test(): void
    {
        $this->guard();
        Csrf::requireValid();
        $tab = $this->resolveTab($_POST['tab'] ?? 'email');
        $to = trim($_POST['test_to'] ?? '');
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Response::flash('error', 'Enter a valid test email address.');
            Response::redirect('/admin/settings?tab=' . $tab);
            return;
        }

        Notifier::clearLastMailError();
        try {
            $result = SmtpMailer::sendWithDiagnostics(
                $to,
                'ZBIF SMTP test',
                '<p>This is a test message from ZBIF admin settings.</p><p>If you received this, SMTP is working.</p>'
            );
            Response::flash('success', 'Test email sent to ' . $to . '.');
            AuditLog::record('settings.smtp_test', 'system', null, ['to' => $to, 'ok' => true]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            Response::flash('error', 'Test email failed: ' . $msg);
            AuditLog::record('settings.smtp_test', 'system', null, ['to' => $to, 'ok' => false, 'error' => $msg]);
        }
        Response::redirect('/admin/settings?tab=' . $tab);
    }

    public function toggleFlag(): void
    {
        $this->guard();
        Csrf::requireValid();
        $key = (string) ($_POST['flag_key'] ?? '');
        $row = Database::fetch('SELECT * FROM feature_flags WHERE flag_key = ?', [$key]);
        if ($row) {
            $next = (int) $row['is_enabled'] ? 0 : 1;
            Database::query('UPDATE feature_flags SET is_enabled = ?, updated_at = NOW() WHERE id = ?', [$next, $row['id']]);
        }
        Response::redirect('/admin/settings?tab=flags');
    }

    private function resolveTab(string $tab): string
    {
        $tab = strtolower(trim($tab));
        return in_array($tab, self::TABS, true) ? $tab : 'email';
    }
}
