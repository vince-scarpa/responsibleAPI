<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\exception
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\helpers;

class help
{
    /**
     * INJECTION SANITIZER
     * @return: SANITIZED STRING
     */
    public function Sanitize($str, $remove_nl = true)
    {
        if (($str == '')) {
            return '';
        }

        $str = stripslashes($str);

        if ($remove_nl) {
            $injections = array(
                '/(\n+)/i',
                '/(\r+)/i',
                '/(\t+)/i',
                '/(%0A+)/i',
                '/(%0D+)/i',
                '/(%08+)/i',
                '/(%09+)/i',
            );

            $str = preg_replace($injections, '', $str);
        }

        return $str;
    }


    /**
     * [getClaim Check if a claim is set and not empty]
     * @param  string $claim
     * @return mixed
     */
    public function checkVal($option, $key, $default = false)
    {
        $val = isset($option[$key]) ? $option[$key] : $default;

        if ($val && empty($option[$key])) {
            $val = $default;
        }

        return $val;
    }
}
