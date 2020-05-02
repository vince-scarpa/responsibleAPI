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

use responsible\core\auth;
use responsible\core\encoder;
use responsible\core\exception;

class jwtDecoder extends jwt
{
    /**
     * [decode]
     * @return array
     */
    public function decode()
    {
        $cipher = new encoder\cipher;
        $validate = new auth\jwtValidate;

        $validate::leeway(self::$LEEWAY);
        $validate::timestamp(self::$TIMESTAMP);
        $validate::algorithm();

        /**
         * Segment the JWT
         */
        list($jwtHead, $jwtPayload, $sig) = $this->segment();

        /**
         * [$headObject JWT head object]
         * @var object
         */
        $headObject = $cipher->jsonDecode($cipher->decode($jwtHead));

        /**
         * [$payloadObject JWT payload object]
         * @var object
         */
        $payloadObject = $cipher->jsonDecode($cipher->decode($jwtPayload));

        if( $this->key == 'payloadOnly' ) {
            return $payloadObject;
        }

        /**
         * [$jwtKey Key signature]
         * @var string
         */
        $signature = $cipher->decode($sig);

        /**
         * Validate the JWT
         *
         * Validation process has been seperated for ease of code readability
         * Exceptions are set in the respective validation methods
         */
        $validate::header($headObject);
        $validate::payload($payloadObject);

        $validate::signature($jwtHead, $jwtPayload, $signature, $this->key);

        return $payloadObject;
    }

    /**
     * [split Decouple the token into segments]
     * @return array
     */
    private function segment()
    {
        $segment = explode('.', $this->getToken());

        if (sizeof($segment) != 3) {
            (new exception\errorException)
                ->setOptions(parent::$options)
                ->message(self::messages('denied_token'))
                ->error('UNAUTHORIZED');
        }

        return $segment;
    }

    /**
     * [token Set the token]
     * @param  string
     * @return string
     */
    public function token($token = null)
    {
        if (is_null($token) || empty($token)) {
            (new exception\errorException)
                ->setOptions(parent::$options)
                ->message(self::messages('denied_token'))
                ->error('UNAUTHORIZED');
        }

        $this->token = $token;

        return $this;
    }

    /**
     * [key Set the clients supplied secret key]
     * @param  string
     * @return string
     */
    public function key($key = null)
    {
        $this->key = $key;

        return $this;
    }
}
