<?php

/**
 * ASSETS ENDPOINT CLASS
 *
 */
namespace responsible\service\endpoints;

use responsible\core\headers;
use responsible\service\interfaces;

class assets implements interfaces\endpointsInterface
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
            '/assets/all',
            '/assets/asset/{assetId}',
            '/assets/{contentsId}/asset/{assetId}',
            '/assets/download/{assetId}',
        );
    }

    /**
     * [scope Routing scope access]
     * @return string
     */
    public function scope()
    {
        return 'private';
    }

    /**
     * [run Run the method request]
     * @return void
     */
    public function run()
    {
        print_r($this);
    }
}
