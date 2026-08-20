<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Support\Database;
use App\Support\Env;

function tableExists(string $table): bool
{
    $db = Env::get('DB_DATABASE', 'zbif');
    $row = Database::fetch(
        'SELECT COUNT(*) AS c FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
        [$db, $table]
    );
    return (int) ($row['c'] ?? 0) > 0;
}

if (!tableExists('exhibit_applications')) {
    Database::query(
        "CREATE TABLE exhibit_applications (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_id BIGINT UNSIGNED NOT NULL,
          event_id BIGINT UNSIGNED NULL,
          organisation_name VARCHAR(255) NOT NULL,
          contact_name VARCHAR(180) NOT NULL,
          contact_email VARCHAR(255) NOT NULL,
          contact_phone VARCHAR(40) NULL,
          exhibit_type ENUM('product','service','demo','startup_booth','university_showcase','other') NOT NULL DEFAULT 'product',
          title VARCHAR(255) NOT NULL,
          description TEXT NOT NULL,
          launching_at_forum TINYINT(1) NOT NULL DEFAULT 0,
          space_preference VARCHAR(120) NULL,
          website VARCHAR(255) NULL,
          status ENUM('submitted','under_review','approved','waitlisted','declined') NOT NULL DEFAULT 'submitted',
          admin_notes TEXT NULL,
          created_at DATETIME NOT NULL,
          updated_at DATETIME NOT NULL,
          INDEX idx_exhibit_user (user_id),
          INDEX idx_exhibit_status (status),
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "OK: exhibit_applications\n";
} else {
    echo "SKIP: exhibit_applications exists\n";
}
echo "Done.\n";
