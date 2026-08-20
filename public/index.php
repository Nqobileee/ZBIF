<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

App\Support\SecurityHeaders::apply();

$router = require dirname(__DIR__) . '/routes/web.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['ZBIF_FORCE_URI'] ?? ($_SERVER['REQUEST_URI'] ?? '/');

try {
    $router->dispatch($method, $uri);
} catch (Throwable $e) {
    App\Support\Logger::log('error', $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    http_response_code(500);
    if (App\Support\Env::get('APP_DEBUG') === 'true') {
        echo '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
    } else {
        echo 'Something went wrong. Please try again.';
    }
}
