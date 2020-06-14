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

class jwtEncoder extends jwt
{
    /**
     * [$cipher Cipher class]
     * @var object
     */
    private $cipher;

    /**
     * [$claims Claims class]
     * @var object
     */
    private $claims;

    /**
     * [$stringToSign Set the string to sign]
     * @var string
     */
    private $stringToSign = '';

    /**
     * [encode]
     * @return string
     */
    public function encode()
    {
        $this->cipher = new encoder\cipher;
        $this->claims = new auth\jwtClaims;
        
        $header = [
            'typ' => self::CYT,
            'alg' => self::$ALGORITHMS['hash_hmac'],
        ];

        $payload = $this->payload;

        $this->claims->setSegment(
            'header',
            $this->cipher->encode($this->cipher->jsonEncode($header))
        );

        $this->claims->setSegment(
            'payload',
            $this->cipher->encode($this->cipher->jsonEncode($payload))
        );

        $this->stringToSign = $this->claims->getSegment('header') . '.' . $this->claims->getSegment('payload');

        $signature = $this->sign();

        $this->claims->setSegment(
            'signature',
            $this->cipher->encode($signature)
        );

        $signed = $this->claims->getSegment('header') . '.' . $this->claims->getSegment('payload') . '.' . $this->claims->getSegment('signature');

        return $signed;
    }

    /**
     * [sign] Sign the segments
     * @return string the JWT
     */
    private function sign()
    {
        return $this->cipher->encode(
            $this->cipher->hash(
                self::$ALGORITHMS['hash_hmac'],
                $this->stringToSign,
                $this->key
            )
        );
    }

    /**
     * [key Set the clients supplied secret key]
     * @param  string - Hashed string
     * @return self
     */
    public function key($key = null)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * [payload Set the clients payload]
     * @param  array $payload
     * @return self
     */
    public function payload($payload)
    {
        $this->payload = $payload;

        return $this;
    }
}
