# ResponsibleAPI RESTfull API
Responsible API is a secure PHP RESTfull application that allows easy HTTP requests from\
internal or external requests fused by Json Web Tokens (JWT).\
[ResponsibleSDK](https://github.com/vince-scarpa/responsibleSDK "ResponsibleAPI software development kit") is recommended to connect to the ResponsibleAPI\
Features include:
1. **JWT**
    - to sign each request
    
2. **Rate limiting**
    - to limit requests to a timed window frame and options to allow unlimited requests
    
3. **The Leaky bucket algorithm**
    - used for request throttling

## Requirements
PHP 5.6.x, 7.x\
[Composer](https://getcomposer.org/doc/00-intro.md "Composer install")\
[ResponsibleSDK](https://github.com/vince-scarpa/responsibleSDK "ResponsibleAPI software development kit")

## Installation
Install composer if not already\
    https://getcomposer.org/doc/00-intro.md  

Install ResponsibleSDK [recommended]  
    https://github.com/vince-scarpa/responsibleSDK
    
```
$ cd <responsible sdk directory>
$ git clone https://github.com/vince-scarpa/responsibleAPI.git
$ composer install
```
## Setup
In order for the ResponsibleAPI to initiate we need to add some basic configurations
1. Import the example sql file
2. Use or Create a .config file
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

Edit your new .config file and paste the below code, change all credentials to reflect your storage\
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
```
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
To access the Responsible server via requests you'll need to run the ResponsibleSDK, this can be found @\
https://github.com/vince-scarpa/responsibleSDK


### Options
[TODO]



### Development
Version 1.2\
This repo is still in development, if you would like to be a contributor ping me a request.
