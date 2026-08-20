<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use App\Notify\Notifier;
use App\Support\Database;

$upcoming = Database::fetchAll(
    "SELECT * FROM meetings WHERE status IN ('accepted','rescheduled','pending') AND starts_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)"
);
foreach ($upcoming as $m) {
    Notifier::queue('meeting_reminder', ['meeting_id' => $m['id']]);
}

$openSurveys = Database::fetchAll("SELECT id, title FROM surveys WHERE status = 'open'");
$surveyQueued = 0;
foreach ($openSurveys as $s) {
    // soft nudge: queue one digest job per open survey (worker expands if needed)
    Notifier::queue('survey_reminder', ['survey_id' => (int) $s['id']]);
    $surveyQueued++;
}

echo date('c') . ' queued ' . count($upcoming) . ' meeting reminders, ' . $surveyQueued . " survey reminders\n";
