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

    /**
     * [parseDurationSeconds Parse a duration string like "720h"]
     * This is used to parse duration strings in the config and convert them to seconds
     *
     * Duration as an integer is treated as seconds,
     * but a string can be used to specify a duration in a more human readable format
     *
     * The format is a number followed by a unit, where the unit can be:
     * s = seconds
     * m = minutes
     * h = hours
     * d = days
     * w = weeks
     *
     *
     * @param  string $value
     * @param  int $defaultSeconds
     * @return int
     */
    public function parseDurationSeconds($value, $defaultSeconds): int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (!is_string($value) || $value === '') {
            return $defaultSeconds;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        if (!preg_match('/^\s*(\d+)\s*([smhdw])\s*$/i', $value, $matches)) {
            return $defaultSeconds;
        }

        $amount = (int) $matches[1];
        $unit = strtolower($matches[2]);

        switch ($unit) {
            case 's':
                return $amount;
            case 'm':
                return $amount * 60;
            case 'h':
                return $amount * 3600;
            case 'd':
                return $amount * 86400;
            case 'w':
                return $amount * 604800;
            default:
                return $defaultSeconds;
        }
    }

    /**
     * [hashRefreshToken Hash a refresh JWT for storage]
     * @param  string $token
     * @param  string $key
     * @return string
     */
    public function hashRefreshToken($token, $key)
    {
        return hash_hmac('sha256', $token, $key);
    }
}
