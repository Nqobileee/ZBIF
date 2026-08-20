<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
$n = App\Jobs\Worker::run(50);
echo date('c') . " processed {$n} jobs\n";
