<?php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Auth\Auth;
use App\Domain\EventContext;
use App\Domain\SurveyEngine;
use App\Rbac\Gate;
use App\Support\Csrf;
use App\Support\Database;
use App\Support\Response;
use App\Support\Str;
use App\Support\View;

final class SurveyAdminController
{
    private function guard(): void
    {
        Auth::requireAdmin();
        Gate::authorize('surveys.manage');
    }

    public function edit(string $id): void
    {
        $this->guard();
        $survey = Database::fetch('SELECT * FROM surveys WHERE id = ?', [(int) $id]);
        if (!$survey) {
            http_response_code(404);
            return;
        }
        View::make('admin/survey-edit', [
            'title' => 'Edit survey',
            'survey' => $survey,
            'questions' => Database::fetchAll('SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order, id', [(int) $id]),
            'logic' => Database::fetchAll('SELECT * FROM survey_logic WHERE survey_id = ?', [(int) $id]),
            'types' => SurveyEngine::TYPES,
            'publicUrl' => \App\Support\Url::to('/survey/' . $id . '?t=' . SurveyEngine::publicToken((int) $id)),
        ], 'layouts/admin');
    }

    public function addQuestion(string $id): void
    {
        $this->guard();
        Csrf::requireValid();
        $type = $_POST['question_type'] ?? 'short_text';
        if (!isset(SurveyEngine::TYPES[$type])) {
            $type = 'short_text';
        }
        $options = null;
        if (in_array($type, ['single', 'multi', 'matrix'], true)) {
            $raw = trim($_POST['options'] ?? '');
            $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\r/', $raw) ?: [])));
            if ($type === 'matrix') {
                $options = [
                    'rows' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\r/', $_POST['matrix_rows'] ?? '') ?: []))),
                    'cols' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\r/', $_POST['matrix_cols'] ?? '') ?: []))),
                ];
            }
        }
        $sort = (int) (Database::fetch('SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM survey_questions WHERE survey_id = ?', [(int) $id])['n'] ?? 1);
        SurveyEngine::addQuestion((int) $id, [
            'question_type' => $type,
            'prompt' => trim($_POST['prompt'] ?? 'Question'),
            'options' => $options,
            'is_required' => isset($_POST['is_required']),
            'sort_order' => $sort,
        ]);
        Response::redirect('/admin/surveys/' . $id . '/edit');
    }

    public function addLogic(string $id): void
    {
        $this->guard();
        Csrf::requireValid();
        SurveyEngine::addLogic(
            (int) $id,
            (int) $_POST['question_id'],
            [
                'op' => $_POST['op'] ?? 'equals',
                'value' => $_POST['value'] ?? '',
            ],
            ($_POST['jump_to_question_id'] ?? '') !== '' ? (int) $_POST['jump_to_question_id'] : null
        );
        Response::redirect('/admin/surveys/' . $id . '/edit');
    }

    public function publish(string $id): void
    {
        $this->guard();
        Csrf::requireValid();
        Database::query('UPDATE surveys SET status = ?, updated_at = NOW() WHERE id = ?', [$_POST['status'] ?? 'open', (int) $id]);
        Response::flash('success', 'Survey status updated.');
        Response::redirect('/admin/surveys/' . $id . '/edit');
    }

    public function deliver(string $id): void
    {
        $this->guard();
        Csrf::requireValid();
        $survey = Database::fetch('SELECT * FROM surveys WHERE id = ?', [(int) $id]);
        $channel = $_POST['channel'] ?? 'in_app';
        $role = trim($_POST['role'] ?? '');
        $users = $role !== ''
            ? Database::fetchAll(
                'SELECT u.* FROM users u INNER JOIN model_has_roles mhr ON mhr.user_id = u.id INNER JOIN roles r ON r.id = mhr.role_id WHERE r.slug = ? AND u.deleted_at IS NULL',
                [$role]
            )
            : Database::fetchAll('SELECT * FROM users WHERE is_active = 1 AND deleted_at IS NULL LIMIT 400');
        $url = \App\Support\Url::to('/survey/' . $id . '?t=' . SurveyEngine::publicToken((int) $id));
        foreach ($users as $u) {
            if ($channel === 'email') {
                \App\Notify\Notifier::sendEmail((string) $u['email'], $survey['title'], '<p>Please complete this ZBIF survey:</p><p><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($url) . '</a></p>');
            } elseif ($channel === 'sms' && !empty($u['phone'])) {
                \App\Notify\Notifier::sendSms((string) $u['phone'], 'ZBIF survey: ' . $url);
            } else {
                \App\Notify\Notifier::inApp((int) $u['id'], 'Survey invitation', $survey['title'], '/app/surveys/' . $id);
                \App\Notify\Notifier::queue('survey_reminder', ['user_id' => $u['id'], 'survey_id' => (int) $id]);
            }
        }
        Response::flash('success', 'Survey delivered to ' . count($users) . ' people via ' . $channel . '.');
        Response::redirect('/admin/surveys/' . $id . '/edit');
    }

    public function richResults(string $id): void
    {
        $this->guard();
        $survey = Database::fetch('SELECT * FROM surveys WHERE id = ?', [(int) $id]);
        $analytics = SurveyEngine::analytics((int) $id);
        View::make('admin/survey-analytics', [
            'title' => 'Survey analytics',
            'survey' => $survey,
            'analytics' => $analytics,
        ], 'layouts/admin');
    }
}
