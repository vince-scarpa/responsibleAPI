<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\server;
use responsible\core\exception\httpException;

final class ApiTest extends TestCase
{
    private $options;

    public function setUp()
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
        $this->expectException(httpException::class);
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
        
        $this->expectException(httpException::class);
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
            $this->fail("Response is not set in test case 'ApiTest::testApiServerRouter()'");
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
        
        $this->expectException(httpException::class);
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
        
        $this->expectException(httpException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $server->route('/mock/123456789');
    }
}