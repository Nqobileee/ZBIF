<?php
declare(strict_types=1);

define('ZBIF_ROOT', __DIR__);
define('ZBIF_START', microtime(true));

require ZBIF_ROOT . '/app/Support/Autoloader.php';
App\Support\Autoloader::register(ZBIF_ROOT . '/app', 'App\\');

App\Support\Env::load(ZBIF_ROOT . '/.env');

date_default_timezone_set(App\Support\Env::get('APP_TIMEZONE', 'Africa/Harare'));

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443)
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
        || App\Support\Env::get('APP_FORCE_HTTPS', 'false') === 'true';
    session_name(App\Support\Env::get('SESSION_NAME', 'zbif_session'));
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => $https,
    ]);
}

App\Support\RequestId::ensure();
App\Support\I18n::boot();
