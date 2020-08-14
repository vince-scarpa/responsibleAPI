<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\request
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\request;

use responsible\core\server;
use responsible\core\exception;

class application
{
    use \responsible\core\traits\optionsTrait;

    /**
     * [$requestType description]
     * @var string
     */
    private $requestType;

    /**
     * [__construct]
     * @param string $application
     */
    public function __construct($application)
    {
        $this->requestType = $application;
    }

    /**
     * [data]
     * @return mixed
     */
    public function data($data)
    {
        if (empty($data)) {
            (new exception\errorException)
                ->setOptions($this->getOptions())
                ->error('NO_CONTENT');
        }

        $this->requestType = $this->requestType . 'Response';

        if (method_exists($this, $this->requestType)) {
            return call_user_func(array(
                $this,
                $this->requestType,
            ), $data);
            
        } else {
            (new exception\errorException)
                ->setOptions($this->getOptions())
                ->message('The requested method `' . strtoupper($this->requestType) . '` dosen\'t exist. Please read the documentation on supported request types.')
                ->error('APPLICATION_ERROR');
        }
    }

    /**
     * [arrayResponse]
     * @return array
     */
    private function arrayResponse($data)
    {
        return $data;
    }

    /**
     * [objectResponse]
     * @return object
     */
    private function objectResponse($data)
    {
        return (object) $data;
    }

    /**
     * [json]
     * @return string
     */
    private function jsonResponse($data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * [xml]
     * [TODO]
     * @return void
     */
    private function xmlResponse($data)
    {
        echo 'XML ERROR:: No methods exist!';
    }

    /**
     * [html]
     * [TODO]
     * @return string
     */
    private function htmlResponse($data)
    {
        return $data;
    }

    /**
     * [debug]
     * @return array
     */
    private function debugResponse($data)
    {
        return $data;
    }
}
