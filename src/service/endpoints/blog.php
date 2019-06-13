<?php

/**
 * ASSETS ENDPOINT CLASS
 *
 */
namespace responsible\service\endpoints;

use responsible\core\headers;
use responsible\service\interfaces;
use responsible\core\exception;

class blog implements interfaces\endpointsInterface
{

    /**
     * [settings Inherited settings]
     * @return [void]
     */
    public function settings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * [headerMethods]
     * @return [void]
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
     * @return [array]
     */
    public function register()
    {
        return array(
            '/blog/article/{blogId}',
            '/blog/post/{blogId}',
            '/blog/post/{year}/{month}/{day}/{blogSlug}',
        );
    }

    /**
     * [scope Routing scope access]
     * @return [string]
     */
    public function scope()
    {
        return 'private';
    }

    /**
     * [run Run the method request]
     * @return [void]
     */
    public function run()
    {
        return array(
            'testing' =>
            [
                'response' => 'works',

                'deepArray' => [
                    'works' => ['sure', 'does'],
                ],
                'deepArray2' => [
                    'works' => 'sure does',
                ],
                'deepArray3' => [
                    'works' => 'sure does',
                ],
                'deepArray4' => [
                    'works' => 'sure does',
                ],
            ],
            // 'responsibleCore' => $this->responsible,
        );
    }
}
