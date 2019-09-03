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
     * [jsonEncode description]
     * @param  [type] $stdArray [description]
     * @return [type]           [description]
     */
    public function jsonEncode(array $stdArray)
    {
        return json_encode($stdArray, true);
    }

    /**
     * [jsonDecode description]
     * @param  [type] $jsonString [description]
     * @return [type]             [description]
     */
    public function jsonDecode($jsonString)
    {
        return json_decode($jsonString, true, 512, JSON_BIGINT_AS_STRING);
    }

    /**
     * [encode description]
     * @param  [type] $stdString [description]
     * @return [type]            [description]
     */
    public function encode($stdString)
    {
        return $this->toBase64(base64_encode($stdString));
    }

    /**
     * [decode description]
     * @param  [type] $toDecode [description]
     * @return [type]           [description]
     */
    public function decode($toDecode)
    {
        return (string) base64_decode(
            $this->padDecode($toDecode),
            true
        );
    }

    /**
     * [toBase64Url description]
     * @param  [type] $base64 [description]
     * @return [type]         [description]
     */
    private function toBase64($base64)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], $base64);
    }

    /**
     * [hash]
     * @return [string]
     */
    public function hash($algo = 'sha256', $stdString, $secret)
    {
        return hash_hmac($algo, $stdString, $secret, true);
    }

    /**
     * [hash_compare Compare two hashed strings]
     * @param  [type] $a [know]
     * @param  [type] $b [unknown user hash]
     * @return [boolean]
     */
    public function hashCompare($a, $b)
    {
        if (!is_string($a) || !is_string($b)) {
            return false;
        }

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
     * [padDecode description]
     * @param [type] $base64String [description]
     */
    private function padDecode($base64String)
    {
        if (strlen($base64String) % 4 !== 0) {
            return $this->padDecode($base64String . '=');
        }
        return $base64String;
    }
}
