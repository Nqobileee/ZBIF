<?php
declare(strict_types=1);

namespace App\Domain;

use App\Ai\EmbeddingService;
use App\Ai\Matchmaker;
use App\Support\Database;

final class MatchingService
{
    /** @return list<array<string, mixed>> */
    public static function ruleBasedForChallenge(int $challengeId, int $limit = 10): array
    {
        $challenge = Database::fetch('SELECT * FROM challenges WHERE id = ?', [$challengeId]);
        if (!$challenge) {
            return [];
        }
        $solutions = Database::fetchAll(
            'SELECT * FROM solutions WHERE is_published = 1 AND (challenge_id IS NULL OR challenge_id = ?) ORDER BY id DESC LIMIT 200',
            [$challengeId]
        );
        $scored = [];
        foreach ($solutions as $sol) {
            $score = 0.0;
            $reasons = [];
            if (strcasecmp((string) $sol['sector'], (string) $challenge['sector']) === 0
                || stripos((string) $sol['sector'], (string) $challenge['category']) !== false) {
                $score += 40;
                $reasons[] = 'Sector and category alignment';
            }
            if ($sol['challenge_id'] && (int) $sol['challenge_id'] === $challengeId) {
                $score += 30;
                $reasons[] = 'Solution already tied to this challenge';
            }
            if (in_array($sol['stage'], ['pilot', 'market_ready', 'scaling'], true)) {
                $score += 15;
                $reasons[] = 'Solution stage is commercially advanced';
            }
            $hay = strtolower($sol['name'] . ' ' . $sol['description'] . ' ' . ($sol['problem_solved'] ?? ''));
            $needle = strtolower($challenge['title'] . ' ' . $challenge['problem_statement']);
            $overlap = self::keywordOverlap($hay, $needle);
            if ($overlap > 0) {
                $score += min(15, $overlap * 3);
                $reasons[] = 'Shared keywords in problem and solution text';
            }
            if ($score <= 0) {
                continue;
            }
            $scored[] = [
                'challenge_id' => $challengeId,
                'solution_id' => (int) $sol['id'],
                'solution' => $sol,
                'score' => round($score, 3),
                'rationale' => implode('; ', $reasons),
                'source' => 'rule',
            ];
        }
        usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $limit);
    }

    /** @return list<array<string, mixed>> */
    public static function embeddingBasedForChallenge(int $challengeId, int $limit = 12): array
    {
        $challenge = Database::fetch('SELECT * FROM challenges WHERE id = ?', [$challengeId]);
        if (!$challenge) {
            return [];
        }
        $text = $challenge['title'] . ' ' . $challenge['problem_statement'] . ' ' . $challenge['sector'] . ' ' . $challenge['category'];
        $q = EmbeddingService::upsert('challenge', $challengeId, $text);
        // ensure solution embeddings exist (batch light)
        $solutions = Database::fetchAll('SELECT * FROM solutions WHERE is_published = 1 LIMIT 300');
        foreach ($solutions as $sol) {
            EmbeddingService::upsert(
                'solution',
                (int) $sol['id'],
                $sol['name'] . ' ' . $sol['description'] . ' ' . ($sol['problem_solved'] ?? '') . ' ' . $sol['sector']
            );
        }
        $similar = EmbeddingService::similar('solution', $q, $limit);
        $out = [];
        foreach ($similar as $row) {
            if ($row['score'] < 0.05) {
                continue;
            }
            $sol = Database::fetch('SELECT * FROM solutions WHERE id = ?', [$row['id']]);
            if (!$sol) {
                continue;
            }
            $pct = round($row['score'] * 100, 1);
            $out[] = [
                'challenge_id' => $challengeId,
                'solution_id' => (int) $sol['id'],
                'solution' => $sol,
                'score' => $pct,
                'rationale' => 'Semantic similarity ' . $pct . '% (' . EmbeddingService::lastProvider() . ' embeddings)',
                'source' => 'ai',
            ];
        }
        return $out;
    }

    public static function persistSuggestions(int $challengeId): void
    {
        $embed = self::embeddingBasedForChallenge($challengeId, 15);
        $rules = self::ruleBasedForChallenge($challengeId, 15);
        $merged = self::mergeScores($challengeId, $embed, $rules);
        $ai = Matchmaker::rankForChallenge($challengeId, $merged);
        $list = $ai !== [] ? $ai : ($merged !== [] ? $merged : $rules);
        foreach ($list as $row) {
            Database::query(
                'INSERT INTO matches (challenge_id, solution_id, score, rationale, source, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, \'suggested\', NOW(), NOW())
                 ON DUPLICATE KEY UPDATE score = VALUES(score), rationale = VALUES(rationale), source = VALUES(source), updated_at = NOW()',
                [$row['challenge_id'], $row['solution_id'], $row['score'], $row['rationale'], $row['source']]
            );
        }
    }

    public static function onChallengeSaved(int $challengeId): void
    {
        $c = Database::fetch('SELECT * FROM challenges WHERE id = ?', [$challengeId]);
        if (!$c) {
            return;
        }
        EmbeddingService::upsert('challenge', $challengeId, $c['title'] . ' ' . $c['problem_statement'] . ' ' . $c['sector'] . ' ' . $c['category']);
        if (in_array($c['status'], ['published', 'allocated', 'in_development', 'prioritised'], true)) {
            self::persistSuggestions($challengeId);
        }
    }

    public static function onSolutionSaved(int $solutionId): void
    {
        $s = Database::fetch('SELECT * FROM solutions WHERE id = ?', [$solutionId]);
        if (!$s) {
            return;
        }
        EmbeddingService::upsert('solution', $solutionId, $s['name'] . ' ' . $s['description'] . ' ' . ($s['problem_solved'] ?? '') . ' ' . $s['sector']);
        if (!empty($s['challenge_id'])) {
            self::persistSuggestions((int) $s['challenge_id']);
        } else {
            // refresh top published challenges lightly
            $pubs = Database::fetchAll("SELECT id FROM challenges WHERE status = 'published' ORDER BY updated_at DESC LIMIT 5");
            foreach ($pubs as $p) {
                self::persistSuggestions((int) $p['id']);
            }
        }
    }

    /**
     * @param list<array<string,mixed>> $embed
     * @param list<array<string,mixed>> $rules
     * @return list<array<string,mixed>>
     */
    private static function mergeScores(int $challengeId, array $embed, array $rules): array
    {
        $byId = [];
        foreach ($rules as $r) {
            $byId[$r['solution_id']] = $r;
        }
        foreach ($embed as $e) {
            $id = $e['solution_id'];
            if (!isset($byId[$id])) {
                $byId[$id] = $e;
                continue;
            }
            $byId[$id]['score'] = round(($byId[$id]['score'] * 0.45) + ($e['score'] * 0.55), 3);
            $byId[$id]['rationale'] = $byId[$id]['rationale'] . '; ' . $e['rationale'];
            $byId[$id]['source'] = 'ai';
            $byId[$id]['challenge_id'] = $challengeId;
        }
        $list = array_values($byId);
        usort($list, static fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($list, 0, 15);
    }

    private static function keywordOverlap(string $a, string $b): int
    {
        $ta = array_unique(array_filter(preg_split('/\W+/', $a) ?: []));
        $tb = array_unique(array_filter(preg_split('/\W+/', $b) ?: []));
        $stop = ['the', 'and', 'for', 'with', 'that', 'this', 'from', 'into', 'a', 'an', 'of', 'to', 'in', 'on'];
        $ta = array_diff($ta, $stop);
        $tb = array_diff($tb, $stop);
        return count(array_intersect($ta, $tb));
    }
}
