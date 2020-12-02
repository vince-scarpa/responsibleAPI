<?php
/**
 * To run tests regularly
 * vendor/bin/phpunit tests/
 * vendor/bin/phpunit --verbose -c phpunit.xml
 *
 * With xdebug installed
 * vendor/bin/phpunit --dump-xdebug-filter tests/build/xdebug-filter.php
 * vendor/bin/phpunit --verbose -c phpunit.xml
 */

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

session_start();

require_once __DIR__ . '/options.php';

// Include the composer autoloader
$loader = require __DIR__ . '/../vendor/autoload.php';