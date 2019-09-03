<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\
 * @version 1.2
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible;

use responsible\core as responsibleCore;
use responsible\core\configuration;
use responsible\core\user;
use responsible\core\headers;

class responsible
{
    /**
     * [$options Variable store for the Responsible API options set]
     * @var [array]
     */
    private $options;

    /**
     * [$config Variable store for the Responsible API config set]
     * @var [array]
     */
    private $config;

    /**
     * [$defaults Variable store for the Responsible API defaults set]
     * @var [array]
     */
    private $defaults;

    /**
     * [$server Core server object]
     * @var [object]
     */
    private $server;

    /**
     * [$response Response data]
     * @var [mixed: array / object]
     */
    private static $response;

    /**
     * [$requestType Header request response format]
     * @var string
     */
    private $requestType = 'json';

    /**
     * [__construc :: Construct the Responsible API]
     * @param [type] $DEFAULTS   [environment settings]
     * @param array  $options [API options]
     */
    public function __construct(array $options = [])
    {
        /**
         * Initiate the Responsible API configuration and options
         */
        $this->setConfig($options);

        $this->setRequestType(
            (isset($this->getOptions()['requestType'])) ? $this->getOptions()['requestType'] : 'json'
        );

        $this->setRateLimit(
            (isset($this->getOptions()['rateLimit'])) ? $this->getOptions()['rateLimit'] : 100
        );

        $this->setRateWindow(
            (isset($this->getOptions()['rateWindow'])) ? $this->getOptions()['rateWindow'] : 'MINUTE'
        );

        /**
         * Initiate the Responsible API server
         */
        $this->server();
    }

    /**
     * [setConfig]
     */
    private function setConfig($options)
    {
        $config = new configuration\config;
        $config->baseApiRoot(dirname(__DIR__));
        $config->responsibleDefault($options);

        $this->options($config->getOptions());
        $this->config($config->getConfig());
        $this->defaults($config->getDefaults());
    }

    /**
     * [server :: Initiate the Responsible core server]
     * @param  [type] $CONFIG [environment settings]
     * @return [void]
     */
    private function server()
    {
        $route = (isset($this->getOptions()['route'])) ? $this->getOptions()['route'] : '';

        /**
         * [$this->server :: Set the a new API server object]
         * @var responsibleCore [Alias for responsible\core]
         */
        $this->server = new responsibleCore\server(
            $this->getConfig(),
            $this->getOptions(),
            true
        );

        $this->server
        // Set the header request format
            ->requestType($this->getRequestType())
        // Set the rate limit and timeframe for API connection limits
            ->rateLimit(
                $this->getRateLimit(),
                $this->getRateWindow()
            )
        // Authenticate the API connections
            ->authenticate()
        // Build the APIs internal router
            ->route($route)
        ;

        self::$response = $this->server->response();
    }

    /**
     * [getApiData Get the Responsible API router data]
     * @return [array]
     */
    public function Router()
    {
        return $this->server->getRouter();
    }

    /**
     * [response :: Get the final API response as an output]
     * @return [object / array]
     */
    public function responseData($debug = 'coredata')
    {
        return $this->server->response($debug);
    }

    /**
     * [options Set the Responsible API options]
     * @param [array] $options
     */
    private function options($options)
    {
        $this->options = $options;
    }

    /**
     * [getOptions Get the stored Responsible API options]
     * @return [array]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * [config Set the Responsible API configuration]
     * @return [void]
     */
    private function config($config)
    {
        $this->config = $config;
    }

    /**
     * [getConfig Get the stored Responsible API configuration]
     * @return [array]
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * [defaults Set the Responsible API defaults]
     * Configuration and Options merged
     * @return [void]
     */
    private function defaults($defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * [getDefaults Get the stored Responsible API defaults]
     * @return [array]
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * [setRequestType :: Header request response format]
     * @param [string] $type
     */
    private function setRequestType($type)
    {
        $this->requestType = $type;
    }

    /**
     * [getRequestType :: Get the header request format]
     * @return [string]
     */
    private function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * [setRateLimit :: Set the Responsible API ratelimit]
     * How many API requests a connection is allowed
     * in a certain timeframe
     *
     * EG: 100 requests per minute
     *
     * @param [integer] $limit
     */
    private function setRateLimit($limit)
    {
        $this->requestLimit = $limit;
    }

    /**
     * [getRateLimit :: Get the Responsible API ratelimit]
     * @return [integer]
     */
    private function getRateLimit()
    {
        return $this->requestLimit;
    }

    /**
     * [setRateWindow Set the Responsible API window for rate limits]
     * The window is a range set for API connection requests
     *
     * @see [setRateLimit()]
     *
     * @param [mixed string or integer] $frame
     */
    private function setRateWindow($frame)
    {
        if (is_numeric($frame)) {
            $this->requestRateWindow = $frame;
            return;
        }

        $this->requestRateWindow = $frame;
    }

    /**
     * [getRateWindow Get the timeframe set for rate limits]
     * @return [mixed: integer/string]
     */
    private function getRateWindow()
    {
        return $this->requestRateWindow;
    }

    /**
     * **************************************
     * PUBLIC FACTORY METHODS
     * ***************************************
     */

    /**
     * [API Initiate the Responsible API]
     * @param array $options
     * @return [self/object]
     */
    public static function API(array $options = [])
    {
        return new self($options);
    }

    /**
     * [unauthorised Set a custom unauthorized header]
     * @return [void]
     */
    public static function unauthorised()
    {
        (new headers\header)->unauthorised();
    }

    /**
     * [response Get the Responsible API response]
     * @return [mixed: array / object]
     */
    public static function response($echo = false)
    {
        if ($echo) {
            print_r(self::$response);
            return;
        }
        return self::$response;
    }

    /**
     * [createUser Create a new user access]
     * @param  [string] $name
     * @param  [string] $mail
     * @return [array]
     */
    public static function createUser($name, $mail, array $options = [])
    {
        return (new user\user)
            ->setOptions($options)
            ->credentials($name, $mail)
            ->create()
        ;
    }

    /**
     * [updateUser Update a user account]
     * @param  [array] $properties
     * @param  [array] $options
     * @return [array]
     */
    public static function updateUser($properties, array $options = [])
    {
        return (new user\user)
            ->update($properties)
        ;
    }

    /**
     * [loadUser Load a stored account]
     * @param  [mixed: interger/string] $property
     * @param  [string] $type
     * @return [array]
     */
    public static function loadUser($property, array $options = [])
    {
        $loadBy = (isset($options['loadBy']) && !empty($options['loadBy']))
        ? $options['loadBy'] : 'account_id';

        $getJWT = (isset($options['getJWT']) && is_bool($options['getJWT']))
        ? $options['getJWT'] : true;

        return (new user\user)
            ->setOptions($options)
            ->load(
                $property,
                array(
                    'loadBy' => $loadBy,
                    'getJWT' => $getJWT,
                )
        );
    }
}
