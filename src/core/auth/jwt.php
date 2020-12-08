<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
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
use responsible\core\headers\header;

class jwt extends \responsible\core\auth\authorise
{
    /**
     * [CTY - Json Web Token content type]
     * @link https://tools.ietf.org/html/rfc7519#section-5.2
     */
    const CYT = 'JWT';

    /**
     * [$TIMESTAMP Set the current timestamp]
     * @var integer
     */
    protected static $TIMESTAMP;

    /**
     * [$LEEWAY Cater for time skew in sever time differences]
     * @var integer
     */
    protected static $LEEWAY = 10;

    /**
     * [$EXPIRES Default token expiry]
     * 300 = 5 minutes
     * @var integer
     */
    protected $EXPIRES = 300;

    /**
     * [$algorithyms Supported algorithms]
     * @var array
     */
    protected static $ALGORITHMS = [
        'HS256','sha256',
        'HS384','sha384',
        'HS512','sha512',
    ];

    /**
     * [$ALGORITHMS_ACRONYM Get the JWT acronym support]
     * @var array
     */
    protected static $ALGORITHMS_ACRONYM = [
        'sha256' => ['hash' => 'sha256'],
        'sha384' => ['hash' => 'sha384'],
        'sha512' => ['hash' => 'sha512'],
        'HS256' => ['hash' => 'sha256'],
        'HS384' => ['hash' => 'sha384'],
        'HS512' => ['hash' => 'sha512'],
    ];

    /**
     * [$token]
     * @var string
     */
    protected $token;

    /**
     * [$key Client secret key]
     * @var string
     */
    protected $key;

    /**
     * [$payload Clients payload]
     * @var array
     */
    protected $payload;

    /**
     * Responsible API options
     */
    protected static $options;

    /**
     * [__construct]
     */
    public function __construct()
    {
        self::$TIMESTAMP = (new \DateTime('now'))->getTimestamp();
    }

    /**
     * [encode]
     * @return string
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
     * @return array
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
     * @param  string
     * @return self
     */
    public function token($token = null)
    {
        if (is_null($token) || empty($token) || !is_string($token)) {
            $this->setUnauthorised();
        }

        $this->token = $token;

        return $this;
    }

    /**
     * [key - Set the secret key]
     *
     * @param  string
     * @return self
     */
    public function key($key = null)
    {
        if (is_null($key) || empty($key) || !is_string($key)) {
            $this->setUnauthorised();
        }

        $this->key = $key;

        return $this;
    }

    /**
     * [setUnauthorised Render unauthorised response]
     */
    protected function setUnauthorised()
    {
        $header = new header;
        $header->setOptions($this->getOptions());
        $header->unauthorised();
        // @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd

    /**
     * [payload Set the clients payload]
     * @param  array $payload
     * @return self
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * [getToken Get the Json Web Token]
     * @return string
     */
    protected function getToken()
    {
        return $this->token;
    }

    /**
     * [getKey Get the client secret key]
     * @return string
     */
    protected function getKey()
    {
        return $this->key;
    }

    /**
     * [getPayload Get the clients payload]
     * @return array
     */
    protected function getPayload()
    {
        return $this->payload;
    }

    /**
     * [getLeeway Get the default leeway]
     * @return integer
     */
    public static function getLeeway()
    {
        return self::$LEEWAY;
    }

    /**
     * [getLeeway Get the default expiry]
     * @return integer
     */
    public function getExpires()
    {
        return $this->EXPIRES;
    }

    /**
     * [getTimestamp Get the current timestamp]
     * @return integer
     */
    public static function getCurrentTimestamp()
    {
        return self::$TIMESTAMP;
    }

    /**
     * [setOptions Inherit Responsible API options]
     * @param array $options
     */
    public function setOptions($options)
    {
        parent::setOptions($options);
        self::$options = $options;
        return $this;
    }

    /**
     * [getOptions]
     * @return array
     */
    public function getOptions():?array
    {
        return self::$options;
    }

    /**
     * [messages Common error messages]
     * @param  string $type [message type]
     * @return array
     */
    protected static function messages($type)
    {
        $error = [];

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
     * @param  string $type [Algorithm hash]
     * @return mixed
     */
    public static function getAlgorithm($type = '')
    {
        return self::resolveAlgorithm();
    }

    /**
     * [resolveAlgorithm Resolve the algorythm to use in the JWT header]
     * @return array
     */
    protected static function resolveAlgorithm()
    {
        $algoKey = (self::$options['jwt']['algo']) ?? 'HS256';
        $algoKey = (isset(self::$ALGORITHMS_ACRONYM[$algoKey])) ? $algoKey : 'HS256';

        $ALGO = [
            'header' => $algoKey,
            'hash' => self::$ALGORITHMS_ACRONYM[$algoKey]['hash'],
        ];

        if (array_search($algoKey, self::$ALGORITHMS) !== FALSE) {
            $ALGO = [
                'header' => $algoKey,
                'hash' => self::$ALGORITHMS_ACRONYM[$algoKey]['hash'],
            ];
        }

        return $ALGO;
    }
}
