<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\headers
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\headers;

use responsible\core\encoder;
use responsible\core\exception;
use responsible\core\server;
use responsible\core\user;
use responsible\core\auth;

class header
{
    /**
     * Max age constant
     */
    const MAX_WINDOW = 3600;

    /**
     * [$options Responsible API options]
     * @var [array]
     */
    private $options = [];

    /**
     * [$REQUEST_APPLICATION]
     * @var array
     */
    private $REQUEST_APPLICATION = array(
        'xml' => 'text/xml',
        'json' => 'application/json',
        'html' => 'text/html',
        'array' => 'text/plain',
        'object' => 'text/plain',
    );

    /**
     * [$REQUEST_TYPE / Default is json]
     * @var string
     */
    private $REQUEST_TYPE;

    /**
     * [$REQUEST_METHOD]
     * @var string
     */
    private $REQUEST_METHOD = [];

    /**
     * [$HEADER_STATUS]
     * @var array
     */
    private $HEADER_STATUS = [];

    /**
     * [requestType]
     * @return [void]
     */
    public function requestType($type = 'json')
    {
        $this->REQUEST_TYPE = $type;
    }

    /**
     * [getRequestType]
     * @return [string]
     */
    public function getRequestType()
    {
        return $this->REQUEST_TYPE;
    }

    /**
     * [requestMethod Set and return the request method]
     * @return [object]
     */
    public function requestMethod()
    {
        switch (strtolower($_SERVER['REQUEST_METHOD'])) {

            case 'get':
                $this->REQUEST_METHOD = ['method' => 'get', 'data' => $_GET];
                break;

            case 'post':
                $this->REQUEST_METHOD = ['method' => 'post', 'data' => $_POST];
                break;

            case 'options':
                $this->REQUEST_METHOD = ['method' => 'options', 'data' => $_POST];
                break;

            case 'put':
                $_PARSE = parse_str(
                    file_get_contents(
                        'php://input',
                        false,
                        null,
                        -1,
                        $_SERVER['CONTENT_LENGTH']
                    ),
                    $_PUT
                );
                $this->REQUEST_METHOD = ['method' => 'put', 'data' => $_PARSE];
                break;

            case 'patch':
                # [TODO]
                $this->REQUEST_METHOD = ['method' => 'patch', 'data' => []];
                break;

            case 'delete':
                # [TODO]
                $this->REQUEST_METHOD = ['method' => 'delete', 'data' => []];
                break;

            default:
                $this->REQUEST_METHOD = [];
                break;
        }
    }

    /**
     * [getMethod Get the request method]
     * @return [object]
     */
    public function getMethod()
    {
        return (object) $this->REQUEST_METHOD;
    }

    /**
     * [setAllowedMethods Set the allowed methods for endpoints]
     * @param array $methods [GET, POST, PUT, PATCH, DELETE, ect..]
     */
    public function setAllowedMethods(array $methods)
    {
        $this->setHeader('Access-Control-Allow-Methods', array(
            implode(',', $methods),
        ));

        $requestMethod = $this->getServerMethod();
        if (!in_array($requestMethod, $methods)) {
            (new exception\errorException)->error('METHOD_NOT_ALLOWED');
        }
    }

    /**
     * [getMethod Get the request method]
     * @return [string]
     */
    public function getServerMethod()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            return [];
        }
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * [getHeaders List all headers Server headers and Apache headers]
     * @return [type] [description]
     */
    public function getHeaders()
    {
        $headers_list = headers_list();
        foreach ($headers_list as $index => $headValue) {
            list($key, $value) = explode(": ", $headValue);

            if ($key && $value) {
                $headers_list[$key] = $value;
                unset($headers_list[$index]);
            }
        }

        if (!function_exists('apache_request_headers')) {
            $apacheRequestHeaders = $this->apacheRequestHeaders();
        } else {
            $apacheRequestHeaders = apache_request_headers();
        }

        $apache_headers = array_merge($headers_list, $apacheRequestHeaders);

        $headers = array();

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) != 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }

        return array_merge($headers, $apache_headers);
    }

    /**
     * [setHeader Append aditional headers]
     * @return [void]
     */
    public function setHeader($header, $headerValue = array(), $status = '', $delimiter = ';')
    {
        $header = trim(str_replace(':', '', $header)) . ': ';
        $headerValue = implode($delimiter . ' ', $headerValue);

        header($header . $status . $headerValue);
    }

    /**
     * [setHeaders Default headers]
     * @return [void]
     */
    public function setHeaders()
    {
        $application = 'json';
        if (isset($this->REQUEST_APPLICATION[$this->getRequestType()])) {
            $application = $this->REQUEST_APPLICATION[$this->getRequestType()];
        }

        $this->setHeader('Content-Type', array(
            $application, 'charset=UTF-8',
        ));

        $this->setHeader('Accept-Ranges', array(
            'bytes',
        ));

        $this->setHeader('Access-Control-Allow-Credentials', array(
            true,
        ));

        $this->setHeader('Access-Control-Allow-Origin', array(
            '*',
        ));

        if( !array_key_exists('Access-Control-Allow-Methods', $this->getHeaders()) ) {
            $this->setHeader('Access-Control-Allow-Methods', array(
                'GET,POST',
            ));
        }

        $this->setHeader('Access-Control-Expose-Headers', array(
            'Content-Range',
        ));

        $this->setHeader('Access-Control-Allow-Headers', array(
            'origin, x-requested-with',
        ));

        $this->setHeader('Access-Control-Max-Age', array(
            $this->getMaxWindow(),
        ));

        $this->setHeader('Expires', array(
            'Wed, 20 September 1978 00:00:00 GMT',
        ));

        $this->setHeader('Cache-Control', array(
            'no-store, no-cache, must-revalidate',
        ));

        $this->setHeader('Cache-Control', array(
            'post-check=0, pre-check=0',
        ));

        $this->setHeader('Pragma', array(
            'no-cache',
        ));

        $this->setHeader('X-Content-Type-Options', array(
            'nosniff',
        ));

        $this->setHeader('X-XSS-Protection', array(
            '1', 'mode=block',
        ));

        if (isset($this->getOptions()['addHeaders']) &&
            (is_array($this->getOptions()['addHeaders']) && !empty($this->getOptions()['addHeaders']))
        ) {
            foreach ($this->getOptions()['addHeaders'] as $i => $customHeader) {
                if (is_array($customHeader) && sizeof($customHeader) == 2) {
                    $this->setHeader($customHeader[0], array(
                        $customHeader[1],
                    ));
                }
            }
        }
    }

    /**
     * [apacheRequestHeaders Native replacment fuction]
     * https://www.php.net/manual/en/function.apache-request-headers.php#70810
     * @return [array]
     */
    public function apacheRequestHeaders()
    {
        $arh = array();
        $rx_http = '/\AHTTP_/';

        foreach ($_SERVER as $key => $val) {
            if (preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();
                $rx_matches = explode('_', $arh_key);
                if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                    foreach ($rx_matches as $ak_key => $ak_val) {
                        $rx_matches[$ak_key] = ucfirst($ak_val);
                    }

                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return ($arh);
    }

    /**
     * [setHeaderStatus]
     * @param [void]
     */
    public function setHeaderStatus($status)
    {
        http_response_code($status);
    }

    /**
     * [getHeaderStatus]
     * @return [integer]
     */
    public function getHeaderStatus()
    {
        return http_response_code();
    }

    /**
     * [hasBearerToken Check if bearer token is present]
     * @return boolean
     */
    public function hasBearerToken()
    {
        $auth_headers = $this->getHeaders();

        if (isset($auth_headers["Authorization"]) && !empty($auth_headers["Authorization"])) {

            list($type, $clientToken) = explode(" ", $auth_headers["Authorization"], 2);

            if (strcasecmp($type, "Bearer") == 0 && !empty($clientToken)) {
                return $clientToken;
            }
        }
        return;
    }

    /**
     * [authorizationHeaders Scan for "Authorization" header]
     * @return [mixed: string / error]
     */
    public function authorizationHeaders($skipError = false)
    {
        $auth_headers = $this->getHeaders();

        if (isset($auth_headers["Authorization"]) && !empty($auth_headers["Authorization"])) {

            /**
             * Test if it's a Authorization Basic & client_credentials
             */
            if (isset($_REQUEST['grant_type']) && $_REQUEST['grant_type'] == 'client_credentials') {
                if ($refreshToken = $this->accessCredentialHeaders($auth_headers)) {
                    return [
                        'client_access_request' => $refreshToken,
                    ];
                }
            }

            /**
             * Test if it's a Authorization Bearer & refresh_token
             */
            if (isset($_REQUEST['grant_type']) && $_REQUEST['grant_type'] == 'refresh_token') {
                if ($refreshToken = $this->accessRefreshHeaders($auth_headers)) {
                    return [
                        'client_access_request' => $refreshToken,
                    ];
                }
            }

            /**
             * Test if it's a Authorization Bearer token
             */
            if (strcasecmp(trim($auth_headers["Authorization"]), "Bearer") == 0) {
                $this->unauthorised();
            }

            list($type, $clientToken) = explode(" ", $auth_headers["Authorization"], 2);

            if (strcasecmp($type, "Bearer") == 0 && !empty($clientToken)) {
                return $clientToken;
            } else {
                if (!$skipError) {
                    $this->unauthorised();
                }
            }
        } else {
            if (!$skipError) {
                $this->unauthorised();
            }
        }

        return '';
    }

    /**
     * [accessRefreshHeaders description]
     * @return [mixed: string / error]
     */
    private function accessRefreshHeaders($auth_headers)
    {
        list($type, $clientToken) = explode(" ", $auth_headers["Authorization"], 2);

        if (strcasecmp($type, "Bearer") == 0 && !empty($clientToken)) {

            $user = new user\user;
            $account = $user
                ->setOptions($this->options)
                ->load(
                    $clientToken,
                    array(
                        'loadBy' => 'refresh_token',
                        'getJWT' => true,
                        'authorizationRefresh' => true,
                    )
                );

            if( empty($account) ) {
                $this->unauthorised();
            }

            $tokens = [
                'token' => $account['JWT'],
                'refresh_token' => $account['refreshToken']['token']
            ];

            $account['refreshToken'] = $tokens;

            return $account;

        } else {
            $this->unauthorised();
        }
    }

    /**
     * [accessCredentialHeaders Check if the credentials are correct]
     * @param  [array] $auth_headers
     * @return [mixed: string / error]
     */
    private function accessCredentialHeaders($auth_headers)
    {
        $cipher = new encoder\cipher;

        list($type, $clientCredentials) = explode(" ", $auth_headers["Authorization"], 2);

        if (strcasecmp($type, "Basic") == 0 && !empty($clientCredentials)) {
            $credentails = explode('/', $clientCredentials);
            if (!empty($credentails) && is_array($credentails)) {
                $credentails = explode(':', $cipher->decode($clientCredentials));

                if (!empty($credentails) && is_array($credentails) && sizeof($credentails) == 2) {
                    $user = new user\user;
                    $user->setAccountID($credentails[0]);

                    $account = $user
                        ->setOptions($this->options)
                        ->load(
                            $credentails[0],
                            array(
                                'loadBy' => 'account_id',
                                'getJWT' => true,
                                'authorizationRefresh' => true,
                            )
                        );

                    $tokens = [
                        'token' => $account['JWT'],
                        'refresh_token' => $account['refreshToken']['token']
                    ];

                    $account['refreshToken'] = $tokens;

                    if (!empty($account)) {
                        if (strcasecmp($account['secret'], $credentails[1]) == 0) {
                            return $account;
                        }
                    }
                }
            }
        } else {
            $this->unauthorised();
        }
    }

    /**
     * [unauthorised Set an unauthorised header]
     * @return [exit exception message]
     */
    public function unauthorised()
    {
        $this->setHeaders();

        $this->setHeader('HTTP/1.1', array(
            'Unauthorized',
        ), 401);

        (new exception\errorException)->error('UNAUTHORIZED');
    }

    /**
     * [getMaxWindow Get the max control age window]
     * @return [integer]
     */
    private function getMaxWindow()
    {
        if ($this->getOptions()) {
            if (isset($this->getOptions()['maxWindow']) && !empty($this->getOptions()['maxWindow'])) {
                if (!is_numeric($this->getOptions()['maxWindow'])) {
                    (new exception\errorException)
                        ->message('maxWindow option must be an integer type')
                        ->error('APPLICATION_ERROR');
                }

                return $this->getOptions()['maxWindow'];
            }
        }
        return self::MAX_WINDOW;
    }

    /**
     * [setData Set request method data]
     * @param array $data
     */
    public function setData($data = []) 
    {
        $this->REQUEST_METHOD['data'] = $data;
    }

    /**
     * [setOptions Set the Responsible API options]
     * @param [array] $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * [getOptions Get the Responsible API options if set]
     * @return [mixed: array/boolean]
     */
    private function getOptions()
    {
        if (!empty($this->options)) {
            return $this->options;
        }
        return;
    }
}
