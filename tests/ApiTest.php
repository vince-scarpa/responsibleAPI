<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\server;
use responsible\core\exception\responsibleException;

final class ApiTest extends TestCase
{
    private $options;

    public function setUp():void
    {
        $apiOptions = new options;
        $this->options = $apiOptions->getApiOptions();
    }

    /**
     * Test if the Responsible API server can initialise
     */
    public function testServerCanInitialise(): void
    {
        $server = new server([], $this->options);
        $this->assertEmpty($server->getResponse());
    }

    /**
     * Test if the Responsible API server exception
     */
    public function testApiServerUnauthorisedException(): void
    {
        $server = new server([], $this->options);
        $this->expectException(responsibleException::class);
        $server->authenticate();
    }

    /**
     * Test if the Responsible API server exception and message
     */
    public function testApiServerUnauthorisedExceptionAndMessage(): void
    {
        $server = new server([], $this->options);
        $apiOptions = new options;
        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $server->authenticate();
    }

    /**
     * Test if the Responsible API server router
     */
    public function testApiServerRouter(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $server = new server([], $this->options);
        $server->rateLimit();
        $server->route('/mock/123456789');

        $response = $server->getResponse();

        $this->assertArrayHasKey('response', $response);
        $this->assertNotEmpty($response);

        if (!isset($response['response'])) {
            $this->fail("There was no response in test case 'ApiTest::testApiServerRouter()'");
        }

        $expected = ['mock_run' => ['passed' => true]];
        $this->assertEquals($expected, $response['response']);
    }

    /**
     * Test test double response key gets resolved
     */
    public function testServerSetResponseKeys(): void
    {
        $server = new server([], $this->options);
        
        $server->setResponse('response', ['payload' => 'data']);
        $server->setResponse('response', ['payload2' => 'data2']);
        $response = $server->getResponse();

        $this->assertArrayHasKey('response', $response);
        $this->assertNotEmpty($response);

        if (!isset($response['response'])) {
            $this->fail("There was no response in test case 'ApiTest::testServerSetResponseKeys()'");
        }

        $this->assertEquals([
            'payload' => 'data',[
               'payload2' => 'data2'
            ]
        ], $response['response']);
    }

    /**
     * Test get the Responsible API server response in JSON format
     */
    public function testApiServerCanGetResponseJSON(): void
    {
        $apiOptions = new options;

        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        $server = new server([], $this->options);
        $server->rateLimit();
        $server->route('/mock/123456789');

        $response = $server->response();
        $response = json_decode($response, true);

        $this->assertArrayHasKey('response', $response);
        $this->assertNotEmpty($response);

        if (!isset($response['response'])) {
            $this->fail("There was no response in test case 'ApiTest::testApiServerCanGetResponseJSON()'");
        }

        $expected = ['mock_run' => ['passed' => true]];
        $this->assertEquals($expected, $response['response']);
    }

    /**
     * Test if the Responsible API server router can fail with a bad request message
     */
    public function testApiServerRouterFail(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $apiOptions = new options;
        $server = new server([], $this->options);
        $server->rateLimit();

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('BAD_REQUEST'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $server->route('/mock/123456789/123');
    }

    /**
     * Test if the Responsible API server router can fail with a wrong request method
     */
    public function testApiServerRouterWrongRequestMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';

        $apiOptions = new options;
        $server = new server([], $this->options);
        $server->rateLimit();

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('METHOD_NOT_ALLOWED'), JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $server->route('/mock/123456789');
    }

    /**
     * Test if the Responsible API server router can set an alternative request type
     */
    public function testApiServerCanSetRequestType(): void
    {
        $apiOptions = new options;
        $server = new server([], $this->options);
        $server->requestType('array');

        $this->assertEquals('array', $server->getRequestType());
    }

    /**
     * Test Check if class instance load returns null when doesn't exist
     */
    public function testServerClassDependenciesNull(): void
    {
        $server = new server([], $this->options);
        $doesntExist = $server->getInstance('doesntExist');
        $this->assertEquals(null, $doesntExist);
    }

    /**
     * Test Check if Responsible API access is denied
     */
    public function testResponsibleAPIDenied(): void
    {
        $apiOptions = new options;
        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);

        $responsible = responsible::API($this->options);
        $response = $responsible::response();

        $this->assertEquals($exceptionMessage, $response);
    }

    /**
     * Test Check if class instance load returns null when doesn't exist
     */
    public function testResponsibleAPIAllowsAccess(): void
    {
        $apiOptions = new options;

        $credentials = base64_encode('mockusername:mockpassword');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic '.$credentials;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_REQUEST['grant_type'] = 'client_credentials';
        $this->options['route'] = '/mock/123456789';

        $responsible = responsible::API($this->options);
        $response = $responsible::response();

        $this->assertArrayHasKey('response', $response);
        $this->assertNotEmpty($response);

        if (!isset($response['response'])) {
            $this->fail("There was no response in test case 'ApiTest::testResponsibleAPIAllowsAccess()'");
        }

        $expected = ['mock_run' => ['passed' => true]];
        $this->assertEquals($expected, $response['response']);
    }
}