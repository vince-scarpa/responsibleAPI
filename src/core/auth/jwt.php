<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsible.git
 *
 * @api Responible API
 * @package responsible\core\auth
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\auth;

use responsible\core\auth;
use responsible\core\exception;
use responsible\core\keys;
use responsible\core\configuration;

class jwt
{
    /**
     * [CTY - Json Web Token content type]
     * @link https://tools.ietf.org/html/rfc7519#section-5.2
     */
    const CYT = 'JWT';

    /**
     * [$TIMESTAMP Set the current timestamp]
     * @var [type]
     */
    protected static $TIMESTAMP;

    /**
     * [$LEEWAY Cater for time skew in sever time differences]
     * @var integer
     */
    protected static $LEEWAY = 10;

    /**
     * [$EXPIRES Default token expiry]
     * @var integer
     */
    protected $EXPIRES = 86400;

    /**
     * [$algorithyms Supported algorithms]
     * @var [array]
     */
    protected static $ALGORITHMS = [
        'hash_hmac' => 'sha256',
    ];

    /**
     * [$token]
     * @var [string]
     */
    protected $token;

    /**
     * [$key Client secret key]
     * @var [string]
     */
    protected $key;

    /**
     * [$payload Clients payload]
     * @var [string]
     */
    protected $payload;

    /**
     * [__construct]
     */
    public function __construct()
    {
        self::$TIMESTAMP = (new \DateTime('now'))->getTimestamp();
    }

    /**
     * [encode]
     * @return [type]
     */
    public function encode()
    {
        $encode = new auth\jwtEncoder;

        $encoded =
        $encode->setPayload($this->getPayload())
            ->key($this->getKey())
            ->encode()
        ;

        return $encoded;
    }

    /**
     * [decode Decode the token]
     * @return [array]
     */
    public function decode()
    {
        $decode = new auth\jwtDecoder;

        $decoded =
        $decode->token($this->getToken())
            ->key($this->getKey())
            ->decode()
        ;

        return $decoded;
    }

    /**
     * [token Set the token]
     *
     * @param  [string] - Hashed string
     * @return [self]
     */
    public function token($token = null)
    {
        if (is_null($token) || empty($token) || !is_string($token)) {
            (new exception\errorException)
                ->message(self::messages('denied_token'))
                ->error('UNAUTHORIZED');
        }

        $this->token = $token;

        return $this;
    }

    /**
     * [key - Set the secret key]
     *
     * @param  [string] - Assigned client secret keystring
     * @return [self]
     */
    public function key($key = null)
    {
        if (is_null($key) || empty($key) || !is_string($key)) {
            (new exception\errorException)
                ->message(self::messages('denied_key'))
                ->error('UNAUTHORIZED');
        }

        $this->key = $key;

        return $this;
    }

    /**
     * [payload Set the clients payload]
     * @param  [array] $payload
     * @return [self]
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * [getToken Get the Json Web Token]
     * @return [string]
     */
    protected function getToken()
    {
        return $this->token;
    }

    /**
     * [getKey Get the client secret key]
     * @return [string]
     */
    protected function getKey()
    {
        return $this->key;
    }

    /**
     * [getPayload Get the clients payload]
     * @return [string]
     */
    protected function getPayload()
    {
        return $this->payload;
    }

    /**
     * [getLeeway Get the default leeway]
     * @return [integer]
     */
    public function getLeeway()
    {
        return self::$LEEWAY;
    }

    /**
     * [getLeeway Get the default expiry]
     * @return [integer]
     */
    public function getExpires()
    {
        return $this->EXPIRES;
    }

    /**
     * [messages Common error messages]
     * @param  [string] $type [message type]
     * @return [array]
     */
    protected static function messages($type)
    {
        switch ($type) {
            case 'denied_token':
                $error = [
                    'error' => 'invalid token',
                    'description' => 'Permission denied - invalid token'
                ];
                break;

            case 'denied_key':
                $error = [
                    'error' => 'invalid key',
                    'description' => 'Permission denied - invalid key'
                ];
                break;

            case 'expired':
                $error = [
                    'error' => 'expired',
                    'description' => 'Token expired'
                ];
                break;

            case 'not_ready':
                $error = [
                    'error' => 'not ready',
                    'description' => 'The token supplied is not ready to be accessed at the moment.'
                ];
                break;
        }

        return $error;
    }

    /**
     * [getAlgorithm Check if the algorithm in the header is supported by the Responsible API]
     * @param  [staring] $type [Algorithm hash]
     * @return [boolean]
     */
    protected static function getAlgorithm($type)
    {
        return array_search($type, self::$ALGORITHMS);
    }
}
