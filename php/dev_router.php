<?php

declare(strict_types=1);

// Dev/test router: php -S localhost:8000 dev_router.php
// Maps clean URLs (/placements, /api/..., /uploads/...) to index.php.

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if ($path !== '/' && !preg_match('#^/(placements|api|uploads)#', $path) && is_file($file)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
