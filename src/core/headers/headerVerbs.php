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

use responsible\core\helpers\help as helper;

class headerVerbs extends header
{
    /**
     * [$contentType Requested content type]
     * @var array
     */
    private static $contentType = [
        'name' => '',
        'type' => ''
    ];

    /**
     * [__construct]
     */
    public function __construct()
    {
        self::setContentType();
    }

    /**
     * [get GET request method]
     * @return array
     */
    public function get(): array
    {
        $_GET = ($_GET) ?? [];
        return [
            'method' => 'get',
            'data' => $_GET,
        ];
    }

    /**
     * [post POST request method]
     * @return array
     */
    public function post(): array
    {
        $_postData = ($_POST) ?? [];

        $jsonData = json_decode(file_get_contents("php://input"));

        if (is_object($jsonData) || is_array($jsonData)) {
            $_postData = json_decode(file_get_contents("php://input"), true);
        }
        $_POST = array_merge($_REQUEST, $_POST);
        $_REQUEST = array_merge($_POST, $_postData);

        $_GET = $this->get()['data'];
        $_REQUEST = array_merge($_GET, $_REQUEST);

        return [
            'method' => 'post',
            'data' => $_REQUEST,
        ];
    }

    /**
     * [options OPTIONS CORS request]
     * @return array
     */
    public function options(): array
    {
        return $this->post();
    }

    /**
     * [post PUT request method]
     * @return array
     */
    public function put(): array
    {
        $_PUT = [];

        if (self::getContentType()['type'] === 'json') {
            $putfp = fopen('php://input', 'r');
            $putdata = '';
            while ($data = fread($putfp, 1024)) {
                $putdata .= $data;
            }
            fclose($putfp);

            if (!empty($putdata)) {
                $_PUT = json_decode($putdata, true);
                if (json_last_error() !== 0) {
                    $_PUT = [];
                }
            }

        } else {
            parse_str(file_get_contents("php://input"), $_PUT);
            foreach ($_PUT as $key => $value) {
                unset($_PUT[$key]);
                $_PUT[str_replace('amp;', '', $key)] = $value;
            }
        }

        // [TODO]
        if (self::getContentType()['name'] === 'multipart/form-data') {

        }
        if (self::getContentType()['type'] === 'x-www-form-urlencoded') {

        }

        $_REQUEST = array_merge($_REQUEST, $_PUT);
        $_REQUEST = array_merge($_POST, $this->post()['data']);
        $_REQUEST = array_merge($this->get()['data'], $_REQUEST);

        return [
            'method' => 'put',
            'data' => $_REQUEST,
        ];
    }

    /**
     * [post DELETE request method]
     * @return array
     */
    public function delete()
    {
        return ['method' => 'delete', 'data' => $this->get()['data']];
    }

    /**
     * [post PATCH request method]
     * @return array
     */
    public function patch()
    {
        return ['method' => 'patch', 'data' => $this->put()['data']];
    }

    /**
     * [getRequestVerb Get the requested method/ verb]
     * @return string
     */
    public static function getRequestVerb():string
    {
        $helper = new helper;
        $method = $helper->checkVal(@$_SERVER, 'REQUEST_METHOD', '' );
        $method = $helper->checkVal(@$_SERVER, 'HTTP_X_HTTP_METHOD', $method );

        return strtolower($method);
    }

    /**
     * [setContentType Set content type]
     * @return void
     */
    public static function setContentType()
    {
        $helper = new helper;
        $contentType = $helper->checkVal(@$_SERVER, 'CONTENT_TYPE', '');
        $contentType = $helper->checkVal(@$_SERVER, 'HTTP_CONTENT_TYPE', $contentType);
        $contentType = $helper->checkVal(@$_SERVER, 'HTTP_X_CONTENT_TYPE', $contentType);

        if (empty($contentType)) {
            return '';
        }

        if (preg_match('@multipart/form-data@', $contentType)) {
            self::$contentType = [
                'name' => 'multipart/form-data',
                'type' => 'form-data',
            ];
            return;
        }

        $contentTypeName = explode('/', $contentType);
        $contentTypeName = ($contentTypeName[1]) ?? '';

        self::$contentType = [
            'name' => $contentType,
            'type' => $contentTypeName,
        ];
    }

    /**
     * [getContentType Get the requested content type]
     * @return array
     */
    public function getContentType(): array
    {
        return self::$contentType;
    }
}
