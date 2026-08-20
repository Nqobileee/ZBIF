<?php
declare(strict_types=1);

/**
 * Additive enterprise tables for existing installs.
 * Usage: php database/migrate_enterprise.php
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Support\Database;

$statements = [
    "CREATE TABLE IF NOT EXISTS testimonials (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      quote TEXT NOT NULL,
      author_name VARCHAR(120) NOT NULL,
      author_role VARCHAR(120) NULL,
      org_name VARCHAR(180) NULL,
      is_published TINYINT(1) NOT NULL DEFAULT 1,
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS foresight_insights (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      sector VARCHAR(120) NOT NULL,
      title VARCHAR(255) NOT NULL,
      summary TEXT NULL,
      body TEXT NOT NULL,
      horizon ENUM('near','mid','long') NOT NULL DEFAULT 'near',
      is_published TINYINT(1) NOT NULL DEFAULT 1,
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($statements as $sql) {
    Database::query($sql);
    echo "OK: " . substr($sql, 0, 60) . "...\n";
}

$hasT = (int) (Database::fetch('SELECT COUNT(*) AS c FROM testimonials')['c'] ?? 0);
if ($hasT === 0) {
    Database::query(
        'INSERT INTO testimonials (quote, author_name, author_role, org_name, is_published, sort_order, created_at) VALUES
         (?, ?, ?, ?, 1, 1, NOW()), (?, ?, ?, ?, 1, 2, NOW()), (?, ?, ?, ?, 1, 3, NOW())',
        [
            'ZBIF turned our plant efficiency challenge into a funded pilot within one forum cycle.',
            'Tendai Moyo', 'Operations Director', 'Midlands Manufacturing',
            'The Deal Room kept mentors, corporates, and our team aligned until the MOU was signed.',
            'Rudo Ncube', 'Founder', 'AgriSense Labs',
            'As an investor, match quality and structured outcomes beat another networking app.',
            'James Chirwa', 'Partner', 'Savanna Capital',
        ]
    );
    echo "Seeded testimonials.\n";
}

$hasF = (int) (Database::fetch('SELECT COUNT(*) AS c FROM foresight_insights')['c'] ?? 0);
if ($hasF === 0) {
    $rows = [
        ['Manufacturing', 'Predictive maintenance for SME plants', 'Sensors plus local analytics cut downtime.', 'Near-term adoption of low-cost IoT kits with local integrators can lift OEE without full Industry 4.0 spend.', 'near'],
        ['Agriculture', 'Climate-smart input financing', 'Bundle weather data with working capital.', 'Fintech and agritech partnerships can price risk dynamically for communal and commercial farmers.', 'mid'],
        ['Mining', 'Artisanal formalisation rails', 'Traceability that unlocks off-take.', 'Digital ledgers and assay-linked payments create pathways from informal sites into compliant supply chains.', 'mid'],
        ['Banking and Fintech', 'Embedded trade finance', 'Marketplace data powers credit.', 'InnovaMatch deal outcomes become alternative data for invoice and PO financing products.', 'near'],
        ['Energy', 'Mini-grid orchestration', 'Match load to local generation.', 'Hybrid solar-diesel operators need software that dispatches community loads and industrial offtakers.', 'long'],
    ];
    foreach ($rows as $i => $r) {
        Database::query(
            'INSERT INTO foresight_insights (sector, title, summary, body, horizon, is_published, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())',
            [$r[0], $r[1], $r[2], $r[3], $r[4], $i + 1]
        );
    }
    echo "Seeded foresight insights.\n";
}

echo "Enterprise migration complete.\n";
