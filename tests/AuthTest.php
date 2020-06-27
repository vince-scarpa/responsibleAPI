<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\server;
use responsible\core\auth;
use responsible\core\headers;
use responsible\core\exception\resposibleException;

final class AuthTest extends TestCase
{
    private $options;

    private $jwt;

    private $token;
    
    private $accessToken;

    private const FAKE_KEY = 'mock-key';
    private const FAKE_TOKEN = 'abc.123.456';

    public function setUp()
    {
        $apiOptions = new options;
        $this->jwt = new auth\jwt;
        $this->options = $apiOptions->getApiOptions();
        $this->accessToken = $apiOptions->getaccessToken();
    }

    /**
     * Test if JWT is valid
     */
    public function testJWTCanEncodeAndDecode(): void
    {
        $apiOptions = new options;
        $options = $apiOptions->getApiOptions();
        $this->assertEquals(true, is_string($this->accessToken));

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($this->accessToken)
            ->key($options['mock'])
            ->decode()
        ;

        $this->assertEquals($apiOptions->getMockJwtHeader(), $decoded);
    }

    /**
     * Test if JWT is not valid and throws an exception
     */
    public function testJWTIsNotValid(): void
    {
        $apiOptions = new options;
        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(resposibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->jwt
            ->setOptions($this->options)
            ->token(self::FAKE_TOKEN)
            ->key(self::FAKE_KEY)
            ->decode()
        ;
    }

    /**
     * Test if JWT is empty and throws an exception
     */
    public function testAccessTokenIsEmpty(): void
    {
        $apiOptions = new options;
        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(resposibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->jwt
            ->setOptions($this->options)
            ->token()
            ->key(self::FAKE_KEY)
            ->decode()
        ;
    }

    /**
     * Test if JWT segments are not 3 throw error
     */
    public function testAccessTokenWrongSegmentAmmount(): void
    {
        $apiOptions = new options;

        $token = $apiOptions->getAccessToken();
        $token = explode('.', $token);
        $token = \implode('.', \array_splice($token, 0, 2));

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(resposibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->jwt
            ->setOptions($this->options)
            ->token($token)
            ->key($this->options['mock'])
            ->decode()
        ;
    }

    /**
     * Test if JWT segments are not 3 throw error
     */
    public function testCanAccessAuthorizationHeaders(): void
    {
        $apiOptions = new options;

        $credentials = base64_encode('testusername:testpassword');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic '.$credentials;
        $_REQUEST['grant_type'] = 'client_credentials';

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(resposibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $server = new server([], $this->options);
        $server->authenticate();
    }

    /**
     * Test if JWT segments are not 3 throw error
     */
    public function testCanAccessAuthorizationBearer(): void
    {
        $apiOptions = new options;

        $credentials = base64_encode('testusername:testpassword');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$this->accessToken;
        $_REQUEST['grant_type'] = 'refresh_token';

        $server = new server([], $this->options);
        $server->authenticate();

        $this->assertEquals(
            true,
            $server->getInstance('auth')->isGrantType()
        );
    }
}