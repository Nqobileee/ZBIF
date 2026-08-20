<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';

use App\Ai\EmbeddingService;
use App\Domain\MatchingService;
use App\Support\Database;

$n = EmbeddingService::recomputeAll();
$pubs = Database::fetchAll("SELECT id FROM challenges WHERE status IN ('published','allocated','in_development')");
foreach ($pubs as $p) {
    MatchingService::persistSuggestions((int) $p['id']);
}
echo date('c') . " embeddings refreshed for {$n} entities; matches recomputed for " . count($pubs) . " challenges\n";
