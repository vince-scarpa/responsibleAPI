<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\keys
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\keys;

class key
{
    /**
     * [accountIdGenerate Generate a user account id]
     * @return integer
     */
    public function accountIdGenerate($length = 8)
    {
        $uq = uniqid(mt_rand(), true);
        $sb = substr($uq, 0, $length);
        $hd = hexdec($sb);
        return substr($hd, 0, $length);
    }

    /**
     * [apiKeyGenerate Generate an API key for the new user account]
     * @param  integer $length
     * @return string
     */
    public function apiKeyGenerate($length = 32)
    {
        $randInt = microtime().rand(1000, 9999);
        $messageDigest = md5($randInt);
        return implode(
            '-',
            str_split(substr(strtolower($messageDigest), 0, $length), 6)
        );
    }

    /**
     * [secretGenerate Generate a strong secret key]
     * @return string
     */
    public function secretGenerate($length = 32)
    {
        $chars =
            'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' .
            '0123456789`-=!@#$%^&*()_+,./?;[]{}\|'
        ;

        $str = '';
        $max = strlen($chars) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, $max)];
        }

        return $str;
    }

    /**
     * [tokenGenerate This is a JWT this is a pseudo token used for the leaky bucket]
     * @return string
     */
    public function tokenGenerate($ACCOUNT_ID, $key = '')
    {
        return hash_hmac('sha256', $ACCOUNT_ID, $key);
    }
}
