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

class jwtClaims
{
    /**
     * [$segments Collection segments]
     * @var array
     */
    private $segments = array();

    /**
     * [setSegment]
     * @param string $encodedHeader [Header claims encoded]
     */
    public function setSegment($segment, $encodedHeader)
    {
        $this->segments[$segment] = $encodedHeader;
    }

    /**
     * [getSegment Return a given segment request]
     * @param  string $segment [header, payload, secret]
     * @return string
     */
    public function getSegment($segment)
    {
        return $this->segments[$segment];
    }
}
