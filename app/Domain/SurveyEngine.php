<?php
declare(strict_types=1);

namespace App\Domain;

use App\Support\Database;

final class SurveyEngine
{
    public const TYPES = [
        'single' => 'Single choice',
        'multi' => 'Multi choice',
        'likert' => 'Likert 1-5',
        'nps' => 'NPS 0-10',
        'stars' => 'Star rating 1-5',
        'short_text' => 'Short text',
        'long_text' => 'Long text',
        'matrix' => 'Matrix',
        'file' => 'File upload',
    ];

    public static function createSurvey(array $data): int
    {
        Database::query(
            'INSERT INTO surveys (event_id, title, slug, audience, is_anonymous, status, opens_at, closes_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['event_id'],
                $data['title'],
                $data['slug'],
                $data['audience'] ?? 'all',
                !empty($data['is_anonymous']) ? 1 : 0,
                $data['status'] ?? 'draft',
                $data['opens_at'] ?? null,
                $data['closes_at'] ?? null,
            ]
        );
        return (int) Database::lastId();
    }

    public static function addQuestion(int $surveyId, array $q): int
    {
        Database::query(
            'INSERT INTO survey_questions (survey_id, question_type, prompt, options_json, is_required, sort_order, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [
                $surveyId,
                $q['question_type'],
                $q['prompt'],
                isset($q['options']) ? json_encode($q['options']) : null,
                !empty($q['is_required']) ? 1 : 0,
                (int) ($q['sort_order'] ?? 0),
            ]
        );
        return (int) Database::lastId();
    }

    public static function addLogic(int $surveyId, int $questionId, array $condition, ?int $jumpTo): void
    {
        Database::query(
            'INSERT INTO survey_logic (survey_id, question_id, condition_json, jump_to_question_id, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [$surveyId, $questionId, json_encode($condition), $jumpTo]
        );
    }

    /** @return array<string, mixed> */
    public static function analytics(int $surveyId): array
    {
        $questions = Database::fetchAll('SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order', [$surveyId]);
        $responses = (int) (Database::fetch('SELECT COUNT(*) AS c FROM survey_responses WHERE survey_id = ? AND completed_at IS NOT NULL', [$surveyId])['c'] ?? 0);
        $out = ['responses' => $responses, 'questions' => []];
        foreach ($questions as $q) {
            $qid = (int) $q['id'];
            $answers = Database::fetchAll('SELECT * FROM survey_answers WHERE question_id = ?', [$qid]);
            $item = [
                'id' => $qid,
                'prompt' => $q['prompt'],
                'type' => $q['question_type'],
                'avg' => null,
                'nps' => null,
                'distribution' => [],
                'word_frequency' => [],
                'sample_texts' => [],
            ];
            if (in_array($q['question_type'], ['nps', 'likert', 'stars'], true)) {
                $nums = array_filter(array_map(static fn ($a) => $a['numeric_value'] !== null ? (float) $a['numeric_value'] : null, $answers), static fn ($v) => $v !== null);
                if ($nums) {
                    $item['avg'] = array_sum($nums) / count($nums);
                    $dist = [];
                    foreach ($nums as $n) {
                        $k = (string) (int) $n;
                        $dist[$k] = ($dist[$k] ?? 0) + 1;
                    }
                    ksort($dist);
                    $item['distribution'] = $dist;
                }
                if ($q['question_type'] === 'nps' && $nums) {
                    $promoters = count(array_filter($nums, static fn ($n) => $n >= 9));
                    $detractors = count(array_filter($nums, static fn ($n) => $n <= 6));
                    $item['nps'] = round((($promoters - $detractors) / count($nums)) * 100, 1);
                }
            }
            if (in_array($q['question_type'], ['short_text', 'long_text'], true)) {
                $texts = array_filter(array_map(static fn ($a) => trim((string) ($a['answer_text'] ?? '')), $answers));
                $item['sample_texts'] = array_slice($texts, 0, 20);
                $freq = [];
                foreach ($texts as $t) {
                    foreach (preg_split('/\W+/u', mb_strtolower($t)) ?: [] as $w) {
                        if (mb_strlen($w) < 3) {
                            continue;
                        }
                        $freq[$w] = ($freq[$w] ?? 0) + 1;
                    }
                }
                arsort($freq);
                $item['word_frequency'] = array_slice($freq, 0, 25, true);
            }
            if (in_array($q['question_type'], ['single', 'multi'], true)) {
                $dist = [];
                foreach ($answers as $a) {
                    $vals = $a['answer_json'] ? (json_decode($a['answer_json'], true) ?: []) : [($a['answer_text'] ?? '')];
                    if (!is_array($vals)) {
                        $vals = [$vals];
                    }
                    foreach ($vals as $v) {
                        $v = (string) $v;
                        if ($v === '') {
                            continue;
                        }
                        $dist[$v] = ($dist[$v] ?? 0) + 1;
                    }
                }
                $item['distribution'] = $dist;
            }
            $out['questions'][] = $item;
        }
        // segment by role when user linked
        $byRole = Database::fetchAll(
            'SELECT r.slug AS role, COUNT(DISTINCT sr.id) AS c
             FROM survey_responses sr
             LEFT JOIN model_has_roles mhr ON mhr.user_id = sr.user_id
             LEFT JOIN roles r ON r.id = mhr.role_id
             WHERE sr.survey_id = ?
             GROUP BY r.slug',
            [$surveyId]
        );
        $out['by_role'] = $byRole;
        return $out;
    }

    public static function publicToken(int $surveyId): string
    {
        return hash_hmac('sha256', 'survey:' . $surveyId, \App\Support\Env::get('APP_KEY', 'zbif-local-key') ?: 'zbif-local-key');
    }
}
