<?php

/*
 * Front controller for DirectAdmin shared hosting.
 *
 * Replaces Laravel's public/index.php, which assumes the application sits one
 * directory up. Here the app lives in ~/biertappen/ while only this directory is
 * web-served, so .env, vendor/ and storage/ are not reachable over HTTP at all —
 * not even if a rewrite rule is misconfigured later.
 *
 * Adjust APP_BASE only if you upload the application somewhere other than
 * ~/biertappen/.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appBase = dirname(__DIR__).'/biertappen';

if (! is_dir($appBase)) {
    http_response_code(500);
    exit('Application directory not found. Check APP_BASE in public_html/index.php.');
}

// Maintenance mode, if the application was put into it.
if (file_exists($maintenance = $appBase.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appBase.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appBase.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
