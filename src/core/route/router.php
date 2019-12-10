<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\route
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\route;

use responsible\core\exception;
use responsible\core\route;
use responsible\core\server;
use responsible\core\encoder;

class router extends server
{
    /**
     * [$root Responsible API root path]
     * @var [type]
     */
    private $root;

    /**
     * [$protocol]
     * @var string
     */
    private $protocol = '';

    /**
     * [$scope Router scope]
     * @var string
     */
    private $scope = 'private';

    /**
     * [$routes]
     * @var array
     */
    private $routes = array();

    /**
     * [$requestBody]
     * @var array
     */
    private $requestBody = [];

    /**
     * [run Try run the request method]
     * @return [mixed]
     */
    public function run()
    {
        $controllerSettings = $this->getController();
        $controller = $controllerSettings->model['namespace'];

        if (class_exists($controller)) {
            $controller = new $controller;

            $neededMethods = [
                'headerMethods',
                'settings',
                'run',
            ];

            foreach ($neededMethods as $method) {
                if (!method_exists($controller, $method)) {
                    (new exception\errorException)
                        ->message(
                            "There's a method missing in '"
                            . $controllerSettings->model['class']
                            . "' {$method}() must be declared."
                        )
                        ->error('NOT_EXTENDED');
                }
            }

            $controller->headerMethods();
            $controller->settings((array) $controllerSettings);
            $controller->responsible = $this->routes;
            $response = $controller->run();

            return $response;
        }
    }

    /**
     * [setPostBody Set the post body payload]
     * @param [array] $payload
     */
    public function setPostBody($payload)
    {
        $payloadPost = [
            'post' => $payload
        ];

        if( isset($this->requestBody['payload']) ) {
            array_merge($this->requestBody['payload'], $payloadPost);
            return;
        }

        $this->requestBody = $payloadPost;
    }

    /**
     * [setRequestBody Set the request body payload]
     * @param [string] $payload
     */
    public function setRequestBody($payload)
    {
        $payload = ltrim($payload, 'payload=');
        $cipher = new encoder\cipher;
        $this->requestBody = $cipher->jsonDecode($cipher->decode($payload));
    }

    /**
     * [getRequestBody Get the request body defaults to empty array]
     * @return [array]
     */
    public function getRequestBody()
    {
        if( isset($this->requestBody['payload']) ) {
            return $this->requestBody['payload'];
        }
        return $this->requestBody;
    }

    /**
     * [baseRoute description]
     * @param  [type] $directory [description]
     * @return [type]            [description]
     */
    public function baseApiRoot($directory)
    {
        $this->root = $directory;
    }

    /**
     * [route]
     * @return [array]
     */
    public function route($customRoute = '')
    {
        $base = new route\base;

        $base_url = $base->url();
        $base_uri = (!empty($customRoute)) ? $customRoute : $base->uri();
        $base_uri = substr($base_uri, 0, 1) !== '/' ? '/' . $base_uri : $base_uri;

        /**
         * Get the routes exit if any errors
         */
        $routes = $this->getRoutes($base_uri);

        $this->routes = array(
            'base' => array(
                'protocol' => $base->protocol(),
                'path' => $base->basepath(),
                'root' => $this->root,
                'url' => $base_url,
            ),
            'url' => array(
                'full' => $base_url . $base->basepath() . $base_uri,
                'path' => $base_uri,
                'query' => $this->queryString(),
            ),
            'route' => array(
                'api' => $routes[0],
                'tiers' => $routes,
                'size' => sizeof($routes),
                'scope' => $this->scope,
            ),
        );

        $this->routes = (object) $this->routes;

        return $this->routes;
    }

    /**
     * [getRoutes description]
     * @return [type] [description]
     */
    private function getRoutes($base_uri)
    {
        $routes = explode('/', $base_uri);
        $routes = array_values(array_filter($routes));

        if (empty($routes)) {
            (new exception\errorException)->error('NOT_FOUND');
        }

        foreach ($routes as $r => $route) {
            $routes[$r] = trim($route);
        }

        return $routes;
    }

    /**
     * [systemAccess Is system access allowed]
     * @return [boolean]
     */
    public function systemAccess($account)
    {
        if (empty($account) || !isset($account->uid)) {
            return;
        }

        if ($account->uid > 0 && $this->getScope() == 'system') {
            return;
        }

        return true;
    }

    /**
     * [queryString]
     * @return [string]
     */
    public function queryString()
    {
        if (isset($_GET) && !empty($_GET)) {
            return http_build_query($_GET);
        }
        return;
    }

    /**
     * [setScope Set the router scope]
     * @param [string] $scope
     */
    public function setScope($scope)
    {
        $this->routes->route['scope'] = $scope;
    }

    /**
     * [getScope Get the router scope]
     * @return [string]
     */
    public function getScope()
    {
        return $this->routes->route['scope'];
    }

    /**
     * [getApi Name of the API request]
     * @return [string]
     */
    public function getApi()
    {
        return $this->routes->route['api'];
    }

    /**
     * [getController Controller build settings]
     * @return [array]
     */
    public function getController()
    {
        if (!isset($this->routes->endpoint)) {
            (new exception\errorException)->error('NOT_FOUND');
        }
        return $this->routes->endpoint;
    }

    /**
     * [getSize Size of the tier request]
     * @return [string]
     */
    public function getSize()
    {
        return $this->routes->route['size'];
    }

    /**
     * [getPath URi Path request]
     * @return [string]
     */
    public function getPath()
    {
        return $this->routes->url['path'];
    }

    /**
     * [getIssuer Get the domain issuer]
     * @return [string]
     */
    public function getIssuer($protocol = false)
    {
        if (!isset($this->routes->base)) {
            $base = new route\base;
            return $base->url();
        }

        if (!$protocol) {
            return str_replace(
                array(
                    $this->routes->base['protocol'],
                    '://',
                ),
                '',
                $this->routes->base['url']
            );
        }

        return $this->routes->base['url'];
    }
}
