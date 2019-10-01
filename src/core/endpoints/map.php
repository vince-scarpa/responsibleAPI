<?php

/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\endpoints
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\endpoints;

use responsible\core\endpoints;
use responsible\core\exception;
use responsible\core\route;

class map extends route\router
{
    /**
     * [$BASE_ENDPOINTS]
     * @var array
     */
    private $BASE_ENDPOINTS = array();

    /**
     * [$BASE_ENDPOINTS]
     * @var array
     */
    private $NAMESPACE_ENDPOINTS = array();

    /**
     * [$registry]
     * @var array
     */
    private $registry = array();

    /**
     * [$options Responsible API options]
     * @var [array]
     */
    private $options;

    /**
     * [$middleWareClass Holds middleware class object]
     * @var [object]
     */
    private static $middleWareClass;

    /**
     * [$SYSTEM_ENDPOINTS Reserved system Endpoints]
     * @var [array]
     */
    const SYSTEM_ENDPOINTS = [
        'token' => '/token/access_token',
        'user' => [
            '/user/create',
            '/user/load',
        ],
    ];

    /**
     * [__construct Silence...]
     */
    public function __construct()
    {
    }

    /**
     * [register]
     * @return [void]
     */
    public function register()
    {
        $options = $this->options;

        /**
         * Check if a custom directory was set in the Responsible API options
         */
        if( (isset($this->options['classRoute']) && !empty($this->options['classRoute'])) && 
            (isset($this->options['classRoute']['directory']) && isset($this->options['classRoute']['namespace']))
        ) {
            $customService = $this->options['classRoute'];
            $directory = $customService['directory'];
            $middleware = $customService['namespace'];

        }else {
            $middleware = 'responsible';

            $endpoint = str_replace(
                array('core', '/', '\\'),
                array('service', DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR),
                __NAMESPACE__
            );

            $directory = $this->route()->base['root'] . '/' . str_replace('responsible/', '', $endpoint);
        }

        if (!is_dir($directory)) {
            (new exception\errorException)
                ->message('Directory Error:: responsible\service needs to exist. See documentation on setting up a service.')
                ->error('NOT_EXTENDED');
        }

        $scanned = array_values(
            array_diff(
                scandir($directory),
                array('..', '.', '.DS_Store')
            )
        );

        if (empty($scanned)) {
            (new exception\errorException)
                ->message('Class Error:: responsible\service\endpoints needs at least 1 class file. See documentation on setting up a service.')
                ->error('NOT_EXTENDED');
        }

        foreach ($scanned as $e => $point) {

            if (substr($point, -4) == '.php') {
                $point = str_replace('.php', '', $point);

                $this->BASE_ENDPOINTS[] = $point;

                $endpoint = str_replace('core', 'service', __NAMESPACE__) . '\\' . $point;
                $endpoint = $middleware . '\\service\\endpoints\\' . $point;
                $child = $endpoint;

                $this->NAMESPACE_ENDPOINTS[$point] = $endpoint;

                if (class_exists($child)) {
                    self::$middleWareClass = new $child;
                    $this->registry[$point] = self::$middleWareClass->register();
                }else{
                    (new exception\errorException)
                        ->message("Class Error:: class {$child} needs to exist. See documentation on setting up a service.")
                        ->error('NOT_EXTENDED');
                }
            }
        }

        return $this->registry;
    }

    /**
     * [isEndpoint]
     * @return [boolean]
     */
    public function isEndpoint($api, $endpoint)
    {
        if (isset(self::SYSTEM_ENDPOINTS[$api]) &&
            (
                in_array($endpoint, self::SYSTEM_ENDPOINTS) ||
                array_search($endpoint, self::SYSTEM_ENDPOINTS[$api]) !== false
            )
        ) {

            $system = new endpoints\system;
            $methodCreate = explode('/', $endpoint);
            $methodCreate = array_values(array_filter($methodCreate));
            $method = '';

            foreach ($methodCreate as $i => $parts) {
                if (preg_match_all('#_#', $parts)) {
                    $parts = str_replace('_', '', lcfirst(ucwords($parts, '_')));
                }
                if ($i > 0) {
                    $method .= ucfirst($parts);
                } else {
                    $method .= $parts;
                }
            }

            $endpointSettings['model'] = array(
                'scope' => 'system',
                'namespace' => 'responsible\core\endpoints\system',
                'class' => 'system',
                'method' => $method,
                'arguments' => '',
            );

            return (object) $endpointSettings;
        }        

        $endpoint = htmlspecialchars($endpoint, ENT_QUOTES, 'UTF-8');
        $index = array_search($api, $this->BASE_ENDPOINTS);

        if ($index !== false) {
            if (isset($this->registry[$api])) {
                $endpointSettings = array(
                    'path' => $endpoint,
                    'model' => array(
                        'namespace' => $this->NAMESPACE_ENDPOINTS[$api],
                        'class' => $this->BASE_ENDPOINTS[$index],
                        'method' => basename($endpoint),
                        'scope' => 'private',
                    ),
                );

                /**
                 * [$found Nothing dynamic, found an exact match]
                 * @var array
                 */
                if ($found = array_search($endpoint, $this->registry[$api]) !== false) {
                    
                    $scope = 'private';
                    if( method_exists($this->NAMESPACE_ENDPOINTS[$api], 'scope') ) {
                        $classScope = (new $this->NAMESPACE_ENDPOINTS[$api])->scope();
                        $position = array_search($endpoint, $this->registry[$api]);
                        
                        if( is_array($classScope) && isset($classScope[$position]) ) {
                            $endpointSettings['model']['scope'] = $classScope[$position];

                        }else{

                            if( !is_array($classScope) ) {
                                $endpointSettings['model']['scope'] = $classScope;
                            }
                        }
                    }
                    return (object) $endpointSettings;
                }

                /**
                 * Check for dynamic uri eg: {asset_id}
                 * Dynamic uri's must be wrapped in {} for a true return
                 */
                foreach ($this->registry[$api] as $i => $path) {
                    $endpointRegister = $path;
                    $methodArgs = [];

                    /**
                     * If comparing the two sizes are not equal
                     * then no use continuing through the loop
                     */
                    if (!$this->uriCheckSize($endpointRegister, $endpoint)) {
                        continue;
                    }

                    /**
                     * This replacment will create a pattern to use as a match all expression
                     * @var [string]
                     */
                    $endpointRegister = preg_replace('@/{(.*?)}@', '/(.*?)', $endpointRegister);

                    if (preg_match_all('@^' . $endpointRegister . '$@i', $endpoint, $dynamicParts)) {
                        $endpointFilter = $this->filterParts($endpoint, $dynamicParts);
                        $model = $this->getClassModel($path);

                        /**
                         * Find the dynamic parts and set them as argument key(s)
                         * then combine them with the endpoint request and set the request parts
                         * as argument value(s)
                         */
                        if (preg_match_all("/(?<={).*?(?=})/", $path, $registerParts)) {
                            if (isset($registerParts[0][0])) {
                                $registerParts = $registerParts[0];

                                if (sizeof($endpointFilter) == sizeof($registerParts)) {
                                    $methodArgs = array_combine($registerParts, $endpointFilter);
                                }
                            }
                        }

                        $scope = 'private';
                        if( method_exists($this->NAMESPACE_ENDPOINTS[$api], 'scope') ) {
                            $classScope = (new $this->NAMESPACE_ENDPOINTS[$api])->scope();
                            $position = array_search($path, $this->registry[$api]);
                            
                            if( is_array($classScope) && isset($classScope[$position]) ) {
                                $scope = $classScope[$position];

                            }else{

                                if( !is_array($classScope) ) {
                                    $scope = $classScope;
                                }
                            }
                        }

                        $endpointSettings['model'] = array(
                            'scope' => $scope,
                            'namespace' => $this->NAMESPACE_ENDPOINTS[$api],
                            'class' => $model['class'],
                            'method' => $model['method'],
                            'arguments' => $methodArgs,
                        );

                        return (object) $endpointSettings;
                    } else {
                        continue;
                    }
                }
            }
        }

        return;
    }

    /**
     * [filterParts Prepare routed parts]
     * @return [array]
     */
    private function filterParts($uri, $parts)
    {
        $filter = array();

        foreach ($parts as $p => $part) {
            if (is_array($part)) {
                foreach ($part as $i => $parti) {
                    if ($parti !== $uri) {
                        $filter[] = $parti;
                    }
                }
            }
        }

        return $filter;
    }

    /**
     * [uriCheckSize]
     *
     * Compare the current request endpoint with the registered endpoint
     * only return the same tier sizes
     *
     * @return [boolean]
     */
    private function uriCheckSize($endpointRegister, $endpoint)
    {
        $registerExplode = explode('/', $endpointRegister);
        $endpointExplode = explode('/', $endpoint);
        return (sizeof($registerExplode) === sizeof($endpointExplode));
    }

    /**
     * [getClassModel Class, Method]
     * @return [array]
     */
    private function getClassModel($request_path)
    {
        $cm = explode('/', $request_path);

        if (!empty($cm) && sizeof($cm) >= 2) {
            $cm = array_values(array_filter($cm));

            return array(
                'class' => $cm[0],
                'method' => $cm[1],
            );
        }

        return;
    }

    /**
     * [options Inherit options from server]
     * @param  [array] $options
     */
    public function options($options)
    {
        $this->options = $options;
    }
}
