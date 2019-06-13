<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===================================================
// Composer Autoloader
// ===================================================
require dirname(__DIR__) . '/responsible/vendor/autoload.php';

// ===================================================
// Load Responsible core API
// ===================================================
require dirname(__DIR__) . '/responsible/src/responsible/responsible.php';

use responsible\responsible;

/**
 * Ceate a new user
 */
$options = array(
    'jwt' => [
        'issuedAt' => time(),
        'expires' => time() + 86400, // Default 86400
        'notBeFor' => time() + 10,
    ]
);
$responsibleUser = responsible::createUser(
    'Vince 1978',         // Unique user name
    'vinnie@example.com',  // Unique email address
    $options
);

print_r($responsibleUser);