<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\configuration
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\configuration;

use josegonzalez;

class config
{
    /**
     * [$DEFAULTS]
     * @var [array]
     */
    private $DEFAULTS;

    /**
     * [responsibleDefault Responsible default options and config]
     * @param  [type] $options
     * @return [void]
     */
    public function responsibleDefault($options = null)
    {
        if (!isset($this->root)) {
            $this->root = dirname(dirname(dirname(__DIR__)));
        }
        
        $ENV_FILE = $this->root . '/config/.config';

        
        if (!file_exists($ENV_FILE)) {
            throw new \Exception(
                "No configuration file seems to exist. Please read the documentation on setting up a configuration file."
            );
        }

        $this->CONFIG = (new josegonzalez\Dotenv\Loader($ENV_FILE))
            ->parse()
            ->toArray();

        if (is_null($this->CONFIG) || empty($this->CONFIG)) {
            throw new \Exception(
                "No config specified in Responsible API class. Please read the documentation on configuration settings."
            );
        }

        $DEFAULTS = [
            'options' => $options,
            'config' => $this->CONFIG,
        ];

        $this->defaults($DEFAULTS);
    }

    /**
     * [defaults Defaults is a merged array of Config and Options]
     * @return [void]
     */
    private function defaults($defaults)
    {
        $this->DEFAULTS = $defaults;
    }

    /**
     * [getDefaults Get default config and options as a single array]
     * @return [array]
     */
    public function getDefaults()
    {
        return $this->DEFAULTS;
    }

    /**
     * [getConfig Get config array]
     * @return [array]
     */
    public function getConfig()
    {
        return $this->DEFAULTS['config'];
    }

    /**
     * [getOptions description Get options array]
     * @return [type]
     */
    public function getOptions()
    {
        return $this->DEFAULTS['options'];
    }

    /**
     * [baseApiRoot Set the responsible API root directory]
     * @return [void]
     */
    public function baseApiRoot($directory)
    {
        $this->root = $directory;
    }
}
