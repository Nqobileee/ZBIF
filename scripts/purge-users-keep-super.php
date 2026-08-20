<?php
declare(strict_types=1);

/**
 * Delete all users except super@zbif.test / super_admin.
 * Usage: php scripts/purge-users-keep-super.php
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Support\Database;

$pdo = Database::pdo();

$super = Database::fetch("SELECT id, email FROM users WHERE email = 'super@zbif.test' LIMIT 1");
if (!$super) {
    $super = Database::fetch(
        "SELECT u.id, u.email
         FROM users u
         INNER JOIN model_has_roles mhr ON mhr.user_id = u.id
         INNER JOIN roles r ON r.id = mhr.role_id
         WHERE r.slug = 'super_admin'
         ORDER BY u.id
         LIMIT 1"
    );
}

if (!$super) {
    fwrite(STDERR, "No superadmin found (expected super@zbif.test).\n");
    exit(1);
}

$superId = (int) $super['id'];
$superEmail = (string) $super['email'];
echo "Keeping superadmin #{$superId} <{$superEmail}>\n";

$before = (int) (Database::fetch('SELECT COUNT(*) AS c FROM users')['c'] ?? 0);
echo "Users before: {$before}\n";

$fks = Database::fetchAll(
    "SELECT k.TABLE_NAME AS t, k.COLUMN_NAME AS c, col.IS_NULLABLE AS n
     FROM information_schema.KEY_COLUMN_USAGE k
     INNER JOIN information_schema.COLUMNS col
       ON col.TABLE_SCHEMA = k.TABLE_SCHEMA
      AND col.TABLE_NAME = k.TABLE_NAME
      AND col.COLUMN_NAME = k.COLUMN_NAME
     WHERE k.TABLE_SCHEMA = DATABASE()
       AND k.REFERENCED_TABLE_NAME = 'users'
       AND k.REFERENCED_COLUMN_NAME = 'id'
       AND k.TABLE_NAME <> 'users'
     ORDER BY k.TABLE_NAME, k.COLUMN_NAME"
);

$pdo->beginTransaction();
try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach ($fks as $fk) {
        $table = (string) $fk['t'];
        $col = (string) $fk['c'];
        if (($fk['n'] ?? '') === 'YES') {
            Database::query("UPDATE `{$table}` SET `{$col}` = NULL WHERE `{$col}` IS NOT NULL AND `{$col}` <> ?", [$superId]);
            echo "Nulled {$table}.{$col}\n";
        } else {
            Database::query("DELETE FROM `{$table}` WHERE `{$col}` <> ?", [$superId]);
            echo "Cleared {$table}.{$col}\n";
        }
    }

    foreach (['otp_codes', 'magic_links'] as $table) {
        $hasEmail = Database::fetch(
            'SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$table, 'email']
        );
        if ((int) ($hasEmail['c'] ?? 0) === 0) {
            continue;
        }
        Database::query("DELETE FROM `{$table}` WHERE email IS NOT NULL AND email <> ?", [$superEmail]);
    }

    Database::query('DELETE FROM users WHERE id <> ?', [$superId]);
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $pdo->commit();
} catch (Throwable $e) {
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    } catch (Throwable $ignored) {
    }
    $pdo->rollBack();
    fwrite(STDERR, 'Failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$after = (int) (Database::fetch('SELECT COUNT(*) AS c FROM users')['c'] ?? 0);
$left = Database::fetchAll('SELECT id, email, status FROM users');
echo "Users after: {$after}\n";
foreach ($left as $u) {
    echo " - #{$u['id']} {$u['email']} ({$u['status']})\n";
}
echo "Done.\n";
