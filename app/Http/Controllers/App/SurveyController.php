<?php
declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Auth\Auth;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;

final class SurveyController
{
    public function index(): void
    {
        Auth::requireLogin();
        View::make('app/surveys/index', [
            'title' => 'Surveys',
            'hidePageHead' => true,
        ], 'layouts/app');
    }

    public function show(string $id): void
    {
        Auth::requireLogin();
        $survey = Database::fetch('SELECT * FROM surveys WHERE id = ?', [(int) $id]);
        if (!$survey) {
            http_response_code(404);
            View::make('public/404', ['title' => 'Not found'], 'layouts/app');
            return;
        }
        $questions = Database::fetchAll('SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order', [(int) $id]);
        $logic = Database::fetchAll('SELECT * FROM survey_logic WHERE survey_id = ?', [(int) $id]);
        View::make('app/surveys/show', [
            'title' => $survey['title'],
            'survey' => $survey,
            'questions' => $questions,
            'logic' => $logic,
        ], 'layouts/app');
    }

    public function submit(string $id): void
    {
        Auth::requireLogin();
        Csrf::requireValid();
        $survey = Database::fetch('SELECT * FROM surveys WHERE id = ?', [(int) $id]);
        $userId = $survey && ((int) $survey['is_anonymous']) ? null : Auth::id();
        Database::query(
            'INSERT INTO survey_responses (survey_id, user_id, completed_at, created_at) VALUES (?, ?, NOW(), NOW())',
            [(int) $id, $userId]
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
                    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string) $_FILES[$key]['name']));
                    $dest = $uploadDir . '/' . $name;
                    move_uploaded_file($_FILES[$key]['tmp_name'], $dest);
                    Database::query(
                        'INSERT INTO survey_answers (response_id, question_id, answer_text, created_at) VALUES (?, ?, ?, NOW())',
                        [$rid, $q['id'], 'surveys/' . $rid . '/' . $name]
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
        Response::flash('success', 'Thank you for your feedback.');
        Response::redirect('/app/surveys');
    }
}
