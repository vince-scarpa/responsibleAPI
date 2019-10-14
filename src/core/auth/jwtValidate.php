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

use responsible\core\encoder;
use responsible\core\exception;
use responsible\core\route;
use responsible\core\user;

class jwtValidate extends jwt
{
    /**
     * [$TIMESTAMP]
     * @var [integer]
     */
    protected static $TIMESTAMP;

    /**
     * [$LEEWAY]
     * @var [integer]
     */
    protected static $LEEWAY = 0;

    /**
     * [$ALGORITHM]
     * @var [string]
     */
    private static $ALGORITHM;

    /**
     * [header Validate the header object]
     * First segment of the token
     * @return [boolean]
     */
    public static function header(array $headObject = [])
    {
        if (
            !is_array($headObject) ||
            !self::typ($headObject) ||
            !self::alg($headObject)
        ) {
            (new exception\errorException)
                ->setOptions(parent::$options)
                ->message(self::messages('denied_token'))
                ->error('UNAUTHORIZED');
        }
    }

    /**
     * [payload Validate the payload object]
     * Second segment of the token
     * @return [boolean]
     */
    public static function payload(array $payloadObject = [])
    {
        if (
            !is_array($payloadObject) ||
            !self::iss($payloadObject) ||
            !self::sub($payloadObject) ||
            !self::iat($payloadObject) ||
            !self::nbf($payloadObject) ||
            !self::exp($payloadObject)
        ) {
            (new exception\errorException)
                ->setOptions(parent::$options)
                ->message(self::messages('denied_token'))
                ->error('UNAUTHORIZED');
        }
    }

    /**
     * [signature - Validate the signature object]
     * Third segment of the token
     * @return [boolean]
     */
    public static function signature($jwtHead, $jwtPayload, $signature, $key)
    {
        if (empty($jwtHead) ||
            empty($jwtPayload) ||
            empty($signature) ||
            empty($key)
        ) {
            (new exception\errorException)
                ->setOptions(parent::$options)
                ->message(self::messages('denied_token'))
                ->error('UNAUTHORIZED');
        }

        $cipher = new encoder\cipher;

        $algorithm = self::$ALGORITHM;

        $hashed = $cipher->encode(
            $cipher->hash(
                self::$ALGORITHM,
                $jwtHead . '.' . $jwtPayload,
                $key
            )
        );

        if (!$cipher->hashCompare($signature, $hashed)) {
            (new exception\errorException)
                ->setOptions(parent::$options)
                ->message(self::messages('denied_token'))
                ->error('UNAUTHORIZED');
        }
    }

    /**
     * [typ Issuer claim]
     *
     * @return [boolean]
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
     * @return [boolean]
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
     * @return [boolean]
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

        return true;
    }

    /**
     * [sub Subject claim]
     * The Responsible API uses the "Subject" claim as a placeholder for account Ids
     *
     * @return [boolean]
     */
    public static function sub($payloadObject)
    {
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

        return true;
    }

    /**
     * [iat Issued at claim]
     *
     * @return [boolean]
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

        return true;
    }

    /**
     * [nbf Not before claim]
     *
     * @return [boolean]
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

        return true;
    }

    /**
     * [exp Expiration claim this optional so if its not set return true]
     *
     * @return [boolean]
     */
    public static function exp($payloadObject)
    {
        if (!isset($payloadObject['exp']) ||
            (isset($payloadObject['exp']) && empty($payloadObject))
        ) {
            return true;
        }

        if ($payloadObject['exp'] <= (int) (self::$TIMESTAMP - self::$LEEWAY)) {
            (new exception\errorException)
                ->setOptions(parent::$options)
                ->message(self::messages('expired'))
                ->error('UNAUTHORIZED');
        }

        return true;
    }

    /**
     * [leeway Inherit the leeway offset]
     * @param  [type] $leeway [integer in seconds]
     * @return [void]
     */
    public static function leeway($leeway)
    {
        self::$LEEWAY = $leeway;
    }

    /**
     * [timestamp Inherit the current timestamp (now)]
     * @param  [type] $timestamp [integer in seconds]
     * @return [void]
     */
    public static function timestamp($timestamp)
    {
        self::$TIMESTAMP = $timestamp;
    }

    /**
     * [getTimestamp Add both the timestamp (now) and the leeway]
     * @return [integer]
     */
    private static function getTimestamp()
    {
        return (int) (self::$TIMESTAMP + self::$LEEWAY);
    }

    /**
     * [algorithm Set the requested hash algorithm]
     * @return [string]
     */
    public static function algorithm($algo = 'SHA256')
    {
        if ($algo == 'HS256') {
            $algo = 'sha256';
        }
        self::$ALGORITHM = $algo;
    }
}
