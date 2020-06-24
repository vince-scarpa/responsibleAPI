<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\oauth
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\auth;

use responsible\core\server;
use responsible\core\encoder;
use responsible\core\exception;
use responsible\core\route;
use responsible\core\user;
use responsible\core\headers\header;

class jwtValidate extends jwt
{
    /**
     * [$TIMESTAMP]
     * @var int
     */
    protected static $TIMESTAMP;

    /**
     * [$LEEWAY]
     * @var int
     */
    protected static $LEEWAY = 0;

    /**
     * [$ALGORITHM]
     * @var string
     */
    private static $ALGORITHM;

    /**
     * [$isPayloadValid Validation placeholders]
     * @var array
     */
    protected static $isPayloadValid = [
        "iss" => false,
        "sub" => false,
        "iat" => false,
        "nbf" => false,
        "exp" => false,
    ];

    /**
     * [header Validate the header object]
     * First segment of the token
     * @return bool
     */
    public static function header(array $headObject = [])
    {
        if (
            empty($headObject) ||
            !self::typ($headObject) ||
            !self::alg($headObject)
        ) {
            (new header)->unauthorised();
        }
    }

    /**
     * [payload Validate the payload object]
     * Second segment of the token
     * @return bool
     */
    public static function payload(array $payloadObject = [])
    {
        if( self::isAnonymousScope($payloadObject) ) {
            return true;
        }

        self::iss($payloadObject);
        self::sub($payloadObject);
        self::iat($payloadObject);
        self::nbf($payloadObject);
        self::exp($payloadObject);

        if ((true === in_array(false, self::$isPayloadValid))) {
            (new header)->unauthorised();
        }
    }

    /**
     * Check if the scope is anonymous
     * @return boolean
     */
    private static function isAnonymousScope($payloadObject)
    {
        return (isset($payloadObject['scope']) && $payloadObject['scope'] == 'anonymous');
    }

    /**
     * [signature - Validate the signature object]
     * Third segment of the token
     * @return bool
     */
    public static function signature($jwtHead, $jwtPayload, $signature, $key)
    {
        if (empty($jwtHead) ||
            empty($jwtPayload) ||
            empty($signature) ||
            empty($key)
        ) {
            (new header)->unauthorised();
        }

        $cipher = new encoder\cipher;

        $hashed = $cipher->encode(
            $cipher->hash(
                self::$ALGORITHM,
                $jwtHead . '.' . $jwtPayload,
                $key
            )
        );

        if (!$cipher->hashCompare($signature, $hashed)) {
            (new header)->unauthorised();
        }
    }

    /**
     * [typ Issuer claim]
     *
     * @return bool
     */
    public static function typ($headObject)
    {
        if (!isset($headObject['typ']) ||
            (isset($headObject['typ']) && empty($headObject))
        ) {
            return;
        }
        return true;
    }

    /**
     * [alg Issuer claim]
     *
     * @return bool
     */
    public static function alg($headObject)
    {
        if (!isset($headObject['alg']) ||
            (isset($headObject['alg']) && empty($headObject)) &&
            self::getAlgorithm($headObject['alg'])
        ) {
            return;
        }

        self::algorithm($headObject['alg']);

        return true;
    }

    /**
     * [iss Issuer claim]
     *
     * @return bool
     */
    public static function iss($payloadObject)
    {
        if (!isset($payloadObject['iss']) ||
            (isset($payloadObject['iss']) && empty($payloadObject))
        ) {
            return;
        }

        $router = new route\router;
        $router->route();

        if ($payloadObject['iss'] !== $router->getIssuer(true)) {
            return;
        }

        self::$isPayloadValid['iss'] = true;
        return true;
    }

    /**
     * [sub Subject claim]
     * The Responsible API uses the "Subject" claim as a placeholder for account Ids
     *
     * @return bool
     */
    public static function sub($payloadObject)
    {
        $server = new server([], parent::$options);
        if ($server->isMockTest()) {
            self::$isPayloadValid['sub'] = true;
            return true;
        }

        if (!isset($payloadObject['sub']) ||
            (isset($payloadObject['sub']) && empty($payloadObject))
        ) {
            return;
        }

        $account = (object) (new user\user)
            ->setOptions(parent::$options)
            ->load($payloadObject['sub'], ['refreshToken' => false])
        ;

        if (empty($account)) {
            return;
        } else {
            if (!isset($account->account_id)) {
                return;
            }

            if ((int) $account->account_id !== (int) $payloadObject['sub']) {
                return;
            }
        }

        self::$isPayloadValid['sub'] = true;
        return true;
    }

    /**
     * [iat Issued at claim]
     *
     * @return bool
     */
    public static function iat($payloadObject)
    {
        if (!isset($payloadObject['iat']) ||
            (isset($payloadObject['iat']) && empty($payloadObject))
        ) {
            return;
        }

        if ($payloadObject['iat'] > self::getTimestamp()) {
            (new exception\errorException)
                ->setOptions(parent::$options)
                ->message(self::messages('not_ready'))
                ->error('NO_CONTENT');
        }

        self::$isPayloadValid['iat'] = true;
        return true;
    }

    /**
     * [nbf Not before claim]
     *
     * @return bool
     */
    public static function nbf($payloadObject)
    {
        if (!isset($payloadObject['nbf']) ||
            (isset($payloadObject['nbf']) && empty($payloadObject))
        ) {
            return;
        }

        if ($payloadObject['nbf'] > self::getTimestamp()) {
            (new exception\errorException)
                ->setOptions(parent::$options)
                ->message(self::messages('not_ready'))
                ->error('NO_CONTENT');
        }

        self::$isPayloadValid['nbf'] = true;
        return true;
    }

    /**
     * [exp Expiration claim this optional so if its not set return true]
     *
     * @return bool
     */
    public static function exp($payloadObject)
    {
        if (!isset($payloadObject['exp']) ||
            (isset($payloadObject['exp']) && empty($payloadObject))
        ) {
            return;
        }

        if ($payloadObject['exp'] <= (int) (self::$TIMESTAMP - self::$LEEWAY)) {
            (new header)->unauthorised();
        }

        self::$isPayloadValid['exp'] = true;
        return true;
    }

    /**
     * [leeway Inherit the leeway offset]
     * @param  int $leeway [integer in seconds]
     * @return void
     */
    public static function leeway($leeway)
    {
        self::$LEEWAY = $leeway;
    }

    /**
     * [timestamp Inherit the current timestamp (now)]
     * @param  int $timestamp [integer in seconds]
     * @return void
     */
    public static function timestamp($timestamp)
    {
        self::$TIMESTAMP = $timestamp;
    }

    /**
     * [getTimestamp Add both the timestamp (now) and the leeway]
     * @return int
     */
    private static function getTimestamp()
    {
        return (int) (self::$TIMESTAMP + self::$LEEWAY);
    }

    /**
     * [algorithm Set the requested hash algorithm]
     * @return string
     */
    public static function algorithm($algo = 'SHA256')
    {
        if ($algo == 'HS256') {
            $algo = 'sha256';
        }
        self::$ALGORITHM = $algo;
    }
}
