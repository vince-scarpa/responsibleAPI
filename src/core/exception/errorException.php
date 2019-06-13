<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsible.git
 *
 * @api Responible API
 * @package responsible\core\exception
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\exception;

use responsible\core\headers;

class errorException extends \Exception
{
    /**
     * [__construct Use parent constructor]
     */
    public function __construct()
    {
        parent::__construct('');
    }

    /**
     * [$ERRORS Error sets Default error messages for supported error codes]
     * @var array
     */
    private $ERRORS = array(

        'APPLICATION_ERROR' => array(
            'ERROR_CODE' => 404,
            'ERROR_STATUS' => 'APPLICATION_ERROR',
            'MESSAGE' => '',
        ),

        'NOT_EXTENDED' => array(
            'ERROR_CODE' => 510,
            'ERROR_STATUS' => 'API_ERROR',
            'MESSAGE' => '',
        ),

        'NO_CONTENT' => array(
            'ERROR_CODE' => 200,
            'ERROR_STATUS' => 'NO_CONTENT',
            'MESSAGE' => [
                'error' => 'empty',
                'description' => 'No results'
            ],
        ),

        'NOT_FOUND' => array(
            'ERROR_CODE' => 404,
            'ERROR_STATUS' => 'NOT_FOUND',
            'MESSAGE' => [
                'error' => 'not found',
                'description' => 'We could not find the resource you requested or the request was not found'
            ],
        ),

        'METHOD_NOT_ALLOWED' => array(
            'ERROR_CODE' => 405,
            'ERROR_STATUS' => 'METHOD_NOT_ALLOWED',
            'MESSAGE' => [
                'error' => 'not allowed',
                'description' => 'The method request provided is not allowed'
            ],
        ),

        'UNAUTHORIZED' => array(
            'ERROR_CODE' => 401,
            'ERROR_STATUS' => 'UNAUTHORIZED',
            'MESSAGE' => [
                'error' => 'denied',
                'description' => 'Permission denied'
            ],
        ),

        'BAD_REQUEST' => array(
            'ERROR_CODE' => 400,
            'ERROR_STATUS' => 'BAD_REQUEST',
            'MESSAGE' => [
                'error' => 'bad request',
                'description' => 'The request provided was Invalid'
            ],
        ),

        'TOO_MANY_REQUESTS' => array(
            'ERROR_CODE' => 429,
            'ERROR_STATUS' => 'TOO_MANY_REQUESTS',
            'MESSAGE' => [
                'error' => 'too many requests',
                'description' => 'Too Many Requests'
            ],
        ),
    );

    /**
     * [$ERROR_STATE Current error state]
     * @var array
     */
    private $ERROR_STATE = array();

    /**
     * [error]
     * @return [void]
     */
    public function error($type, $level = 'system')
    {
        if (!isset($this->ERRORS[$type])) {
            if (isset($this->ERRORS['MESSAGE'])) {
                $this->ERROR_STATE = array(
                    'ERROR_CODE' => 500,
                    'ERROR_STATUS' => $type,
                    'MESSAGE' => $this->ERRORS['MESSAGE'],
                );
                $this->throwError($level);
                return;
            }

            $this->ERROR_STATE = $this->ERRORS['APPLICATION_ERROR'];
            $this->ERROR_STATE['MESSAGE'] = "`" . $type . "`" . ' is not defined as an error code';
            $this->throwError($level);
            return;
        }

        $this->ERROR_STATE = $this->ERRORS[$type];
        $this->throwError($level);
    }

    /**
     * [message Custom message override]
     * @return [object / self]
     */
    public function message($message)
    {
        $this->ERRORS['MESSAGE'] = $message;
        return $this;
    }

    /**
     * [errorMessage]
     * @return [object / exit]
     */
    private function throwError($level)
    {
        (new headers\header)->setHeaders();

        http_response_code($this->ERROR_STATE['ERROR_CODE']);

        $message = isset($this->ERRORS['MESSAGE']) && !empty($this->ERRORS['MESSAGE'])
        ? $this->ERRORS['MESSAGE']
        : $this->ERROR_STATE['MESSAGE'];

        if ($level == 'normal') {
            echo json_encode(array(
                'ERROR_CODE' => $this->ERROR_STATE['ERROR_CODE'],
                'ERROR_STATUS' => $this->ERROR_STATE['ERROR_STATUS'],
                'MESSAGE' => $message,
            ), JSON_PRETTY_PRINT);
            exit;
        }

        if ($level == 'system') {
            echo json_encode(array(
                'ERROR_CODE' => $this->ERROR_STATE['ERROR_CODE'],
                'ERROR_STATUS' => $this->ERROR_STATE['ERROR_STATUS'],
                'MESSAGE' => $message,
            ), JSON_PRETTY_PRINT);
            exit;
        }
    }
}
