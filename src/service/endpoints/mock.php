<?php

/**
 * ASSETS ENDPOINT CLASS
 *
 */
namespace responsible\service\endpoints;

use responsible\core\headers;
use responsible\service\interfaces;
use responsible\core\exception;

class mock implements interfaces\endpointsInterface
{
    /**
     * [$settings]
     * @var array
     */
    private $settings = [];

    /**
     * [settings Inherited settings]
     * @return void
     */
    public function settings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * [headerMethods]
     * @return void
     */
    public function headerMethods()
    {
        $headers = new headers\header;
        $headers->setOptions($this->responsible->options);
        $headers->setAllowedMethods(
            ['GET', 'POST']
        );
    }

    /**
     * [register]
     * @return array
     */
    public function register()
    {
        return array(
            '/mock',
            '/mock/{mockId}',
            '/mock/{mockId}/post',
            '/mock/{mockId}/public',
        );
    }

    /**
     * [scope Routing scope access]
     * @return string
     */
    public function scope()
    {
        return [
            'private',
            'private',
            'private',
            'anonymous',
        ];
    }

    /**
     * [run Run the method request]
     * @return array
     */
    public function run()
    {
        return ['mock_run' => [
            'passed' => true
        ]];
    }
}
