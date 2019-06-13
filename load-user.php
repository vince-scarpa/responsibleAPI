<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===================================================
// Composer Autoloader
// ===================================================
require dirname(__DIR__) . '/vendor/autoload.php';

// ===================================================
// Load Responsible core API
// ===================================================
require dirname(__DIR__) . '/src/responsible/responsible.php';

use responsible\responsible;

/**
 * Load a user account
 */
$options = array(
    'jwt' => [
        'issuedAt' => time(),
        'expires' => time() + 600,
        'notBeFor' => time() + 10,
    ],
    'loadBy' => 'email'
);
$responsibleUser = responsible::loadUser('vinnie@example.com', $options);

print_r($responsibleUser);
