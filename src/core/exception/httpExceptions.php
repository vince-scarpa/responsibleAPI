<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\exception
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\exception;

use responsible\core\headers;

class httpException extends Exception
{
    /**
     * [__construct Use parent constructor]
     */
    public function __construct()
    {
        parent::__construct('');
    }
}
