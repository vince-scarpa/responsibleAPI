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
use responsible\core\exception\responsibleException;

class responsible
{
    use \responsible\core\traits\optionsTrait;

    /**
     * [$config Variable store for the Responsible API config set]
     * @var array
     */
    private $config;

    /**
     * [$defaults Variable store for the Responsible API defaults set]
     * @var array
     */
    private $defaults;

    /**
     * [$server Core server object]
     * @var object
     */
    private $server;

    /**
     * [$response Response data]
     * @var array|object
     */
    private static $response;

    /**
     * [$requestType Header request response format]
     * @var string
     */
    private $requestType = 'json';

    /**
     * [$requestType Request limit]
     * @var integer
     */
    private $requestLimit;

    /**
     * [$requestType Request window rate]
     * @var string|integer
     */
    private $requestRateWindow;

    /**
     * [__construc :: Construct the Responsible API]
     * @param array $DEFAULTS   
     *        environment settings
     * @param array  $options  
     *        API options
     */
    public function __construct(array $options = [], $initiate = true)
    {
        /**
         * Initiate the Responsible API configuration and options
         */
        $this->setConfig($options);

        $this->setRequestType(($options['requestType']) ?? 'json');

        $this->setRateLimit(($options['rateLimit']) ?? 100);

        $this->setRateWindow(($options['rateWindow']) ?? 'MINUTE');

        if ($initiate) {
            $this->run();
        }
    }

    /**
     * Run the server
     * @return mixed
     */
    public function run()
    {
        /**
         * Initiate the Responsible API server
         */
        $this->server();
    }

    /**
     * [setConfig Set the ResponsibleAPI configuration]
     * @return void
     */
    private function setConfig($options)
    {
        $config = new configuration\config;
        $config->baseApiRoot(dirname(__DIR__));
        $config->responsibleDefault($options);

        $this->setOptions($config->getOptions());
        $this->config($config->getConfig());
        $this->defaults($config->getDefaults());
    }

    /**
     * [server :: Initiate the Responsible core server]
     * @return void
     */
    private function server()
    {
        $options = $this->getOptions();
        $route = ($options['route']) ?? '';

        /**
         * [$this->server :: Set the a new API server object]
         * @var responsibleCore [Alias for responsible\core]
         */
        $this->server = new responsibleCore\server(
            $this->getConfig(),
            $options,
            true
        );

        // Set the header request format
        $this->server->requestType($this->getRequestType());

        // Authenticate the API connections
        try {
            $this->server->authenticate();
        }catch (responsibleException | \Exception $e) {
            self::$response = $e->getMessage();
            return;
        }

        // Set the rate limit and timeframe for API connection limits
        try {
            $this->server->rateLimit(
                $this->getRateLimit(),
                $this->getRateWindow()
            );
        }catch (responsibleException | \Exception $e) {
            self::$response = $e->getMessage();
            return;
        }

        // Build the APIs internal router
        try {
            $this->server->route($route);
        }catch (responsibleException | \Exception $e) {
            self::$response = $e->getMessage();
            return;
        }

        self::$response = $this->server->response();
    }

    /**
     * [getApiData Get the Responsible API router data]
     * @return array
     */
    public function Router()
    {
        return $this->server->getRouter();
    }

    /**
     * [response :: Get the final API response as an output]
     * @return object|array
     */
    public function responseData($debug = 'coredata')
    {
        return $this->server->response($debug);
    }

    /**
     * [config Set the Responsible API configuration]
     * @return void
     */
    private function config($config)
    {
        $this->config = $config;
    }

    /**
     * [getConfig Get the stored Responsible API configuration]
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * [defaults Set the Responsible API defaults]
     * Configuration and Options merged
     * @return void
     */
    private function defaults($defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * [getDefaults Get the stored Responsible API defaults]
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * [setRequestType :: Header request response format]
     * @param string $type
     */
    private function setRequestType($type)
    {
        $this->requestType = $type;
    }

    /**
     * [getRequestType :: Get the header request format]
     * @return string
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
     * @param integer $limit
     */
    private function setRateLimit($limit)
    {
        $this->requestLimit = $limit;
    }

    /**
     * [getRateLimit :: Get the Responsible API ratelimit]
     * @return integer
     */
    private function getRateLimit()
    {
        return $this->requestLimit;
    }

    /**
     * [setRateWindow Set the Responsible API window for rate limits]
     * The window is a range set for API connection requests
     *
     * @see setRateLimit()
     * @param string|integer $frame
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
     * @return integer|string
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
     * @return self|object
     */
    public static function API(array $options = [])
    {
        return new self($options);
    }

    /**
     * [unauthorised Set a custom unauthorized header]
     * @return void
     */
    public static function unauthorised()
    {
        (new headers\header)->unauthorised();
    }

    /**
     * [response Get the Responsible API response]
     * @return array|object
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
     * @param  string $name
     * @param  string $mail
     * @return array
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
     * @param  array $properties
     * @return array
     */
    public static function updateUser($properties)
    {
        return (new user\user)
            ->update($properties)
        ;
    }

    /**
     * [loadUser Load a stored account]
     * @param  integer|string $property
     * @param  string $type
     * @return array
     */
    public static function loadUser($property, array $options = [])
    {
        $loadBy = (isset($options['loadBy']) && !empty($options['loadBy']))
        ? $options['loadBy'] : 'account_id';

        $getJWT = (isset($options['getJWT']) && is_bool($options['getJWT']))
        ? $options['getJWT'] : true;

        $getSecretAppend = (isset($options['secret']) && ($options['secret'] == 'append') )
        ? $options['secret'] : false;

        return (new user\user)
            ->setOptions($options)
            ->load(
                $property,
                array(
                    'loadBy' => $loadBy,
                    'getJWT' => $getJWT,
                    'secret' => $getSecretAppend
                )
        );
    }
}
