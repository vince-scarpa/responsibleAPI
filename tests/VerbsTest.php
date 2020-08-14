<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\server;
use responsible\core\headers;
use responsible\core\auth;
use responsible\core\exception\responsibleException;

final class VerbsTest extends TestCase
{
    private $options;

    private $jwt;
    
    private $headers;

    private $token;
    
    private $accessToken;

    private const MOCK_PAYLOAD = ['a' =>'b', 'foo' => 'bar'];

    public function setUp()
    {
        $apiOptions = new options;
        $this->jwt = new auth\jwt;
        $this->headers = new headers\header;
        $this->options = $apiOptions->getApiOptions();
        $this->accessToken = $apiOptions->getaccessToken();

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$this->accessToken;
    }

    /**
     * Test Check request method GET
     */
    public function testResponsibleAPIRequestGET(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['query'] = self::MOCK_PAYLOAD;

        $responsible = new responsible($this->options, false);
        $responsible->setOption('route', '/mock/123456789');
        $responsible->run();

        $this->headers->requestMethod();
        $method = $this->headers->getMethod();
        $payloadBody = $this->headers->getBody();

        $this->assertEquals('get', $method->method);
        $this->assertEquals($_GET, $payloadBody);
    }

    /**
     * Test Check request method POST
     */
    public function testResponsibleAPIRequestPOST(): void
    {
        $header = new headers\header;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['query'] = ['a' =>'b', 'foo' => 'bar'];
        $_POST['someData'] = ['a' =>'b', 'foo' => 'bar'];

        $responsible = new responsible($this->options, false);
        $responsible->setOption('route', '/mock/123456789');
        $responsible->run();

        $this->headers->requestMethod();
        $method = $this->headers->getMethod();
        $payloadBody = $this->headers->getBody();

        $this->assertEquals('post', $method->method);

        $expected = array_merge($_GET, $_POST);
        $this->assertEquals($expected, $payloadBody);
    }

    /**
     * Test Check request method PUT
     */
    public function testResponsibleAPIRequestPUT(): void
    {
        $header = new headers\header;

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_GET['query'] = ['a' =>'b', 'foo' => 'bar'];
        $_POST['someData'] = ['a' =>'b', 'foo' => 'bar'];

        $responsible = new responsible($this->options, false);
        $responsible->setOption('route', '/mock/123456789');
        $responsible->run();

        $this->headers->requestMethod();
        $method = $this->headers->getMethod();
        $payloadBody = $this->headers->getBody();

        $this->assertEquals('put', $method->method);

        $expected = array_merge($_GET, $_POST);
        $this->assertEquals($expected, $payloadBody);
    }

    /**
     * Test Check request method PUT with content type header set as json
     */
    public function testResponsibleAPIRequestPUTJson(): void
    {
        $header = new headers\header;

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_GET['query'] = ['a' =>'b', 'foo' => 'bar'];
        $_POST['someData'] = ['a' =>'b', 'foo' => 'bar'];

        $responsible = new responsible($this->options, false);
        $responsible->setOption('route', '/mock/123456789');
        $responsible->run();

        $this->headers->requestMethod();
        $method = $this->headers->getMethod();
        $payloadBody = $this->headers->getBody();

        $this->assertEquals('put', $method->method);

        $expected = array_merge($_GET, $_POST);
        $this->assertEquals($expected, $payloadBody);
    }

    /**
     * Test Check request method PATCH
     */
    public function testResponsibleAPIRequestPATCH(): void
    {
        $header = new headers\header;

        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        $_GET['query'] = ['a' =>'b', 'foo' => 'bar'];
        $_POST['someData'] = ['a' =>'b', 'foo' => 'bar'];

        $responsible = new responsible($this->options, false);
        $responsible->setOption('route', '/mock/123456789');
        $responsible->run();

        $this->headers->requestMethod();
        $method = $this->headers->getMethod();
        $payloadBody = $this->headers->getBody();

        $this->assertEquals('patch', $method->method);

        $expected = array_merge($_GET, $_POST);
        $this->assertEquals($expected, $payloadBody);
    }

    /**
     * Test Check request method DELETE
     */
    public function testResponsibleAPIRequestDELETE(): void
    {
        $header = new headers\header;

        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_GET['query'] = ['a' =>'b', 'foo' => 'bar'];

        $responsible = new responsible($this->options, false);
        $responsible->setOption('route', '/mock/123456789');
        $responsible->run();

        $this->headers->requestMethod();
        $method = $this->headers->getMethod();
        $payloadBody = $this->headers->getBody();

        $this->assertEquals('delete', $method->method);
        $this->assertEquals($_GET, $payloadBody);
    }
}