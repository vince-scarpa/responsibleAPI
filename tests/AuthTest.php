<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\auth;
use responsible\core\headers;
use responsible\core\exception\resposibleException;

final class AuthTest extends TestCase
{
    private $options;

    private $requestTime;

    private $jwt;
    
    private $accessToken;

    private const SECRET_KEY = 'mock-key';

    private const TOKEN = 'abc-123';

    public function setUp()
    {
        $this->jwt = new auth\jwt;
        $apiOptions = new options;
        $this->options = $apiOptions->getApiOptions();
        $this->requestTime = $_SERVER['REQUEST_TIME'];

        $payload = [
            'iss' => 'http://localhost',
            'iat' => $this->requestTime,
            'exp' => $this->requestTime+300,
            'nbf' => $this->requestTime,
        ];

        $this->accessToken = $this->jwt->key(self::SECRET_KEY)
            ->setOptions($this->options)
            ->setPayload($payload)
            ->encode()
        ;
    }

    /**
     * @test Test if JWT is valid
     */
    public function testJWTCanEncodeAndDecode(): void
    {
        $this->assertEquals(true, is_string($this->accessToken));

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($this->accessToken)
            ->key(self::SECRET_KEY)
            ->decode()
        ;

        $this->assertEquals(true, (is_array($decoded)&&!empty($decoded)) );
    }

    /**
     * @test Test if JWT is not valid and throws an exception
     */
    public function testJWTIsNotValid(): void
    {
        $this->expectException(resposibleException::class);

        $this->jwt->setOptions($this->options)
            ->token(self::TOKEN)
            ->key(self::SECRET_KEY)
            ->decode()
        ;
    }
}