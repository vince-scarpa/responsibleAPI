<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\server;
use responsible\core\encoder;
use responsible\core\exception\responsibleException;

final class routerTest extends TestCase
{
    private $options;

    public function setUp():void
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
     * Test test router get query strings
     */
    public function testRouterGetsQueryStrings(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = [
            'query1' => 'test1',
            'query2' => 'test2',
        ];
        
        $server = new server([], $this->options);
        $server->rateLimit();
        $server->route('/mock/123456789');

        $router = $server->getInstance('routerClass');

        $body = $router->getBody();

        $this->assertEquals(true, is_array($body));
        $this->assertEquals($_GET, $body);
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

    /**
     * Test test public routing
     */
    public function testRouterTierCount(): void
    {   
        $apiOptions = new options;
        $server = new server([], $this->options);
        $server->requestType('json');
        $server->rateLimit();

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('NOT_FOUND'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $server->route('/mock');
    }

    /**
     * Test test public routing
     */
    public function testRouterSendEncryptedPostbody(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $cipher = new encoder\cipher;
        $postData = $cipher->encode(
            $cipher->jsonEncode(['encrypted' =>'data'])
        );
        $_POST['payload'] = 'payload='.$postData;
        
        $server = new server([], $this->options);
        $server->requestType('json');
        $server->rateLimit();
        $server->route('/mock/123456789/post');

        $router = $server->getInstance('routerClass');

        $body = $router->getBody();

        $this->assertArrayHasKey('encrypted', $body);
        $this->assertNotEmpty($body);
    }

    /**
     * Test test public routing
     */
    public function testServiceHasMissingMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $server = new server([], $this->options);
        $server->requestType('json');
        $server->rateLimit();
        
        $exceptionMessage = json_encode(json_decode('{
            "ERROR_CODE": 510,
            "ERROR_STATUS": "API_ERROR",
            "MESSAGE": "There\'s a method missing in \'mockfail\' headerMethods() must be declared."
        }'), JSON_PRETTY_PRINT);
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $server->route('/mockfail/missing/methods');
    }
}