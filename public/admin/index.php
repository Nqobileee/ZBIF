<?php
declare(strict_types=1);

/**
 * Physical /public/admin/ directory would otherwise cause nginx 403 on /admin/.
 * Route through the front controller as /admin (which redirects to /dashboard).
 */
$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? ('?' . $_SERVER['QUERY_STRING'])
    : '';
$_SERVER['ZBIF_FORCE_URI'] = '/admin' . $query;
require dirname(__DIR__) . '/index.php';
