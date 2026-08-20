<?php
declare(strict_types=1);

/**
 * Organiser / super-admin sign-in only. Not linked from the public site.
 * Canonical URL: /admin/login.php
 */
$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? ('?' . $_SERVER['QUERY_STRING'])
    : '';
$_SERVER['ZBIF_FORCE_URI'] = '/admin/login' . $query;
require dirname(__DIR__) . '/index.php';
