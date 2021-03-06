<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\throttle
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\throttle;

use responsible\core\throttle;
use responsible\core\encoder;

class tokenPack
{
    /**
     * [$cipher]
     * @var object
     */
    private $cipher;

    /**
     * [__construct Call cipher class]
     */
    public function __construct()
    {
        $this->cipher = new encoder\cipher;
    }

    /**
     * [pack Tokenize the data array]
     * @return string
     */
    public function pack(array $data = array())
    {
        if (empty($data)) {
            return '';
        }
        return $this->cipher->encode(
            $this->cipher->jsonEncode(
                $data
            )
        );
    }

    /**
     * [pack Tokenize the data array]
     * @return array
     */
    public function unpack($data)
    {
        if (empty($data)) {
            return [];
        }
        return $this->cipher->jsonDecode(
            $this->cipher->decode(
                $data
            )
        );
    }
}
