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

use responsible\core\auth;
use responsible\core\exception;
use responsible\core\interfaces;
use responsible\core\server;

class header extends server implements interfaces\optionsInterface
{
    use \responsible\core\traits\optionsTrait;

    /**
     * Max age constant
     */
    const MAX_WINDOW = 3600;

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
    private $REQUEST_TYPE = 'json';

    /**
     * [$REQUEST_METHOD]
     * @var array
     */
    private $REQUEST_METHOD = [];

    /**
     * [$headerAuth Header authorise class object]
     * @var object
     */
    protected $headerAuth;

    /**
     * [__construct]
     */
    public function __construct()
    {}

    /**
     * [requestType]
     * @return void
     */
    public function requestType($type = 'json')
    {
        $this->REQUEST_TYPE = $type;
    }

    /**
     * [getRequestType]
     * @return string
     */
    public function getRequestType()
    {
        return $this->REQUEST_TYPE;
    }

    /**
     * [requestMethod Set and return the request method]
     * @return object
     */
    public function requestMethod()
    {
        $verbs = new headerVerbs;

        switch (strtolower($_SERVER['REQUEST_METHOD'])) {

            case 'get':
                return $this->REQUEST_METHOD = $verbs->get();
                break;

            case 'post':
                return $this->REQUEST_METHOD = $verbs->post();
                break;

            case 'options':
                $this->REQUEST_METHOD = $verbs->post();
                echo json_encode(['success' => true]);
                $this->setHeaders();
                exit;
                break;

            case 'put':
                $this->REQUEST_METHOD = $verbs->put();
                break;

            case 'patch':
                $this->REQUEST_METHOD = $verbs->patch();
                break;

            case 'delete':
                $this->REQUEST_METHOD = $verbs->delete();
                break;

            default:
                $this->REQUEST_METHOD = [];
                break;
        }
    }

    /**
     * [getMethod Get the request method]
     * @return object
     */
    public function getMethod()
    {
        return (object) $this->REQUEST_METHOD;
    }

    /**
     * [getBody Get the post body]
     * @return array
     */
    public function getBody():array
    {
        if (isset($this->getMethod()->data) && !empty($this->getMethod()->data)) {
            return $this->getMethod()->data;
        }
        return [];
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
            (new exception\errorException)
                ->setOptions($this->getOptions())
                ->error('METHOD_NOT_ALLOWED');
        }
    }

    /**
     * [getMethod Get the request method]
     * @return string
     */
    public function getServerMethod()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            return '';
        }
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * [getHeaders List all headers Server headers and Apache headers]
     * @return array
     */
    public function getHeaders()
    {
        $headers_list = $this->headersList();
        foreach ($headers_list as $index => $headValue) {
            @list($key, $value) = explode(": ", $headValue);

            if (!is_null($key) && !is_null($value)) {
                $headers_list[$key] = $value;
                unset($headers_list[$index]);
            }
        }

        if (!function_exists('apache_request_headers')) {
            $apacheRequestHeaders = $this->apacheRequestHeaders();
        } else {
            $apacheRequestHeaders = apache_request_headers();
        }

        if (is_null($apacheRequestHeaders) || empty($apacheRequestHeaders)) {
            return [];
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

    private function headersList()
    {
        $server = new server([], $this->getOptions());
        if ($isMockTest = $server->isMockTest()) {
            return $this->apacheRequestHeaders();
        }
        
        return headers_list();
    }

    /**
     * [setHeader Append aditional headers]
     * @return void
     */
    public function setHeader($header, $headerValue = array(), $status = '', $delimiter = ';')
    {
        $header = trim(str_replace(':', '', $header)) . ': ';
        $headerValue = implode($delimiter . ' ', $headerValue);

        header($header . $status . $headerValue);
    }

    /**
     * [setHeaders Default headers]
     * @return void
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

        if (!array_key_exists('Access-Control-Allow-Methods', $this->getHeaders())) {
            $this->setHeader('Access-Control-Allow-Methods', array(
                'GET,POST,OPTIONS',
            ));
        }

        $this->setHeader('Access-Control-Expose-Headers', array(
            'Content-Range',
        ));

        $this->setHeader('Access-Control-Allow-Headers', array(
            'origin,x-requested-with,Authorization,cache-control',
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
     * @return array
     */
    public function apacheRequestHeaders()
    {
        $arh = array();
        $rx_http = '/\AHTTP_/';

        foreach ($_SERVER as $key => $val) {
            if (preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, '', $key);
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
     * [headerAuth]
     * @return object
     */
    public function headerAuth()
    {
        if (is_null($this->headerAuth)) {
            $this->headerAuth = new headerAuth;
        }
        $this->headerAuth->setOptions($this->getOptions());
        return $this->headerAuth;
    }

    /**
     * [authorizationHeaders Scan for "Authorization" header]
     * @return string|array [mixed: string / error]
     */
    public function authorizationHeaders($skipError = false)
    {
        return $this->headerAuth()->authorizationHeaders($skipError);
    }

    /**
     * [hasBearerToken Check if bearer token is present]
     * @return string|null
     */
    public function hasBearerToken()
    {
        return $this->headerAuth()->hasBearerToken();
    }

    /**
     * [unauthorised Set an unauthorised header]
     * @return array [exit exception message]
     */
    public function unauthorised()
    {
        $this->headerAuth()->setUnauthorised();
    }

    /**
     * [getMaxWindow Get the max control age window]
     * @return integer
     */
    private function getMaxWindow()
    {
        if ($this->getOptions()) {
            if (isset($this->getOptions()['maxWindow']) && !empty($this->getOptions()['maxWindow'])) {
                if (!is_numeric($this->getOptions()['maxWindow'])) {
                    (new exception\errorException)
                        ->setOptions($this->getOptions())
                        ->message('maxWindow option must be an integer type')
                        ->error('APPLICATION_ERROR');
                }

                return $this->getOptions()['maxWindow'];
            }
        }
        return self::MAX_WINDOW;
    }

    /**
     * [setHeaderStatus]
     * @param void
     */
    public function setHeaderStatus($status)
    {
        http_response_code($status);
    }

    /**
     * [getHeaderStatus]
     * @return integer
     */
    public function getHeaderStatus()
    {
        return http_response_code();
    }

    /**
     * [setData Set request method data]
     * @param array $data
     * @return void
     */
    public function setData($data = [])
    {
        $this->REQUEST_METHOD['data'] = $data;
    }
}
