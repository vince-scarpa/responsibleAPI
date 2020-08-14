<?php

/**
 * ASSETS ENDPOINT CLASS
 *
 */
namespace responsible\service\endpoints;

use responsible\core\headers;
use responsible\service\interfaces;
use responsible\core\exception;

class mockFail
{
    /**
     * [settings Inherited settings]
     * @return void
     */
    public function settings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * [register]
     * @return array
     */
    public function register()
    {
        return array(
            '/mockfail/missing/methods',
        );
    }
}