<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsible.git
 *
 * @api Responible API
 * @package responsible\core\request
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\request;

use responsible\core\exception;

class application
{
    /**
     * [__construct]
     * @param [string] $application
     */
    public function __construct($application)
    {
        $this->requestType = $application;
    }

    /**
     * [data]
     * @return [type]
     */
    public function data($data)
    {
        if (empty($data)) {
            (new exception\errorException)->error('NO_CONTENT');
        }

        $this->requestType = $this->requestType . 'Response';

        if (method_exists($this, $this->requestType)) {
            return call_user_func(array(
                $this,
                $this->requestType,
            ), $data);
        } else {
            (new exception\errorException)
                ->message('The requested method `' . strtoupper($this->requestType) . '` dosen\'t exist. Please read the documentation on supported request types.')
                ->error('APPLICATION_ERROR');
        }
    }

    /**
     * [arrayResponse]
     * @return [array]
     */
    private function arrayResponse($data)
    {
        return $data;
    }

    /**
     * [objectResponse]
     * @return [object]
     */
    private function objectResponse($data)
    {
        return (object) $data;
    }

    /**
     * [json]
     * @return [json object]
     */
    private function jsonResponse($data)
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * [xml]
     * [TODO]
     * @return [xml nodes]
     */
    private function xmlResponse($data)
    {
        echo 'XML ERROR:: No methods exist!';
    }

    /**
     * [html]
     * [TODO]
     * @return [html DOM]
     */
    private function htmlResponse($data)
    {
        return $data;
    }

    /**
     * [debug]
     * @return [array]
     */
    private function debugResponse($data)
    {
        return $data;
    }
}
