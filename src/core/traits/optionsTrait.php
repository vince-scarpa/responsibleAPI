<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\traits
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\traits;

// use responsible\core\options;

trait optionsTrait
{
    /**
     * $options 
     *     Options property
     * @var array
     */
    private $options = [];

    /**
     * setOptions 
     *     Set the Responsible API options
     * @param array $options
     */
    public function setOptions($options):void
    {
        $this->options = $options;
    }

    /**
     * getOptions 
     *     Get the Responsible API options if set
     * @return array
     */
    public function getOptions():array
    {
        if (!empty($this->options)) {
            return $this->options;
        }
        return [];
    }
}