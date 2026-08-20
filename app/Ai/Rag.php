<?php
declare(strict_types=1);

namespace App\Ai;

use App\Support\Database;

final class Rag
{
    /** @return list<array<string, mixed>> */
    public static function retrieve(string $query, int $limit = 5): array
    {
        $terms = array_filter(preg_split('/\W+/', strtolower($query)) ?: []);
        if ($terms === []) {
            return Database::fetchAll('SELECT * FROM knowledge_chunks ORDER BY id ASC LIMIT ?', [$limit]);
        }
        $like = '%' . implode('%', array_slice($terms, 0, 4)) . '%';
        $rows = Database::fetchAll(
            'SELECT * FROM knowledge_chunks WHERE body LIKE ? OR title LIKE ? OR tags LIKE ? LIMIT 20',
            [$like, $like, $like]
        );
        if ($rows === []) {
            $rows = Database::fetchAll('SELECT * FROM knowledge_chunks ORDER BY id ASC LIMIT 10');
        }
        usort($rows, static function ($a, $b) use ($terms) {
            return self::score($b, $terms) <=> self::score($a, $terms);
        });
        return array_slice($rows, 0, $limit);
    }

    public static function answer(string $question): array
    {
        $chunks = self::retrieve($question);
        $context = '';
        foreach ($chunks as $c) {
            $context .= "### {$c['title']}\n{$c['body']}\n\n";
        }
        $system = 'You are Nova, the ZBIF concierge. Answer only from the provided knowledge base. '
            . 'If unknown, say you do not know and suggest /faq or /contact. Never invent dates, fees, or venues. '
            . 'Do not use em-dashes. Speak plainly to executives.';
        $user = "Knowledge base:\n{$context}\n\nQuestion: {$question}";
        $res = LlmClient::complete($system, $user, 'rag');
        if (!$res['ok']) {
            $fallback = $chunks[0]['body'] ?? 'I do not have that detail yet. Please see the FAQ or contact the organisers.';
            return ['answer' => LlmClient::stripEmDashes($fallback), 'grounded' => $chunks !== [], 'provider' => null, 'degraded' => true];
        }
        return ['answer' => $res['text'], 'grounded' => true, 'provider' => $res['provider'], 'degraded' => false];
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
}
