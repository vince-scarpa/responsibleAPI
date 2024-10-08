[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vince-scarpa/responsibleAPI/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vince-scarpa/responsibleAPI/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/vince-scarpa/responsibleAPI/badges/build.png?b=master)](https://scrutinizer-ci.com/g/vince-scarpa/responsibleAPI/build-status/master)
![Packagist Downloads](https://img.shields.io/packagist/dm/vince-scarpa/responsibleapi)
# ResponsibleAPI RESTful API
Responsible API is a secure PHP RESTful application that allows easy HTTP requests from\
external requests fused by Json Web Tokens (JWT).\
[ResponsibleSDK](https://github.com/vince-scarpa/responsibleSDK "ResponsibleAPI software development kit") is recommended to connect to the ResponsibleAPI\

### Development
Version 1.4\
This repo is open for contributions.

Features include:
1. **JWT**
    - to sign each request
    
2. **Rate limiting**
    - to limit requests to a timed window frame and options to allow unlimited requests
    
3. **The Leaky bucket algorithm**
    - used for request throttling

## Requirements
PHP 7.x\
[Composer](https://getcomposer.org/doc/00-intro.md "Composer install")\
[ResponsibleSDK](https://github.com/vince-scarpa/responsibleSDK "ResponsibleAPI software development kit") [recommended]

## Installation
Install composer if not already\
    https://getcomposer.org/doc/00-intro.md  

Install ResponsibleSDK [recommended for quick example]  
    https://github.com/vince-scarpa/responsibleSDK
    
```
$ cd <responsible sdk directory>
$ git clone https://github.com/vince-scarpa/responsibleAPI.git
$ composer install
```
## Setup
In order for the ResponsibleAPI to initiate we need to add some basic configurations
1. Import the example sql file
2. Use or Create a `.config` file
### Setup your storage
The ResponsibleAPI uses MySQL as Database storage, run the supplied `responsible.sql` file to install the required tables

### Setup a config file
If you didn't get a `.config` file shipped in this repo then you'll need to create one.\
In the repo root directory run the following or anyway you know to create and write to files
```
$ cd <responsible sdk directory>
$ mkdir config
$ touch config/.config
```
Your file structure should look like this
* responsibleAPI
    * config
        * .config
    * src
        * (...)

Edit your new `.config` file and paste the below code, change all credentials to reflect your storage\
Note: The MASTER_KEY is the global "secret key" for system use to sign requests.\
To generate a strong secret I recommend using a strong password generator, this has no length limit but do note you probably want to stick to 32bytes. The only tested characters are;
```
ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789`-=~!@#$%^&*()_+,./?;[]{}\|
```

```
DB_TYPE = 'mysql'
DB_PORT = '3306'
DB_NAME = '<YOUR_DB_NAME>'
DB_USER = '<YOUR_DB_USER_NAME>'
DB_PASSWORD = '<YOUR_DB_PASSWORD>'
DB_HOST = '<YOUR_DB_HOST>'

MASTER_KEY = '<YOUR_MASTER_SECRET>'
```

## Basic usage
#### Setting up our API server
```php
<?php
/**
 * Composer Autoloader
 */
require __DIR__ . '/responsibleAPI/vendor/autoload.php';

/**
 * Load Responsible API core
 */
require __DIR__ . '/responsibleAPI/src/responsible.php';

/**
 * Load Responsible API core
 */
use responsible\responsible;

// Initiate the API with no options, let ResponsibleAPI take care of all defaults
$responsible = responsible::API();

// Print the response
$responsible::response(true);
```
What does the above example give us? well, nothing really...\
Just a `permissions denied` message and a server waiting for real requests.\
To access the ResponsibleAPI server via requests you'll need to run the ResponsibleSDK, this can be found @\
https://github.com/vince-scarpa/responsibleSDK or any other HTTP request method you're comfortable with


### Options
Add options

```php
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
        ['x-my-new-responsible-api', '1.2'],
    ),

    /**
     * JWT refresh options
     */
    'jwt' => [
        'leeway' => 10, // n seconds leeway for expiry
        'issuedAt' => time(),
        'expires' => time() + 300,
        'notBeFor' => 'issuedAt', // issuedAt, or e.g (time()+10)
    ],

    /**
     * Rate limiter
     */
    'rateLimit' => 1000, // API call Limit
    'rateWindow' => 'MINUTE', // Window timeframe SECOND, MINUTE, HOUR, DAY, [CUSTOM/ A POSITIVE INTEGER]

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
     * Customise a middle ware class directory
     */
    'classRoute' => [
        'directory' => __DIR__ . '/src/service/endpoints', // ABSOLUTE_PATH_STRING, __DIR__ ect...,
        'namespace' => 'backmagic',
    ]
);
$responsible = responsible::API($options);
```
