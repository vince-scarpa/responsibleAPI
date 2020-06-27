<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core;

use responsible\core\auth;
use responsible\core\configuration;
use responsible\core\connect;
use responsible\core\endpoints;
use responsible\core\exception;
use responsible\core\headers;
use responsible\core\keys;
use responsible\core\request;
use responsible\core\route;
use responsible\core\throttle;

class server
{
    /**
     * [$config]
     * @var object
     */
    protected $config;

    /**
     * [$options Variable store for the Responsible API options set]
     * @var array|null
     */
    private $options = null;

    /**
     * [$DB Database PDO connector]
     * @var object|null
     */
    protected $DB = null;

    /**
     * [$grant_access If grant type is set then allow system scope override]
     * @var boolean
     */
    protected $grantAccess = false;

    /**
     * [$RESPONSE]
     * @var array
     */
    protected $RESPONSE = array();

    /**
     * [$header Header class object]
     * @var object
     */
    protected $header;

    /**
     * [$endpoints Endpoints class object]
     * @var object
     */
    protected $endpoints;

    /**
     * [$keys Keys class object]
     * @var object
     */
    protected $keys;

    /**
     * [$auth Auth class object]
     * @var object
     */
    protected $auth;

    /**
     * [$limiter Limiter class object]
     * @var object
     */
    protected $limiter;

    /**
     * [$router Router class object]
     * @var object
     */
    protected $router;

    /**
     * [$renderError]
     * @var boolean
     */
    protected $renderError = false;

    /**
     * [__construct]
     * @param array  $config 
     *        environment variables
     * @param boolean $db
     */
    public function __construct(array $config = [], array $options = [], $db = false)
    {
        $this->setOptions($options);

        // @codeCoverageIgnoreStart
        if ($db && !$this->isMockTest()) {
            if (empty($config)) {
                $config = $this->getConfig();
            }
            if (is_null($this->DB)) {
                $this->DB = new connect\DB($config['DB_HOST'], $config['DB_NAME'], $config['DB_USER'], $config['DB_PASSWORD']);
            }
        }
        // @codeCoverageIgnoreEnd

        $this->setDependencies();
    }

    /**
     * [getConfig]
     * @return array
     */
    public function getConfig()
    {
        $config = new configuration\config;
        $config->responsibleDefault();
        $config = $config->getConfig();

        return $config;
    }

    /**
     * [setDependencies Setup all dependent classes]
     */
    private function setDependencies()
    {
        $options = $this->getOptions();

        if (is_null($this->header)) {
            $this->header = new headers\header;
            $this->header->setOptions($options);
        }

        if (is_null($this->keys)) {
            $this->keys = new keys\key;
        }

        if (is_null($this->endpoints)) {
            $this->endpoints = new endpoints\map;
            $this->endpoints->setOptions($options);
        }

        if (is_null($this->auth)) {
            $this->auth = new auth\authorise($options);
            $this->auth->header = $this->header;
        }
    }

    /**
     * [getInstance Get a child dependency of sever class]
     * @param  [type] $class
     * @return object|null
     */
    public function getInstance($class)
    {
        if (!is_null($this->{$class})) {
            return $this->{$class};
        }
        return null;
    }

    /**
     * [options Responsible API options]
     * @param array $options
     */
    public function setOptions($options)
    {
        if (!is_null($this->options)) {
            array_merge($this->options, $options);
            return;
        }

        $this->options = $options;
    }

    /**
     * [getOptions Get the stored Responsible API options]
     * @return array|null
     */
    public function getOptions():?array
    {
        return $this->options;
    }

    /**
     * [DB Get the database instance]
     * @codeCoverageIgnore
     * @return object
     */
    public function DB()
    {
        return $this->DB;
    }

    /**
     * [requestType]
     * @var string $type
     * @return self
     */
    public function requestType($type)
    {
        $this->header->requestType($type);
        $this->header->requestMethod();
        return $this;
    }

    /**
     * [getRequestType]
     * @return string
     */
    public function getRequestType()
    {
        return $this->header->getRequestType();
    }

    /**
     * [setResponse Append the Responsible API response]
     * @param string|array $key
     * @param array|object|null $response
     */
    public function setResponse($key, $response)
    {
        $this->RESPONSE = [
            'headerStatus' => $this->header->getHeaderStatus(),
            'expires_in' => $this->auth->getJWTObject('expiresIn'),
            'access_token' => $this->auth->getJWTObject('token'),
            'refresh_token' => $this->auth->getJWTObject('refresh'),
        ];

        if (isset($this->RESPONSE['response'][$key])) {
            $this->RESPONSE['response'][$key][] = $response;
            return;
        }
        if (is_null($key) || $key == '') {
            if( !is_null($response) ) {
                $this->RESPONSE['response'] = $response;
            }
            return;
        }

        $this->RESPONSE['response'][$key] = $response;
    }

    /**
     * [getResponse Get the Responsible API output response]
     * @return array
     */
    public function getResponse()
    {
        return $this->RESPONSE;
    }

    /**
     * [rate Set the API rate limit]
     * @param  integer $limit [The request limit]
     * @param  string|integer $rate  [The request window]
     * @return self
     */
    public function rateLimit($limit = null, $rate = null)
    {
        $this->limiter = new throttle\limiter($limit, $rate);
        $this->limiter->setOptions($this->getOptions());
        return $this;
    }

    /**
     * [authenticate Parse the requests to Responsible API]
     *
     * 1. Authorise the requests JWT
     * 2. Throttle the requests
     *
     * @return self
     */
    public function authenticate()
    {
        $options = $this->getOptions();
        $route = (isset($options['route']) && !empty($options['route']) ) ? $options['route'] : '';

        $this->endpoints->baseApiRoot(dirname(__DIR__));
        $this->endpoints->register();
        
        $router = new route\router();
        $router->baseApiRoot(dirname(__DIR__));

        $this->router = $router->route($route);
        $endpoint = $this->endpoints->isEndpoint($router->getApi(), $router->getPath());

        if(isset($endpoint->model['scope'])) {
            $_REQUEST['scope'] = $endpoint->model['scope'];
            $this->header->setData($_REQUEST);
        }

        /**
         * Authenticate the JWT
         */
        $this->auth->authorise();

        /**
         * Call the rate limiter then throttle
         */
        if (!isset($this->limiter)) {
            $this->rateLimit();
        }
        
        $this->limiter
            ->setAccount($this->auth->user())
            ->setupOptions()
            ->throttleRequest()
        ;

        return $this;
    }

    /**
     * [route Build the Responsible router]
     *
     * 1. Endpoints registry
     * 2. Build router
     * 3. Try run middleware
     *
     * @return self
     */
    public function route($route)
    {
        /**
         * Register endpoints
         */
        $this->endpoints->baseApiRoot(dirname(__DIR__));
        $this->endpoints->register();

        /**
         * Initialise the router
         */
        $router = new route\router();
        $router->baseApiRoot(dirname(__DIR__));
        $this->router = $router->route($route);
        $this->router->options = $this->getOptions();
        $this->router->auth = $this->auth->user();
        $this->router->limiter = $this->limiter->getThrottle();

        /**
         * Endpoint tiers must be larger than 1
         */
        if ($router->getSize() < 2) {
            (new exception\errorException)
                ->setOptions($this->getOptions())
                ->error('NOT_FOUND');
        }

        /**
         * Check if the requested endpoint is allowed
         */
        if (!$this->router->endpoint =
            $this->endpoints->isEndpoint($router->getApi(), $router->getPath())
        ) {
            (new exception\errorException)
                ->setOptions($this->getOptions())
                ->error('BAD_REQUEST');
        }

        $this->router->endpoint->header = [
            'method' => $this->header->getServerMethod(),
            'status' => $this->header->getHeaderStatus(),
            'body' => $this->header->getMethod(),
        ];

        /**
         * Check if theres a payload sent
         */
        if(isset($_REQUEST['payload'])) {
            $router->setRequestBody($_REQUEST['payload']);
        }
        // print_r($_REQUEST);
        /*if(isset($_POST) && !empty($_POST)) {
            $router->setPostBody($_POST);
        }*/
        $this->router->payload = $router->getRequestBody();

        /**
         * Check the access scope
         */
        if( !isset($this->router->endpoint->model['scope']) ) {
            $this->router->endpoint->model['scope'] = 'private';
        }

        if( isset($this->header->getMethod()->data['scope']) && 
            ($this->header->getMethod()->data['scope'] == 'anonymous')
        ) {
            $this->router->endpoint->model['scope'] = 'anonymous';
        }

        $router->setScope($this->router->endpoint->model['scope']);
        
        // @codeCoverageIgnoreStart
        if (!$this->auth->isGrantType() && !$this->isMockTest()) {
            if (!$router->systemAccess($this->auth->user())) {
                $this->header->unauthorised();
            }
        }
        // @codeCoverageIgnoreEnd

        /**
         * Try run the requests
         */
        if ($router->getScope() !== 'system') {
            $response = $router->run();

        } else {
            /*$response = [
                'system' => $router->getApi(),
            ];*/

            $response = $router->run();
        }

        $this->setResponse('', $response);

        return $this;
    }

    /**
     * [getRouter Get the details of the Responsible API router]
     * @return object
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * [coredata Get the core data response]
     * @return object
     */
    public function coredata()
    {
        /**
         * Set the core data response
         * Used for debugging
         */
        foreach ($this->router as $key => $value) {
            $this->setResponse($key, $value);
        }
        return $this;
    }

    /**
     * [response Finnal response output]
     * @return array|object
     */
    public function response($debug = '')
    {
        /**
         * Output bebug functions
         */
        if (!empty($debug)) {
            if (method_exists($this, $debug)) {
                call_user_func(array($this, $debug));
            }
        }

        /**
         * Set the Responsible headers
         */
        $this->header->requestType($this->getRequestType());
        $this->header->setHeaders();

        /**
         * Output the response if any
         */
        return (new request\application($this->getRequestType()))
            ->data($this->getResponse());
    }

    /**
     * isMockTest
     *     Check if there's a mock test request
     * @return boolean
     */
    public function isMockTest():bool
    {
        $config = $this->getConfig();

        return (isset($this->options['mock']) && $this->options['mock'] == $config['MASTER_KEY']);
    }
}
