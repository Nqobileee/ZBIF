<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use App\Notify\Notifier;
use App\Support\Database;

$users = Database::fetchAll(
    'SELECT u.id FROM users u
     LEFT JOIN notification_preferences p ON p.user_id = u.id
     WHERE u.is_active = 1 AND u.deleted_at IS NULL
       AND (p.digest_enabled IS NULL OR p.digest_enabled = 1)
     LIMIT 800'
);
foreach ($users as $u) {
    Notifier::queue('daily_digest', ['user_id' => (int) $u['id']]);
}
echo date('c') . ' queued ' . count($users) . " daily digests\n";
