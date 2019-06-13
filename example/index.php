<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Australia/Melbourne');

// ===================================================
// Composer Autoloader
// ===================================================
require __DIR__ . '/vendor/autoload.php';

// ===================================================
// Load Responsible API core
// ===================================================
require __DIR__ . '/src/responsible.php';

// ===================================================
// Use Responsible API core
// ===================================================
use responsible\responsible;

/**
 * [$options Setup Responsible API options]
 * @var array
 */
$options = array(
    /**
     * Output type
     */
    'requestType' => 'json', // json, array, object, xml, html, debug

    /**
     * Headers
     */
    // Max control age header "Access-Control-Max-Age"
    'maxWindow' => 86400, // Defaults to 3600 (1 hour)

    /**
     * Add custom headers
     * Custom headers must be create in the below format
     * nothing will happen if not, no errors no added headers
     */
    'addHeaders' => array(
        ['X-MY-CUSTOM-APP-HEADER', 'my custom app value'],
        ['X-MY-CUSTOM-HEADER', 'Another custom value'],
    ),

    /**
     * JWT refresh options
     */
    'jwt' => [
        'leeway' => 10, // n seconds leeway for expiry
        'issuedAt' => time(),
        'expires' => time() + 3600,
        'notBeFor' => 'issuedAt', // issuedAt, or e.g (time()+10)
    ],

    /**
     * Rate limiter
     */
    'rateLimit' => 10, // API call Limit
    'rateWindow' => 'MINUTE', // Window timeframe
    // 'rateWindow' => 10, // nth Seconds

    /**
     * --- Warning ---
     *
     * This will override any rate limits
     * No maximum calls will be set and the Responsible API
     * can run for as many calls and as often as you like
     * This should only be used for system admin calls
     */
    'unlimited' => false, // Unlimited API calls true / false or don't include to default to false

    /**
     * Leaky bucket
     *
     */
    'leak' => true, // Use token bucket "defaults to true"
    'leakRate' => 'default', // slow, medium, normal, default, fast or custom positive integer

    /**
     * --- Warning ---
     * User
     * [TODO]
     */
    // 'userType' => 'anonymous',
);

$responsible = responsible::API($options);
$responsible::response(true);

// print_r($responsible->getDefaults());
// print_r($responsible->getOptions());
// print_r($responsible->getConfig());
// print_r($responsible->Router());
// $responsible->responseData();

/**
 * OPTIONS
 *
 * 1. Headers
 *      - requestType [array | object | json | xml | html]
 *      - maxWindow [positive integer]
 *      - addHeaders [array | [{key,value}, {key,value}] ]
 *      -
 *
 * 2. JWT
 *      - leeway [integer]
 *
 *
 * 3. Rate limiter
 *      - rateLimit [integer]
 *      - rateWindow [SECOND | MINUTE | HOUR | positive integer]
 *      - leak [boolean]
 *      - leakRate [slow | medium | normal | default | positive integer]
 *      - unlimited [boolean]
 *
 *
 * 4. User access accounts
 *      - userType [anonymous]
 */
