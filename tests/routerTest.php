<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\server;
use responsible\core\exception\responsibleException;

final class routerTest extends TestCase
{
    private $options;

    public function setUp()
    {
        $apiOptions = new options;
        $this->options = $apiOptions->getApiOptions();
    }

    /**
     * Test test router
     */
    public function testRouter(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        $server = new server([], $this->options);
        $server->rateLimit();
        $server->route('/mock/123456789');

        $router = $server->getInstance('routerClass');

        $scope = $router->getScope();
        $api = $router->getApi();
        $issuer = $router->getIssuer();

        $this->assertEquals('private', $scope);
        $this->assertEquals('mock', $api);
        $this->assertEquals('localhost', $issuer);
    }

    /**
     * Test test public routing
     */
    public function testRouterAnonymousRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        $server = new server([], $this->options);
        $server->rateLimit();
        $server->route('/mock/123456789/public');

        $router = $server->getInstance('routerClass');

        $scope = $router->getScope();
        $this->assertEquals('anonymous', $scope);
    }

    /**
     * Test test public routing
     */
    public function testRouterPostToRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['payload'] = ['some' =>'data'];
        
        $server = new server([], $this->options);
        $server->requestType('json');
        $server->rateLimit();
        $server->route('/mock/123456789/post');

        $router = $server->getInstance('routerClass');

        $body = $router->getBody();
        $this->assertEquals(true, is_array($body));
    }
}