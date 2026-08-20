<?php
declare(strict_types=1);

namespace App\Ai;

use App\Support\Database;
use App\Support\Env;
use App\Support\Logger;

final class LlmClient
{
    /** @return array{ok: bool, text?: string, provider?: string, error?: string} */
    public static function complete(string $system, string $user, string $purpose = 'chat'): array
    {
        $providers = [
            'claude' => [self::class, 'claude'],
            'groq' => [self::class, 'groq'],
            'gemini' => [self::class, 'gemini'],
        ];
        foreach ($providers as $name => $fn) {
            $start = microtime(true);
            try {
                $text = $fn($system, $user);
                if ($text !== null && $text !== '') {
                    self::log($name, $purpose, true, (int) ((microtime(true) - $start) * 1000));
                    return ['ok' => true, 'text' => self::stripEmDashes($text), 'provider' => $name];
                }
            } catch (\Throwable $e) {
                self::log($name, $purpose, false, (int) ((microtime(true) - $start) * 1000), $e->getMessage());
                Logger::log('warning', 'LLM provider failed', ['provider' => $name, 'error' => $e->getMessage()]);
            }
        }
        return ['ok' => false, 'error' => 'All LLM providers unavailable'];
    }

    /** @return array<string, bool> */
    public static function health(): array
    {
        return [
            'claude' => (bool) Env::get('ANTHROPIC_API_KEY'),
            'groq' => (bool) Env::get('GROQ_API_KEY'),
            'gemini' => (bool) Env::get('GEMINI_API_KEY'),
        ];
    }

    private static function claude(string $system, string $user): ?string
    {
        $key = Env::get('ANTHROPIC_API_KEY');
        if (!$key) {
            return null;
        }
        $body = json_encode([
            'model' => Env::get('ANTHROPIC_MODEL', 'claude-3-5-haiku-latest'),
            'max_tokens' => 800,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $user]],
        ]);
        $res = self::httpJson('https://api.anthropic.com/v1/messages', $body, [
            'x-api-key: ' . $key,
            'anthropic-version: 2023-06-01',
            'content-type: application/json',
        ]);
        return $res['content'][0]['text'] ?? null;
    }

    private static function groq(string $system, string $user): ?string
    {
        $key = Env::get('GROQ_API_KEY');
        if (!$key) {
            return null;
        }
        $body = json_encode([
            'model' => Env::get('GROQ_MODEL', 'llama-3.1-8b-instant'),
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'max_tokens' => 800,
        ]);
        $res = self::httpJson('https://api.groq.com/openai/v1/chat/completions', $body, [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ]);
        return $res['choices'][0]['message']['content'] ?? null;
    }

    private static function gemini(string $system, string $user): ?string
    {
        $key = Env::get('GEMINI_API_KEY');
        if (!$key) {
            return null;
        }
        $model = Env::get('GEMINI_MODEL', 'gemini-1.5-flash');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . urlencode($key);
        $body = json_encode([
            'contents' => [['parts' => [['text' => $system . "\n\n" . $user]]]],
        ]);
        $res = self::httpJson($url, $body, ['Content-Type: application/json']);
        return $res['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    /** @param list<string> $headers */
    private static function httpJson(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => (int) Env::get('AI_TIMEOUT', '20'),
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $code >= 400) {
            throw new \RuntimeException($err ?: ('HTTP ' . $code . ': ' . substr((string) $raw, 0, 200)));
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function log(string $provider, string $purpose, bool $ok, int $ms, ?string $error = null): void
    {
        Database::query(
            'INSERT INTO ai_request_logs (provider, purpose, success, latency_ms, error_message, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [$provider, $purpose, $ok ? 1 : 0, $ms, $error ? substr($error, 0, 250) : null]
        );
    }

    public static function stripEmDashes(string $text): string
    {
        return str_replace(["\u{2014}", "\u{2013}", '—', '–'], [',', ',', ',', ','], $text);
    }
}
