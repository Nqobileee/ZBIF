<?php
declare(strict_types=1);

namespace App\Notify;

use App\Support\Database;
use App\Support\Env;

final class Notifier
{
    private static ?string $lastMailError = null;

    public static function prefs(int $userId): array
    {
        $row = Database::fetch('SELECT * FROM notification_preferences WHERE user_id = ?', [$userId]);
        if ($row) {
            return $row;
        }
        return [
            'email_enabled' => 1,
            'sms_enabled' => 1,
            'whatsapp_enabled' => 0,
            'in_app_enabled' => 1,
            'digest_enabled' => 1,
        ];
    }

    /**
     * Preference-aware multi-channel notify.
     * @param list<string> $channels subset of in_app|email|sms
     */
    public static function notifyUser(int $userId, string $title, string $body, ?string $link = null, array $channels = ['in_app', 'email']): void
    {
        $user = Database::fetch('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$userId]);
        if (!$user) {
            return;
        }
        $prefs = self::prefs($userId);
        $title = self::clean($title);
        $body = self::clean($body);

        if (in_array('in_app', $channels, true) && !empty($prefs['in_app_enabled'])) {
            self::inApp($userId, $title, $body, $link);
        }
        if (in_array('email', $channels, true) && !empty($prefs['email_enabled']) && !empty($user['email'])) {
            $html = self::renderEmail($title, $body, $link);
            self::sendEmail((string) $user['email'], $title, $html);
        }
        if (in_array('sms', $channels, true) && !empty($prefs['sms_enabled']) && !empty($user['phone'])) {
            self::sendSms((string) $user['phone'], $title . ': ' . mb_substr($body, 0, 120) . ($link ? ' ' . $link : ''));
        }
    }

    public static function renderEmail(string $title, string $body, ?string $link = null): string
    {
        $tpl = ZBIF_ROOT . '/views/emails/transactional.php';
        if (!is_file($tpl)) {
            $html = '<h1>' . htmlspecialchars($title) . '</h1><p>' . nl2br(htmlspecialchars($body)) . '</p>';
            if ($link) {
                $html .= '<p><a href="' . htmlspecialchars(\App\Support\Url::to($link)) . '">Open in ZBIF</a></p>';
            }
            return $html;
        }
        ob_start();
        $emailTitle = $title;
        $emailBody = $body;
        $emailLink = $link ? \App\Support\Url::to($link) : null;
        include $tpl;
        return (string) ob_get_clean();
    }

    public static function inApp(int $userId, string $title, string $body, ?string $link = null): void
    {
        Database::query(
            'INSERT INTO notifications (user_id, channel, title, body, link, is_read, created_at)
             VALUES (?, \'in_app\', ?, ?, ?, 0, NOW())',
            [$userId, self::clean($title), self::clean($body), $link]
        );
    }

    public static function queue(string $jobType, array $payload, string $queue = 'default', ?\DateTimeInterface $when = null): void
    {
        $available = ($when ?? new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        Database::query(
            'INSERT INTO jobs (queue, job_type, payload_json, attempts, available_at, created_at)
             VALUES (?, ?, ?, 0, ?, NOW())',
            [$queue, $jobType, json_encode($payload), $available]
        );
    }

    /** @param array<string, mixed> $data */
    public static function event(string $event, array $data): void
    {
        self::queue('notify_event', ['event' => $event, 'data' => $data]);
        if ($event === 'challenge_status' && isset($data['challenge_id'])) {
            $challenge = Database::fetch('SELECT created_by, title, status FROM challenges WHERE id = ?', [$data['challenge_id']]);
            if ($challenge) {
                self::notifyUser(
                    (int) $challenge['created_by'],
                    'Challenge update',
                    'Your challenge "' . $challenge['title'] . '" is now ' . str_replace('_', ' ', (string) $data['status']) . '.',
                    '/app/challenges/' . $data['challenge_id']
                );
            }
        }
        if ($event === 'deal_stage_changed' && isset($data['deal_room_id'])) {
            $parts = Database::fetchAll('SELECT user_id FROM deal_room_participants WHERE deal_room_id = ?', [$data['deal_room_id']]);
            foreach ($parts as $p) {
                self::notifyUser(
                    (int) $p['user_id'],
                    'Deal stage changed',
                    'A deal room moved to ' . str_replace('_', ' ', (string) $data['stage']) . '.',
                    '/app/deals/' . $data['deal_room_id']
                );
            }
        }
        if ($event === 'deal_message' && isset($data['deal_room_id'], $data['from_user_id'])) {
            $parts = Database::fetchAll(
                'SELECT user_id FROM deal_room_participants WHERE deal_room_id = ? AND user_id <> ?',
                [$data['deal_room_id'], $data['from_user_id']]
            );
            foreach ($parts as $p) {
                self::notifyUser(
                    (int) $p['user_id'],
                    'New deal room message',
                    (string) ($data['preview'] ?? 'You have a new message in a deal room.'),
                    '/app/deals/' . $data['deal_room_id'],
                    ['in_app', 'email']
                );
            }
        }
    }

    public static function sendEmail(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        $html = self::cleanHtml($htmlBody);
        $text = $textBody !== null ? self::clean($textBody) : null;
        $subj = self::clean($subject);
        $ok = false;
        self::$lastMailError = null;
        try {
            if (\App\Support\Settings::mail('smtp_host', 'MAIL_HOST', '') !== '') {
                $ok = SmtpMailer::send($to, $subj, $html, $text);
            }
        } catch (\Throwable $e) {
            $ok = false;
            self::queue('smtp_error', ['to' => $to, 'subject' => $subject, 'error' => $e->getMessage()]);
            self::$lastMailError = $e->getMessage();
        }
        if (!$ok && \App\Support\Settings::mail('smtp_host', 'MAIL_HOST', '') === '') {
            $from = \App\Support\Settings::mail('mail_from', 'MAIL_FROM_ADDRESS', '');
            $fromName = \App\Support\Settings::mail('mail_from_name', 'MAIL_FROM_NAME', 'ZBIF');
            $smtpUser = trim(\App\Support\Settings::mail('smtp_user', 'MAIL_USERNAME', ''));
            if ($from === '' || preg_match('/\.(local|test|invalid|example)$/i', (string) substr(strrchr($from, '@') ?: '', 1))) {
                if ($smtpUser !== '' && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
                    $from = $smtpUser;
                } else {
                    self::$lastMailError = 'Configure SMTP with a real From address on your domain (not .local).';
                    self::queue('log_email', ['to' => $to, 'subject' => $subject, 'ok' => false]);
                    return false;
                }
            }
            $boundary = 'zbif_' . bin2hex(random_bytes(8));
            $plain = $text ?? SmtpMailer::htmlToText($html);
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
                'From: ' . $fromName . ' <' . $from . '>',
                'Reply-To: ' . $from,
                'Auto-Submitted: auto-generated',
            ];
            $body = "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n\r\n{$plain}\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n"
                . "--{$boundary}--";
            $ok = (bool) @mail($to, $subj, $body, implode("\r\n", $headers));
            if (!$ok) {
                self::$lastMailError = 'PHP mail() fallback failed and no SMTP host is set.';
            }
        } elseif (!$ok && self::$lastMailError === null) {
            self::$lastMailError = 'SMTP send failed without a detailed error. Check host, port, encryption, and password.';
        }
        self::queue('log_email', ['to' => $to, 'subject' => $subject, 'ok' => $ok]);
        return (bool) $ok;
    }

    public static function lastMailError(): ?string
    {
        return self::$lastMailError;
    }

    public static function clearLastMailError(): void
    {
        self::$lastMailError = null;
    }

    public static function sendSms(string $phone, string $message): bool
    {
        $key = Env::get('AT_API_KEY');
        $username = Env::get('AT_USERNAME');
        if (!$key || !$username) {
            self::queue('sms_skipped', ['phone' => $phone, 'message' => $message]);
            return false;
        }
        $payload = http_build_query([
            'username' => $username,
            'to' => $phone,
            'message' => self::clean($message),
            'from' => Env::get('AT_SENDER_ID', 'ZBIF'),
        ]);
        $ch = curl_init('https://api.africastalking.com/version1/messaging');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
                'apiKey: ' . $key,
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300 && $res !== false;
    }

    private static function clean(string $text): string
    {
        return str_replace(["\u{2014}", "\u{2013}", '—', '–'], [',', ',', ',', ','], $text);
    }

    private static function cleanHtml(string $html): string
    {
        return self::clean($html);
    }
}
