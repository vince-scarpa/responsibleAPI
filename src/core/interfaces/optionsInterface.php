<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\interfaces
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\interfaces;

interface optionsInterface
{
    /**
     * setOptions 
     *     Set the Responsible API options
     *     
     * @param array $options  
     * @return void
     */
    public function setOptions($options):void;

    /**
     * getOptions 
     *     Get the Responsible API options
     *     
     * @return array
     */
    public function getOptions():array;
}