<?php
declare(strict_types=1);

namespace App\Notify;

use App\Support\Settings;
use App\Support\Env;

/**
 * Minimal SMTP client for shared hosting (no Composer).
 * Supports STARTTLS (587) and implicit SSL (465), LOGIN/PLAIN auth.
 * Sends multipart/alternative with inbox-friendly headers.
 */
final class SmtpMailer
{
    /** @return array{ok:bool, detail:string} */
    public static function sendWithDiagnostics(string $to, string $subject, string $htmlBody, ?string $textBody = null): array
    {
        $host = trim(Settings::mail('smtp_host', 'MAIL_HOST', ''));
        if ($host === '') {
            throw new \RuntimeException('SMTP host is not configured.');
        }

        $port = (int) Settings::mail('smtp_port', 'MAIL_PORT', '587');
        $user = trim(Settings::mail('smtp_user', 'MAIL_USERNAME', ''));
        $pass = (string) Settings::mail('smtp_pass', 'MAIL_PASSWORD', '');
        $encryption = strtolower(trim(Settings::mail('smtp_encryption', 'MAIL_ENCRYPTION', 'tls')));
        $from = trim(Settings::mail('mail_from', 'MAIL_FROM_ADDRESS', ''));
        $fromName = trim(Settings::mail('mail_from_name', 'MAIL_FROM_NAME', 'ZBIF'));
        $replyTo = trim(Settings::mail('mail_reply_to', 'MAIL_REPLY_TO', ''));

        if ($user !== '' && $pass === '') {
            throw new \RuntimeException('SMTP password is empty. Save the password again in Settings.');
        }

        // Align visible From with authenticated mailbox / real domain (critical for inbox placement).
        [$from, $envelopeFrom, $fromName, $alignNote] = self::resolveFromIdentities($from, $fromName, $user);
        if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('From email is missing or invalid. Use a real address on your SMTP domain (not .local).');
        }
        if ($replyTo === '' || !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $replyTo = $from;
        }

        $ehlo = self::ehloName($from);
        $log = [];
        if ($alignNote !== '') {
            $log[] = $alignNote;
        }

        $remote = self::remoteEndpoint($host, $port, $encryption);
        $crypto = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $crypto |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $host,
                'allow_self_signed' => false,
                'crypto_method' => $crypto,
            ],
        ]);

        $fp = @stream_socket_client($remote, $errno, $errstr, 25, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            $ctxInsecure = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                    'crypto_method' => $crypto,
                ],
            ]);
            $fp = @stream_socket_client($remote, $errno, $errstr, 25, STREAM_CLIENT_CONNECT, $ctxInsecure);
            if (!$fp) {
                throw new \RuntimeException("Cannot connect to {$remote}: {$errstr} ({$errno})");
            }
            $log[] = 'connected with relaxed TLS verify (local CA store may be incomplete)';
        }

        stream_set_timeout($fp, 25);
        try {
            self::expect($fp, [220], $log);
            self::cmd($fp, 'EHLO ' . $ehlo, [250], $log);

            if ($encryption === 'tls' || ($encryption === 'starttls')) {
                self::cmd($fp, 'STARTTLS', [220], $log);
                $cryptoOk = @stream_socket_enable_crypto($fp, true, $crypto);
                if (!$cryptoOk) {
                    $cryptoOk = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                }
                if (!$cryptoOk) {
                    throw new \RuntimeException('STARTTLS negotiation failed. Try port 465 with SSL, or check OpenSSL.');
                }
                self::cmd($fp, 'EHLO ' . $ehlo, [250], $log);
            }

            if ($user !== '') {
                self::authenticate($fp, $user, $pass, $log);
            }

            self::cmd($fp, 'MAIL FROM:<' . $envelopeFrom . '>', [250], $log);
            self::cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251], $log);
            self::cmd($fp, 'DATA', [354], $log);

            $payload = self::buildMimeMessage(
                $to,
                $from,
                $fromName,
                $envelopeFrom,
                $replyTo,
                $subject,
                $htmlBody,
                $textBody
            );
            fwrite($fp, $payload . "\r\n.\r\n");
            self::expect($fp, [250], $log);
            self::cmd($fp, 'QUIT', [221], $log);
        } finally {
            if (is_resource($fp)) {
                fclose($fp);
            }
        }

        return ['ok' => true, 'detail' => implode(' | ', $log)];
    }

    public static function send(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        $result = self::sendWithDiagnostics($to, $subject, $htmlBody, $textBody);
        return !empty($result['ok']);
    }

    /**
     * Prefer authenticated mailbox; reject disposable/.local From that tanks reputation.
     * @return array{0:string,1:string,2:string,3:string} from, envelopeFrom, fromName, note
     */
    private static function resolveFromIdentities(string $from, string $fromName, string $smtpUser): array
    {
        $note = '';
        $fromName = $fromName !== '' ? $fromName : 'ZBIF';

        $badFrom = $from === ''
            || !filter_var($from, FILTER_VALIDATE_EMAIL)
            || preg_match('/\.(local|test|invalid|example|localhost)$/i', (string) substr(strrchr($from, '@') ?: '', 1))
            || str_ends_with(strtolower($from), '@localhost');

        if ($smtpUser !== '' && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
            $envelopeFrom = $smtpUser;
            if ($badFrom || strcasecmp($from, $smtpUser) !== 0) {
                $fromDomain = strtolower((string) substr(strrchr($from, '@') ?: '', 1));
                $userDomain = strtolower((string) substr(strrchr($smtpUser, '@') ?: '', 1));
                if ($badFrom || ($fromDomain !== '' && $userDomain !== '' && $fromDomain !== $userDomain)) {
                    $note = 'From aligned to SMTP mailbox for deliverability';
                    $from = $smtpUser;
                }
            }
            return [$from, $envelopeFrom, $fromName, $note];
        }

        return [$from, $from, $fromName, $note];
    }

    private static function buildMimeMessage(
        string $to,
        string $from,
        string $fromName,
        string $envelopeFrom,
        string $replyTo,
        string $subject,
        string $htmlBody,
        ?string $textBody
    ): string {
        $domain = strtolower((string) substr(strrchr($from, '@') ?: '@zbif.org', 1)) ?: 'zbif.org';
        $messageId = sprintf('<%s@%s>', bin2hex(random_bytes(16)), $domain);
        $boundary = 'zbif_' . bin2hex(random_bytes(12));
        $text = $textBody ?? self::htmlToText($htmlBody);
        $html = self::normalizeNewlines($htmlBody);
        $text = self::normalizeNewlines($text);

        $headers = [
            'Date: ' . date('r'),
            'From: ' . self::encodeName($fromName) . ' <' . $from . '>',
            'Sender: ' . self::encodeName($fromName) . ' <' . $envelopeFrom . '>',
            'Reply-To: <' . $replyTo . '>',
            'To: <' . $to . '>',
            'Message-ID: ' . $messageId,
            'Subject: ' . self::encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'Content-Language: en',
            'Auto-Submitted: auto-generated',
            'X-Auto-Response-Suppress: All',
            'X-Entity-Ref-ID: ' . bin2hex(random_bytes(8)),
        ];

        $parts = [
            '--' . $boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            self::quotedPrintable($text),
            '--' . $boundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            self::quotedPrintable($html),
            '--' . $boundary . '--',
            '',
        ];

        $raw = implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $parts);
        // Dot-stuffing for SMTP DATA
        return preg_replace('/^\./m', '..', $raw) ?? $raw;
    }

    public static function htmlToText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\/p>/i', "\n\n", $text) ?? $text;
        $text = preg_replace('/<\/(div|h[1-6]|tr|li)>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2 ($1)', $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        return trim($text);
    }

    private static function normalizeNewlines(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        return str_replace("\n", "\r\n", $value);
    }

    private static function quotedPrintable(string $value): string
    {
        $encoded = quoted_printable_encode($value);
        return str_replace(["\r\n", "\n"], ["\n", "\r\n"], $encoded);
    }

    private static function remoteEndpoint(string $host, int $port, string $encryption): string
    {
        if ($encryption === 'ssl' || $port === 465) {
            return 'ssl://' . $host . ':' . ($port ?: 465);
        }
        return 'tcp://' . $host . ':' . ($port ?: 587);
    }

    private static function ehloName(string $fromEmail = ''): string
    {
        $domain = '';
        if ($fromEmail !== '' && str_contains($fromEmail, '@')) {
            $domain = strtolower((string) substr(strrchr($fromEmail, '@'), 1));
        }
        if ($domain === '' || preg_match('/\.(local|test|invalid|example)$/i', $domain)) {
            $host = parse_url((string) (Env::get('APP_URL', '')), PHP_URL_HOST)
                ?: ($_SERVER['SERVER_NAME'] ?? '');
            $domain = is_string($host) ? strtolower($host) : '';
        }
        if ($domain === '' || $domain === 'localhost' || filter_var($domain, FILTER_VALIDATE_IP) || preg_match('/\.(local|test)$/i', $domain)) {
            $domain = 'mail.zbif.org';
        }
        return preg_replace('/[^a-zA-Z0-9.-]/', '', $domain) ?: 'mail.zbif.org';
    }

    /** @param resource $fp @param list<string> $log */
    private static function authenticate($fp, string $user, string $pass, array &$log): void
    {
        try {
            self::cmd($fp, 'AUTH LOGIN', [334], $log);
            self::cmd($fp, base64_encode($user), [334], $log);
            self::cmd($fp, base64_encode($pass), [235], $log);
            return;
        } catch (\Throwable $e) {
            $log[] = 'AUTH LOGIN failed, trying PLAIN';
        }
        $plain = base64_encode("\0" . $user . "\0" . $pass);
        self::cmd($fp, 'AUTH PLAIN ' . $plain, [235], $log);
    }

    /** @param resource $fp @param list<int> $ok @param list<string> $log */
    private static function cmd($fp, string $line, array $ok, array &$log): void
    {
        if (str_starts_with($line, 'AUTH PLAIN') || preg_match('#^[A-Za-z0-9+/=]{8,}$#', $line)) {
            $log[] = '>***';
        } else {
            $safe = preg_replace('/^(AUTH\s+\w+).*/i', '$1 …', $line) ?: $line;
            if (strlen($safe) > 80) {
                $safe = substr($safe, 0, 77) . '…';
            }
            $log[] = '>' . $safe;
        }
        fwrite($fp, $line . "\r\n");
        self::expect($fp, $ok, $log);
    }

    /** @param resource $fp @param list<int> $ok @param list<string> $log */
    private static function expect($fp, array $ok, array &$log): void
    {
        $resp = '';
        while (($line = fgets($fp, 515)) !== false) {
            $resp .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
            if ($line === '') {
                break;
            }
        }
        $trim = trim($resp);
        $log[] = '<' . substr($trim, 0, 120);
        $code = (int) substr($resp, 0, 3);
        if (!in_array($code, $ok, true)) {
            throw new \RuntimeException('SMTP error: ' . ($trim !== '' ? $trim : 'empty response (timeout or disconnect)'));
        }
    }

    private static function encodeName(string $name): string
    {
        if (preg_match('/[^\x20-\x7E]/', $name)) {
            return '=?UTF-8?B?' . base64_encode($name) . '?=';
        }
        return '"' . addcslashes($name, '"\\') . '"';
    }

    private static function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }
}
