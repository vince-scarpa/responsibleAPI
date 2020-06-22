<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\encoder
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\encoder;

class cipher
{
    /**
     * [jsonEncode]
     * @param  array $stdArray
     *         Array to encode
     * @return string
     */
    public function jsonEncode(array $stdArray)
    {
        return json_encode($stdArray);
    }

    /**
     * [jsonDecode]
     * @param  string $jsonString
     *         String to decode
     * @return array
     */
    public function jsonDecode($jsonString)
    {
        return json_decode($jsonString, true, 512, JSON_BIGINT_AS_STRING);
    }

    /**
     * [encode]
     * @param  string $stdString
     * @return string
     */
    public function encode($stdString)
    {
        return $this->toBase64(base64_encode($stdString));
    }

    /**
     * [decode]
     * @param  string $toDecode
     * @return string
     */
    public function decode($toDecode)
    {
        return (string) base64_decode(
            $this->padDecode($toDecode),
            true
        );
    }

    /**
     * [toBase64Url]
     * @param  string $base64
     * @return string
     */
    private function toBase64($base64)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], $base64);
    }

    /**
     * [hash]
     * @return string
     */
    public function hash($algo = 'sha256', $stdString, $secret)
    {
        return hash_hmac($algo, $stdString, $secret, true);
    }

    /**
     * [hash_compare Compare two hashed strings]
     * @param  string $a
     *         Known user hash
     * @param  string $b 
     *         Unknown user hash
     * @return boolean
     */
    public function hashCompare($a, $b)
    {
        $len = strlen($a);
        if ($len !== strlen($b)) {
            return false;
        }

        $status = 0;
        for ($i = 0; $i < $len; $i++) {
            $status |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $status === 0;
    }

    /**
     * [padDecode]
     * @param string $base64String
     */
    private function padDecode($base64String)
    {
        if (strlen($base64String) % 4 !== 0) {
            return $this->padDecode($base64String . '=');
        }
        return $base64String;
    }
}
