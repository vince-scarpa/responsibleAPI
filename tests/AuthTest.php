<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use responsible\responsible;
use responsible\core\server;
use responsible\core\auth;
use responsible\core\headers;
use responsible\core\exception\responsibleException;

final class AuthTest extends TestCase
{
    private $options;

    private $jwt;

    private $token;
    
    private $accessToken;

    private const FAKE_KEY = 'mock-key';
    private const FAKE_TOKEN = 'abc.123.456';

    public function setUp():void
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
        $this->assertEquals(true, is_string($this->accessToken));

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($this->accessToken)
            ->key($this->options['mock'])
            ->decode()
        ;

        $this->assertEquals($apiOptions->getMockJwtPayload(), $decoded);
    }

    /**
     * Test if JWT is not valid and throws an exception
     */
    public function testJWTIsNotValid(): void
    {
        $apiOptions = new options;
        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
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
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->jwt
            ->setOptions($this->options)
            ->token()
            ->key(self::FAKE_KEY)
            ->decode()
        ;
    }

    /**
     * Test if JWT secret is empty and throws an exception
     */
    public function testAccessSecretKeyIsEmpty(): void
    {
        $apiOptions = new options;
        $token = $apiOptions->getAccessToken();

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($token)
            ->key()
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
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->jwt
            ->setOptions($this->options)
            ->token($token)
            ->key($this->options['mock'])
            ->decode()
        ;
    }

    /**
     * Test Basic authentication
     */
    public function testCanAccessAuthorizationHeaders(): void
    {
        $apiOptions = new options;

        $credentials = base64_encode('testusername:testpassword');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic '.$credentials;
        $_REQUEST['grant_type'] = 'client_credentials';

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $server = new server([], $this->options);
        $server->authenticate();
    }

    /**
     * Test Bearer authentication
     */
    public function testCanAccessAuthorizationBearer(): void
    {
        $apiOptions = new options;

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer '.$this->accessToken;
        $_REQUEST['grant_type'] = 'refresh_token';

        $server = new server([], $this->options);
        $server->authenticate();

        $this->assertEquals(
            true,
            $server->getInstance('auth')->isGrantType()
        );
    }

    /**
     * Test if we don't add iss property to JWT "payload" request should fail
     */
    public function testAuthoriseFailWhenNoISSPayload(): void
    {
        $apiOptions = new options;
        $requestTime = $_SERVER['REQUEST_TIME'];

        $payload = [
            'sub' => $this->options['mock'],
            'iat' => $requestTime,
            'exp' => $requestTime+300,
            'nbf' => $requestTime,
        ];

        $accessToken = $this->jwt->key($this->options['mock'])
            ->setOptions($this->options)
            ->setPayload($payload)
            ->encode()
        ;

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($accessToken)
            ->key($this->options['mock'])
            ->decode()
        ;
    }

    /**
     * Test if we don't add nbf property to JWT "payload" request should fail
     */
    public function testAuthoriseFailWhenNoNBFPayload(): void
    {
        $apiOptions = new options;
        $requestTime = $_SERVER['REQUEST_TIME'];

        $payload = [
            'sub' => $this->options['mock'],
            'iat' => $requestTime,
            'iss' => 'http://localhost',
            'exp' => $requestTime+300,
        ];

        $accessToken = $this->jwt->key($this->options['mock'])
            ->setOptions($this->options)
            ->setPayload($payload)
            ->encode()
        ;

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($accessToken)
            ->key($this->options['mock'])
            ->decode()
        ;
    }

    /**
     * Test if we don't add iat property to JWT "payload" request should fail
     */
    public function testAuthoriseFailWhenNoIATPayload(): void
    {
        $apiOptions = new options;
        $requestTime = $_SERVER['REQUEST_TIME'];

        $payload = [
            'sub' => $this->options['mock'],
            'iss' => 'http://localhost',
            'exp' => $requestTime+300,
            'nbf' => $requestTime,
        ];

        $accessToken = $this->jwt->key($this->options['mock'])
            ->setOptions($this->options)
            ->setPayload($payload)
            ->encode()
        ;

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($accessToken)
            ->key($this->options['mock'])
            ->decode()
        ;
    }

    /**
     * Test if iat property is greater than current time
     */
    public function testAuthoriseFailWhenIatIsGreaterThanNow(): void
    {
        $apiOptions = new options;
        $requestTime = $_SERVER['REQUEST_TIME'];

        $payload = [
            'sub' => $this->options['mock'],
            'iss' => 'http://localhost',
            'iat' => $requestTime+84600,
            'exp' => $requestTime+300,
            'nbf' => $requestTime,
        ];

        $accessToken = $this->jwt->key($this->options['mock'])
            ->setOptions($this->options)
            ->setPayload($payload)
            ->encode()
        ;

        $message = $apiOptions->getExceptionMessage('NO_CONTENT');
        $message['MESSAGE']['error'] = 'not ready';
        $message['MESSAGE']['description'] = 'The token supplied is not ready to be accessed at the moment.';
        $exceptionMessage = json_encode($message,
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($accessToken)
            ->key($this->options['mock'])
            ->decode()
        ;
    }

    /**
     * Test if nbf property is greater than current time
     */
    public function testAuthoriseFailWhenNbfIsGreaterThanNow(): void
    {
        $apiOptions = new options;
        $requestTime = $_SERVER['REQUEST_TIME'];

        $payload = [
            'sub' => $this->options['mock'],
            'iss' => 'http://localhost',
            'iat' => $requestTime,
            'exp' => $requestTime+300,
            'nbf' => $requestTime+84600,
        ];

        $accessToken = $this->jwt->key($this->options['mock'])
            ->setOptions($this->options)
            ->setPayload($payload)
            ->encode()
        ;

        $message = $apiOptions->getExceptionMessage('NO_CONTENT');
        $message['MESSAGE']['error'] = 'not ready';
        $message['MESSAGE']['description'] = 'The token supplied is not ready to be accessed at the moment.';
        $exceptionMessage = json_encode($message,
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($accessToken)
            ->key($this->options['mock'])
            ->decode()
        ;
    }

    /**
     * Test if exp has expired
     */
    public function testAuthoriseFailWhenTokenExpired(): void
    {
        $apiOptions = new options;
        $requestTime = $_SERVER['REQUEST_TIME'];

        $payload = [
            'sub' => $this->options['mock'],
            'iss' => 'http://localhost',
            'iat' => $requestTime,
            'exp' => $requestTime-84600,
            'nbf' => $requestTime,
        ];

        $accessToken = $this->jwt->key($this->options['mock'])
            ->setOptions($this->options)
            ->setPayload($payload)
            ->encode()
        ;
        
        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($accessToken)
            ->key($this->options['mock'])
            ->decode()
        ;
    }

    /**
     * Test if we don't add exp property to JWT "payload" request should fail
     */
    public function testAuthoriseFailWhenNoExpPayload(): void
    {
        $apiOptions = new options;
        $requestTime = $_SERVER['REQUEST_TIME'];

        $payload = [
            'sub' => $this->options['mock'],
            'iss' => 'http://localhost',
            'iss' => $requestTime,
            'nbf' => $requestTime,
        ];

        $accessToken = $this->jwt->key($this->options['mock'])
            ->setOptions($this->options)
            ->setPayload($payload)
            ->encode()
        ;

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($accessToken)
            ->key($this->options['mock'])
            ->decode()
        ;
    }

    /**
     * Test if we don't add exp property to JWT "payload" request should fail
     */
    public function testAuthoriseAnonymousScope(): void
    {
        $apiOptions = new options;
        $requestTime = $_SERVER['REQUEST_TIME'];

        $payload = [
            'scope' => 'anonymous',
            'iss' => 'http://localhost',
            'iat' => $requestTime,
            'exp' => $requestTime-84600,
            'nbf' => $requestTime,
        ];

        $accessToken = $this->jwt->key($this->options['mock'])
            ->setOptions($this->options)
            ->setPayload($payload)
            ->encode()
        ;

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($accessToken)
            ->key($this->options['mock'])
            ->decode()
        ;

        $this->assertEquals($payload, $decoded);
    }

    /**
     * Test wrong secret key
     */
    public function testAuthoriseFailsWithWrongSecret(): void
    {
        $apiOptions = new options;

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($this->accessToken)
            ->key(self::FAKE_KEY)
            ->decode()
        ;
    }

    /**
     * Test JWT header doesn't contain typ "type = JWT"
     */
    public function testAuthoriseFailsWithNoHeaderTyp(): void
    {
        $apiOptions = new options;

        $accessToken = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.GuoUe6tw79bJlbU1HU0ADX0pr0u2kf3r_4OdrDufSfQ';

        $exceptionMessage = json_encode($apiOptions->getExceptionMessage('UNAUTHORIZED'),
            JSON_PRETTY_PRINT);
        
        $this->expectException(responsibleException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $decoded = $this->jwt
            ->setOptions($this->options)
            ->token($accessToken)
            ->key($this->options['mock'])
            ->decode()
        ;
    }

    /**
     * Test JWT algorythm support
     */
    public function testAuthoriseWithAlgoSupport(): void
    {
        $apiOptions = new options;

        $payload = $apiOptions->getMockJwtPayload();

        $algoSupported = [
            'HS256','sha256',
            'HS384','sha384',
            'HS512','sha512',
        ];

        foreach ($algoSupported as $a => $suppoerted) {
            $this->options['jwt']['algo'] = $suppoerted;

            $accessToken = $this->jwt->key($this->options['mock'])
                ->setOptions($this->options)
                ->setPayload($payload)
                ->encode()
            ;

            $decoded = $this->jwt
                ->setOptions($this->options)
                ->token($accessToken)
                ->key($this->options['mock'])
                ->decode()
            ;

            try {
                $this->assertEquals($payload, $decoded);
            }catch (\Exception $e) {
                $this->fail("Test failed at (Algo {$suppoerted}) - " . $e);
            }
        }
    }

    /**
     * Test JWT no algorythm support and resolves fallback "HS256"
     */
    public function testAuthoriseWithAlgoNoSupport(): void
    {
        $apiOptions = new options;

        $payload = $apiOptions->getMockJwtPayload();

        $algoSupported = [
            'NOT_SUPPORTED_TEST'
        ];

        foreach ($algoSupported as $a => $suppoerted) {
            $this->options['jwt']['algo'] = $suppoerted;
            $accessToken = $this->jwt->key($this->options['mock'])
                ->setOptions($this->options)
                ->setPayload($payload)
                ->encode()
            ;

            $this->assertEquals([
                'header' => 'HS256',
                'hash' => 'sha256',
            ], $this->jwt->getAlgorithm());
        }
    }
}