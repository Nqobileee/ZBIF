<?php
declare(strict_types=1);

/**
 * One-shot installer: creates tables from schema.sql then seeds.
 * Usage: php database/install.php
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Support\Database;
use App\Support\Env;

$host = Env::get('DB_HOST', '127.0.0.1');
$port = Env::get('DB_PORT', '3306');
$name = Env::get('DB_DATABASE', 'zbif');
$user = Env::get('DB_USERNAME', 'root');
$pass = Env::get('DB_PASSWORD', '');

$pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$name}`");

$sql = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
$pdo->exec($sql);
echo "Schema imported.\n";

passthru('php "' . dirname(__DIR__) . '/database/seeds/seed.php"', $code);
exit($code);
