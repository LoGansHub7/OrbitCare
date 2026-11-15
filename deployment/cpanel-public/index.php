<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
 * cPanel document root: /home/CPANEL_USER/public_html/zasn
 * Laravel application:  /home/CPANEL_USER/orbitcare_app
 *
 * Keep this file in the document root. The application itself remains outside
 * public_html so its .env, vendor, and storage folders are not web-accessible.
 */
$appRoot = dirname(__DIR__, 2).'/orbitcare_app';

if (file_exists($maintenance = $appRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
