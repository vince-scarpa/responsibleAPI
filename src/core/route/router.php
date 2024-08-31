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
    use \responsible\core\traits\optionsTrait;

    /**
     * [$root Responsible API root path]
     * @var string
     */
    private $root;

    /**
     * [$scope Router scope]
     * @var string
     */
    private $scope = 'private';

    /**
     * [$routes]
     * @var object
     */
    private $routes;

    /**
     * [$requestBody]
     * @var array
     */
    private $requestBody = [];

    /**
     * [$requestBody]
     * @var array
     */
    private $payloadBody = [];

    /**
     * [run Try run the request method]
     * @return array|object
     */
    public function run()
    {
        $controllerSettings = $this->getController();
        $controller = $controllerSettings->model['namespace'];

        if (class_exists($controller)) {
            $controller = new $controller();

            $neededMethods = [
                'headerMethods',
                'settings',
                'run',
            ];

            foreach ($neededMethods as $method) {
                if (!method_exists($controller, $method)) {
                    (new exception\errorException())
                        ->setOptions($this->getOptions())
                        ->message(
                            "There's a method missing in '"
                            . $controllerSettings->model['class']
                            . "' {$method}() must be declared."
                        )
                        ->error('NOT_EXTENDED');
                }
            }

            $controller->responsible = new \stdClass();
            $controller->responsible = $this->getRoutes();

            $controller->headerMethods();
            $controller->settings((array) $controllerSettings);

            $response = $controller->run();

            return $response;
        }
    }

    /**
     * [setPostBody Set the post body payload]
     * @param array $payload
     */
    public function setPostBody($payload)
    {
        if (!empty($this->payloadBody)) {
            $payload = array_merge($this->payloadBody, ['post' => $payload]);
        }

        if (!empty($this->requestBody)) {
            $payload = array_merge($this->requestBody, ['post' => $payload]);
        }

        $this->payloadBody = $payload;
    }

    /**
     * [setRequestBody Set the request body payload]
     * @param string $payload
     */
    public function setRequestBody($payload)
    {
        if (is_array($payload)) {
            $this->requestBody = $payload;
            return;
        }

        $payload = ltrim($payload, 'payload=');
        $cipher = new encoder\cipher();
        $this->requestBody = $cipher->jsonDecode($cipher->decode($payload));
    }

    /**
     * [getBody]
     * @return array
     */
    public function getBody()
    {
        return $this->payloadBody;
    }

    /**
     * [getRequestBody Get the request body defaults to empty array]
     * @return array
     */
    public function getRequestBody()
    {
        if (isset($this->requestBody['payload'])) {
            return $this->requestBody['payload'];
        }
        return $this->requestBody;
    }

    /**
     * [baseRoute]
     * @param  string $directory
     */
    public function baseApiRoot($directory)
    {
        $this->root = $directory;
    }

    /**
     * [route]
     * @return object
     */
    public function route($customRoute = '')
    {
        $base = new route\base();

        $base_url = $base->url();
        $base_uri = (!empty($customRoute)) ? $customRoute : $base->uri();
        $base_uri = substr($base_uri, 0, 1) !== '/' ? '/' . $base_uri : $base_uri;

        /**
         * Get the routes exit if any errors
         */
        $routes = $this->getTierList($base_uri);

        $routesArray = array(
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
            'responseType' => null,
        );

        $this->setRoutes((object)$routesArray);

        return $this->getRoutes();
    }

    /**
     * [getTierList]
     * @return array
     */
    private function getTierList($base_uri)
    {
        $routes = explode('/', $base_uri);
        $routes = array_values(array_filter($routes));

        if (empty($routes)) {
            (new exception\errorException())
                ->setOptions($this->getOptions())
                ->error('NOT_FOUND');
        }

        foreach ($routes as $r => $route) {
            $routes[$r] = trim($route);
        }

        return $routes;
    }

    /**
     * [systemAccess Is system access allowed]
     * @return boolean
     */
    public function systemAccess($account)
    {
        if (empty($account) || !isset($account->uid)) {
            return false;
        }

        if ($account->uid > 0 && $this->getScope() == 'system') {
            return false;
        }

        return true;
    }

    /**
     * [queryString]
     * @return string|null
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
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->getRoutes()->route['scope'] = $scope;
    }

    /**
     * [getScope Get the router scope]
     * @return string
     */
    public function getScope()
    {
        return $this->getRoutes()->route['scope'];
    }

    /**
     * [getApi Name of the API request]
     * @return string
     */
    public function getApi()
    {
        return $this->getRoutes()->route['api'];
    }

    /**
     * [getController Controller build settings]
     * @return array
     */
    public function getController()
    {
        if (!isset($this->getRoutes()->endpoint)) {
            (new exception\errorException())
                ->setOptions($this->getOptions())
                ->error('NOT_FOUND');
        }
        return $this->getRoutes()->endpoint;
    }

    /**
     * [getSize Size of the tier request]
     * @return string
     */
    public function getSize()
    {
        return $this->getRoutes()->route['size'];
    }

    /**
     * [getPath URi Path request]
     * @return string
     */
    public function getPath()
    {
        return $this->getRoutes()->url['path'];
    }

    /**
     * [getIssuer Get the domain issuer]
     * @return string
     */
    public function getIssuer($protocol = false)
    {
        if (!isset($this->getRoutes()->base)) {
            $base = new route\base();
            return $base->url();
        }

        if (!$protocol) {
            return str_replace(
                array(
                    $this->getRoutes()->base['protocol'],
                    '://',
                ),
                '',
                $this->getRoutes()->base['url']
            );
        }

        return $this->getRoutes()->base['url'];
    }

    /**
     * [setRoutes Set the routers object]
     * @param object $routes
     */
    public function setRoutes($routes)
    {
        $this->routes = new \stdClass();
        $this->routes = $routes;
    }

    /**
     * [getRoutes Get the routers object]
     * @return object
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}
