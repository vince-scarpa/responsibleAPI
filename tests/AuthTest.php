<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\auth;
use responsible\core\headers;
use responsible\core\exception as ResponsibleError;

final class AuthTest extends TestCase
{
    private $options;

    private $requestTime;

    private const SECRET_KEY = 'mock-key';

    private const TOKEN = 'abc-123';

    public function setUp()
    {
        $apiOptions = new options;
        $this->options = $apiOptions->getApiOptions();
        $this->requestTime = $_SERVER['REQUEST_TIME'];
    }

    /**
     * @test Test if JWT is valid
     */
    public function testJWTCanEncodeAndDecode(): void
    {
        $jwt = new auth\jwt;

        $payload = [
            'iss' => 'http://localhost',
            'iat' => $this->requestTime,
            'exp' => $this->requestTime,
            'nbf' => $this->requestTime,
        ];

        $encoded = $jwt->key(self::SECRET_KEY)
            ->setOptions($this->options)
            ->setPayload($payload)
            ->encode()
        ;

        $this->assertEquals(true, is_string($encoded));

        $decoded = $jwt
            ->setOptions($this->options)
            ->token($encoded)
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
        $jwt = new auth\jwt;

        $this->expectException(\Exception::class);

        $jwt->setOptions($this->options)
            ->token(self::TOKEN)
            ->key(self::SECRET_KEY)
            ->decode()
        ;
    }
}