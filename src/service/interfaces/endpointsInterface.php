<?php

/**
 * ENDPOINT INTERFACE
 *
 */
namespace responsible\service\interfaces;

interface endpointsInterface
{

    /**
     * [settings The controller settings are passed on by the router]
     * @param  [array] $settings [methods, args ect..]
     * @return void
     */
    public function settings(array $settings);

    /**
     * [register - Register the service endpoints]
     * @return array
     */
    public function register();

    /**
     * [headerMethods Describe the allowed methods]
     * @return void
     */
    public function headerMethods();

    /**
     * [scope Routing scope access 'public or private']
     * @return string
     */
    public function scope();

    /**
     * [run Run the method request]
     * @return mixed
     */
    public function run();
}
