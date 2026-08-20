<?php
declare(strict_types=1);

/**
 * Public participant / company sign-in entry (shared hosting friendly).
 * Canonical URL: /login.php
 */
$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? ('?' . $_SERVER['QUERY_STRING'])
    : '';
$_SERVER['ZBIF_FORCE_URI'] = '/login' . $query;
require __DIR__ . '/index.php';
