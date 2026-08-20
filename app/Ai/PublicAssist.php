<?php
declare(strict_types=1);

namespace App\Ai;

use App\Domain\EventContext;
use App\Support\Database;
use App\Support\Str;

/**
 * Public-facing ZBIF assistant. Answers only visitor-safe topics.
 * Never exposes admin, SMTP, user data, internal IDs, or unpublished details.
 */
final class PublicAssist
{
    private const BLOCKED = [
        'password', 'smtp', 'database', 'admin login', 'super_admin', 'api key',
        'secret', 'token hash', 'csrf', 'env', '.env', 'sql', 'root password',
        'paynow key', 'integration key', 'totp', 'recovery code', 'ssn',
        'other user', 'all users', 'export users', 'drop table',
    ];

    public static function answer(string $question): array
    {
        $q = trim(Str::noEmDash($question));
        if ($q === '') {
            return self::pack('Ask me about registering, the forum programme, or how matching works.', false);
        }
        if (self::isBlocked($q)) {
            return self::pack(
                'I can only share public ZBIF information such as how to register, who can join, and general forum details. For account or organiser matters, use Contact or Sign in.',
                false
            );
        }

        $event = EventContext::current();
        $liveFacts = self::livePublicFacts($event);
        $chunks = self::publicChunks($q);
        $context = $liveFacts . "\n";
        foreach ($chunks as $c) {
            $context .= "### {$c['title']}\n{$c['body']}\n\n";
        }

        $system = 'You are Nova, a helpful ZBIF visitor assistant. '
            . 'Answer ONLY with public participant information from the knowledge provided. '
            . 'Allowed topics: what ZBIF is, how to register, participation types, high-level process (challenge, match, pitch, deal), public programme outline, venue city, published dates, public FAQ. '
            . 'Never reveal admin tools, SMTP, credentials, internal IDs, other people\'s data, unpublished fees, private budgets, staff personal contacts, or system internals. '
            . 'If asked something sensitive or unknown, refuse politely and suggest /faq, /contact, or /register. '
            . 'Keep answers short (2-5 sentences). Do not use em-dashes. Do not invent dates, fees, or venues.';

        $user = "Public knowledge:\n{$context}\n\nVisitor question: {$q}";
        $res = LlmClient::complete($system, $user, 'assist');
        if (!$res['ok']) {
            $fallback = self::deterministicFallback($q, $chunks, $event);
            return self::pack($fallback, $chunks !== []);
        }
        $text = LlmClient::stripEmDashes($res['text'] ?? '');
        if (self::isBlocked($text) || self::looksLeaky($text)) {
            return self::pack(
                'For that request, please use the FAQ or contact organisers. I can help with registration and public forum info.',
                false
            );
        }
        return [
            'answer' => $text,
            'grounded' => true,
            'provider' => $res['provider'] ?? null,
            'degraded' => false,
        ];
    }

    /** @param array<string,mixed>|null $event */
    private static function livePublicFacts(?array $event): string
    {
        if (!$event) {
            return "ZBIF is the Zimbabwe Business Innovation Forum.\n";
        }
        $starts = date('j M Y', strtotime((string) $event['starts_at']));
        $ends = date('j M Y', strtotime((string) $event['ends_at']));
        return "Event: {$event['name']} ({$event['edition']}). Dates: {$starts} to {$ends}. "
            . "City: {$event['city']}. Venue: {$event['venue']}. "
            . "Public status only: participants register at /register then sign in at /login.php.\n";
    }

    /** @return list<array<string,mixed>> */
    private static function publicChunks(string $query): array
    {
        $terms = array_filter(preg_split('/\W+/', strtolower($query)) ?: []);
        $rows = Database::fetchAll(
            "SELECT * FROM knowledge_chunks
             WHERE source IN ('faq','programme','sectors','public')
                OR tags LIKE '%faq%' OR tags LIKE '%programme%' OR tags LIKE '%public%' OR tags LIKE '%event%' OR tags LIKE '%sectors%'
             ORDER BY id ASC LIMIT 40"
        );
        if ($terms === []) {
            return array_slice($rows, 0, 5);
        }
        usort($rows, static function ($a, $b) use ($terms) {
            return self::score($b, $terms) <=> self::score($a, $terms);
        });
        return array_slice($rows, 0, 5);
    }

    /** @param list<array<string,mixed>> $chunks @param array<string,mixed>|null $event */
    private static function deterministicFallback(string $q, array $chunks, ?array $event): string
    {
        $ql = strtolower($q);
        if (str_contains($ql, 'register') || str_contains($ql, 'sign up')) {
            return 'Create an account at /register. Choose your participation type, then complete the short profile. Sign in later at /login.php.';
        }
        if (str_contains($ql, 'date') || str_contains($ql, 'when') || str_contains($ql, 'countdown')) {
            if ($event) {
                return 'The active forum runs ' . date('j M Y', strtotime((string) $event['starts_at']))
                    . ' to ' . date('j M Y', strtotime((string) $event['ends_at']))
                    . ' in ' . ($event['city'] ?? 'Zimbabwe') . '.';
            }
            return 'Forum dates are shown on the homepage countdown and venue page.';
        }
        if (str_contains($ql, 'how') && (str_contains($ql, 'work') || str_contains($ql, 'match'))) {
            return 'Companies submit challenges. Innovators are matched. Solutions are developed. Deals close at the forum through pitches and deal rooms.';
        }
        if ($chunks !== []) {
            return LlmClient::stripEmDashes((string) $chunks[0]['body']);
        }
        return 'I do not have that public detail. See /faq or /contact, or continue with /register.';
    }

    private static function isBlocked(string $text): bool
    {
        $l = strtolower($text);
        foreach (self::BLOCKED as $term) {
            if (str_contains($l, $term)) {
                return true;
            }
        }
        return false;
    }

    private static function looksLeaky(string $text): bool
    {
        return (bool) preg_match('/\b(smtp|password_hash|AUTH_APP_KEY|sk-[a-z0-9]{10,}|Bearer\s+[A-Za-z0-9._-]{20,})\b/i', $text);
    }

    /** @param list<string> $terms */
    private static function score(array $chunk, array $terms): int
    {
        $hay = strtolower($chunk['title'] . ' ' . $chunk['body'] . ' ' . ($chunk['tags'] ?? ''));
        $score = 0;
        foreach ($terms as $t) {
            if ($t !== '' && str_contains($hay, $t)) {
                $score++;
            }
        }
        return $score;
    }

    /** @return array{answer:string,grounded:bool,provider:?string,degraded:bool} */
    private static function pack(string $answer, bool $grounded): array
    {
        return [
            'answer' => LlmClient::stripEmDashes($answer),
            'grounded' => $grounded,
            'provider' => null,
            'degraded' => true,
        ];
    }
}
