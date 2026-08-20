<?php
declare(strict_types=1);

/**
 * Run all incremental migrations (safe to re-run).
 * Usage: php database/migrate_all.php
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Support\Database;
use App\Support\Env;
use App\Support\Settings;

$scripts = [
    'migrate_enterprise.php',
    'migrate_enterprise2.php',
    'migrate_enterprise3.php',
    'migrate_auth.php',
    'migrate_exhibit.php',
    'migrate_secure.php',
];

foreach ($scripts as $script) {
    $path = __DIR__ . '/' . $script;
    if (!is_file($path)) {
        echo "SKIP missing: {$script}\n";
        continue;
    }
    echo "\n=== {$script} ===\n";
    passthru('php "' . $path . '"', $code);
    if ($code !== 0) {
        fwrite(STDERR, "Migration failed: {$script} (exit {$code})\n");
        exit($code);
    }
}

Settings::ensureTable();
echo "\nOK: system_settings ready\n";

// Quick integrity report
$db = Env::get('DB_DATABASE', 'zbif');
$tables = Database::fetchAll(
    'SELECT TABLE_NAME AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
    [$db]
);
echo 'Tables in ' . $db . ': ' . count($tables) . "\n";
echo "All migrations complete.\n";
