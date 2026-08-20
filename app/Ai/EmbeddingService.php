<?php
declare(strict_types=1);

namespace App\Ai;

use App\Support\Database;
use App\Support\Env;
use App\Support\Logger;

/**
 * Enterprise matchmaking embeddings:
 * 1) Prefer remote embedding API when configured
 * 2) Fall back to deterministic hashed TF vectors (works offline on shared LAMP)
 */
final class EmbeddingService
{
    private const DIM = 256;

    public static function upsert(string $type, int $id, string $text): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        $hash = hash('sha256', $text);
        $existing = Database::fetch(
            'SELECT * FROM embeddings WHERE embeddable_type = ? AND embeddable_id = ?',
            [$type, $id]
        );
        if ($existing && ($existing['text_hash'] ?? '') === $hash && !empty($existing['vector_json'])) {
            return json_decode($existing['vector_json'], true) ?: [];
        }

        $vector = self::embed($text);
        $provider = self::lastProvider();
        if ($existing) {
            Database::query(
                'UPDATE embeddings SET provider = ?, vector_json = ?, text_hash = ?, metadata_json = ?, updated_at = NOW() WHERE id = ?',
                [$provider, json_encode($vector), $hash, json_encode(['chars' => mb_strlen($text)]), $existing['id']]
            );
        } else {
            Database::query(
                'INSERT INTO embeddings (embeddable_type, embeddable_id, provider, vector_json, text_hash, metadata_json, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
                [$type, $id, $provider, json_encode($vector), $hash, json_encode(['chars' => mb_strlen($text)])]
            );
        }
        return $vector;
    }

    public static function get(string $type, int $id): ?array
    {
        $row = Database::fetch(
            'SELECT vector_json FROM embeddings WHERE embeddable_type = ? AND embeddable_id = ?',
            [$type, $id]
        );
        if (!$row) {
            return null;
        }
        $v = json_decode($row['vector_json'] ?: '[]', true);
        return is_array($v) ? $v : null;
    }

    /** @return list<array{id:int,score:float}> */
    public static function similar(string $type, array $queryVector, int $limit = 20, ?int $excludeId = null): array
    {
        $rows = Database::fetchAll(
            'SELECT embeddable_id, vector_json FROM embeddings WHERE embeddable_type = ?',
            [$type]
        );
        $scored = [];
        foreach ($rows as $row) {
            $id = (int) $row['embeddable_id'];
            if ($excludeId !== null && $id === $excludeId) {
                continue;
            }
            $vec = json_decode($row['vector_json'] ?: '[]', true);
            if (!is_array($vec) || $vec === []) {
                continue;
            }
            $scored[] = ['id' => $id, 'score' => self::cosine($queryVector, $vec)];
        }
        usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $limit);
    }

    public static function recomputeAll(): int
    {
        $n = 0;
        foreach (Database::fetchAll('SELECT id, title, problem_statement, sector, category FROM challenges') as $c) {
            self::upsert('challenge', (int) $c['id'], $c['title'] . ' ' . $c['problem_statement'] . ' ' . $c['sector'] . ' ' . $c['category']);
            $n++;
        }
        foreach (Database::fetchAll('SELECT id, name, description, problem_solved, sector, stage FROM solutions') as $s) {
            self::upsert('solution', (int) $s['id'], $s['name'] . ' ' . $s['description'] . ' ' . ($s['problem_solved'] ?? '') . ' ' . $s['sector'] . ' ' . $s['stage']);
            $n++;
        }
        return $n;
    }

    /** @return list<float> */
    public static function embed(string $text): array
    {
        $remote = self::remoteEmbed($text);
        if ($remote !== null) {
            self::$lastProvider = 'remote';
            return $remote;
        }
        self::$lastProvider = 'local_tf';
        return self::localHashEmbed($text);
    }

    private static string $lastProvider = 'local_tf';

    public static function lastProvider(): string
    {
        return self::$lastProvider;
    }

    /** @return list<float>|null */
    private static function remoteEmbed(string $text): ?array
    {
        // Optional: Gemini embedding when key present
        $key = Env::get('GEMINI_API_KEY');
        if (!$key) {
            return null;
        }
        try {
            $model = Env::get('GEMINI_EMBED_MODEL', 'text-embedding-004');
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':embedContent?key=' . urlencode($key);
            $body = json_encode(['content' => ['parts' => [['text' => mb_substr($text, 0, 6000)]]]]);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT => 20,
            ]);
            $raw = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($raw === false || $code >= 400) {
                return null;
            }
            $decoded = json_decode($raw, true);
            $values = $decoded['embedding']['values'] ?? null;
            if (!is_array($values) || $values === []) {
                return null;
            }
            return array_map('floatval', $values);
        } catch (\Throwable $e) {
            Logger::log('warning', 'Remote embed failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /** @return list<float> */
    private static function localHashEmbed(string $text): array
    {
        $vec = array_fill(0, self::DIM, 0.0);
        $tokens = preg_split('/\W+/u', mb_strtolower($text)) ?: [];
        $stop = ['the','and','for','with','that','this','from','into','a','an','of','to','in','on','is','are','as','by','or','be'];
        $tf = [];
        foreach ($tokens as $t) {
            if ($t === '' || in_array($t, $stop, true) || mb_strlen($t) < 2) {
                continue;
            }
            $tf[$t] = ($tf[$t] ?? 0) + 1;
        }
        foreach ($tf as $token => $count) {
            $token = (string) $token;
            $h = unpack('N*', hash('sha256', $token, true));
            $idx = abs((int) ($h[1] ?? 0)) % self::DIM;
            $sign = ((int) ($h[2] ?? 0) & 1) ? 1.0 : -1.0;
            $vec[$idx] += $sign * (1.0 + log((float) $count));
        }
        return self::normalize($vec);
    }

    /** @param list<float> $a @param list<float> $b */
    public static function cosine(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) {
            return 0.0;
        }
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }
        if ($na <= 0.0 || $nb <= 0.0) {
            return 0.0;
        }
        return $dot / (sqrt($na) * sqrt($nb));
    }

    /** @param list<float> $v @return list<float> */
    private static function normalize(array $v): array
    {
        $norm = 0.0;
        foreach ($v as $x) {
            $norm += $x * $x;
        }
        $norm = sqrt($norm);
        if ($norm <= 0.0) {
            return $v;
        }
        return array_map(static fn ($x) => $x / $norm, $v);
    }
}
