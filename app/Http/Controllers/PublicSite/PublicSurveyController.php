<?php
declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Auth\Auth;
use App\Domain\SurveyEngine;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;

final class PublicSurveyController
{
    public function show(string $id): void
    {
        $survey = Database::fetch('SELECT * FROM surveys WHERE id = ? AND status = \'open\'', [(int) $id]);
        $token = (string) ($_GET['t'] ?? '');
        if (!$survey || !hash_equals(SurveyEngine::publicToken((int) $id), $token)) {
            // allow logged-in users without token
            if (!$survey || !Auth::check()) {
                http_response_code(404);
                View::make('public/404', ['title' => 'Survey not found']);
                return;
            }
        }
        $questions = Database::fetchAll('SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order, id', [(int) $id]);
        $logic = Database::fetchAll('SELECT * FROM survey_logic WHERE survey_id = ?', [(int) $id]);
        View::make('public/survey-take', [
            'title' => $survey['title'],
            'survey' => $survey,
            'questions' => $questions,
            'logic' => $logic,
            'token' => $token,
        ], 'layouts/public');
    }

    public function submit(string $id): void
    {
        Csrf::requireValid();
        $survey = Database::fetch('SELECT * FROM surveys WHERE id = ? AND status = \'open\'', [(int) $id]);
        if (!$survey) {
            Response::flash('error', 'Survey closed.');
            Response::redirect('/');
        }
        $userId = ((int) $survey['is_anonymous']) ? null : Auth::id();
        Database::query(
            'INSERT INTO survey_responses (survey_id, user_id, session_token, completed_at, created_at) VALUES (?, ?, ?, NOW(), NOW())',
            [(int) $id, $userId, bin2hex(random_bytes(8))]
        );
        $rid = (int) Database::lastId();
        $questions = Database::fetchAll('SELECT * FROM survey_questions WHERE survey_id = ?', [(int) $id]);
        $uploadDir = ZBIF_ROOT . '/storage/uploads/surveys/' . $rid;
        foreach ($questions as $q) {
            $key = 'q_' . $q['id'];
            if ($q['question_type'] === 'file') {
                if (!empty($_FILES[$key]['tmp_name']) && is_uploaded_file($_FILES[$key]['tmp_name'])) {
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $name = basename((string) $_FILES[$key]['name']);
                    $dest = $uploadDir . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
                    move_uploaded_file($_FILES[$key]['tmp_name'], $dest);
                    Database::query(
                        'INSERT INTO survey_answers (response_id, question_id, answer_text, created_at) VALUES (?, ?, ?, NOW())',
                        [$rid, $q['id'], 'surveys/' . $rid . '/' . basename($dest)]
                    );
                }
                continue;
            }
            $val = $_POST[$key] ?? null;
            if ($val === null) {
                continue;
            }
            $text = is_array($val) ? null : (string) $val;
            $json = is_array($val) ? json_encode($val) : null;
            $num = is_numeric($val) ? (float) $val : null;
            Database::query(
                'INSERT INTO survey_answers (response_id, question_id, answer_text, answer_json, numeric_value, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())',
                [$rid, $q['id'], $text, $json, $num]
            );
        }
        Response::flash('success', 'Thank you. Your response was recorded.');
        Response::redirect('/survey/' . $id . '/thanks?t=' . urlencode((string) ($_POST['t'] ?? '')));
    }

    public function thanks(string $id): void
    {
        View::make('public/survey-thanks', ['title' => 'Thank you', 'id' => $id], 'layouts/public');
    }
}
