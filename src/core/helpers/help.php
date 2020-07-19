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
     * [checkVal Check if an array value isset and not empty]
     *
     * This is not a multidimensional search, it's intent is to 
     * replace the use of isset and not empty cases in if statements
     * 
     * @param  array $array
     *         array to check
     * @param  string $key
     *         key to find
     * @param  mixed $default
     *         Value to return if nothing found
     * 
     * @return mixed
     */
    public function checkVal(array $array, $key, $default = false)
    {
        $val = isset($array[$key]) ? $array[$key] : $default;

        if ($val && empty($array[$key])) {
            $val = $default;
        }

        return $val;
    }
}
