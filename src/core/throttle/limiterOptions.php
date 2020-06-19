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

use responsible\core\server;
use responsible\core\exception;

class limiterOptions
{
    use \responsible\core\traits\optionsTrait;

    /**
     * [$capacity Bucket volume]
     * @var integer
     */
    protected $capacity = 100;

    /**
     * [$leakRate Constant rate at which the bucket will leak]
     * @var string|integer
     */
    protected $leakRate = 1;

    /**
     * [$timeframe Durations are in seconds]
     * @var array
     */
    protected static $timeframe = [
        'SECOND' => 1,
        'MINUTE' => 60,
        'HOUR' => 3600,
        'DAY' => 86400,
        'CUSTOM' => 0,
    ];

    /**
     * [$window Timeframe window]
     * @var integer|string
     */
    protected $window = 'MINUTE';

    /**
     * [$unlimited Rate limiter bypass if true]
     * @var boolean
     */
    protected $unlimited = false;

    /**
     * [$account User account object]
     */
    protected $account;

    /**
     * [$isMockTest Set the mock test]
     * @var boolean
     */
    protected $isMockTest = false;

    /**
     * [$isMockTest Set the mock account]
     * @var array
     */
    protected $mockAccount = [];

    /**
     * [$scope Set the default scope]
     * @var string
     */
    protected $scope = 'private';

    /**
     * [hasOptionProperty Check if an option property is set]
     * @param  array  $options
     * @param  string  $property
     * @return string|integer|boolean
     */
    protected function hasOptionProperty(array $options, $property, $default = false)
    {
        $val = isset($options[$property]) ? $options[$property] : $default;

        if ($val && empty($options[$property])) {
            $val = $default;
        }

        return $val;
    }

    /**
     * [setCapacity Set the buckets capacity]
     * @param array $options
     */
    protected function setCapacity($options)
    {
        $hasCapacityOption = $this->hasOptionProperty($options, 'rateLimit');

        if (!is_numeric($hasCapacityOption) || empty($hasCapacityOption)) {
            $hasCapacityOption = false;
        }

        $this->capacity = ($hasCapacityOption) ? intval($hasCapacityOption) : intval($this->capacity);
    }

    /**
     * [getCapacity Get the buckets capacity]
     * @return integer
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * [setTimeframe Set the window timeframe]
     * @param array $options
     */
    protected function setTimeframe($options)
    {
        $timeframe = $this->hasOptionProperty($options, 'rateWindow');

        if (is_string($timeframe)) {
            if (isset(self::$timeframe[$timeframe])) {
                $this->window = intval(self::$timeframe[$timeframe]);
                return;
            }
        }

        if (is_numeric($timeframe)) {
            if ($timeframe < 0) {
                $timeframe = ($timeframe*-1);
            }
            self::$timeframe['CUSTOM'] = $timeframe;
            $this->window = intval(self::$timeframe['CUSTOM']);
            return;
        }

        $this->window = self::$timeframe['MINUTE'];
    }

    /**
     * [getTimeframe Get the timeframe window]
     * @return integer|string
     */
    public function getTimeframe()
    {
        return $this->window;
    }

    /**
     * [setLeakRate Set the buckets leak rate]
     * Options: slow, medium, normal, default, fast or custom positive integer
     * @param array $options
     */
    protected function setLeakRate($options)
    {
        if (isset($options['leak']) && !$options['leak']) {
            $options['leakRate'] = 'default';
        }

        $leakRate = $this->hasOptionProperty($options, 'leakRate');

        if (empty($leakRate) || !is_string($leakRate)) {
            $leakRate = 'default';
        }

        $this->leakRate = $leakRate;
    }

    /**
     * [getLeakRate Get the buckets leak rate]
     * @return string|integer
     */
    public function getLeakRate()
    {
        return $this->leakRate;
    }

    /**
     * [setUnlimited Rate limiter bypass]
     * @param array $options
     */
    protected function setUnlimited($options)
    {
        $unlimited = false;

        if (isset($options['unlimited']) && ($options['unlimited'] == 1 || $options['unlimited'] == true)) {
            $unlimited = true;
        }

        $this->unlimited = $unlimited;
    }

    /**
     * [setUnlimited Rate limiter bypass in debug mode]
     * @param array $options
     */
    protected function setDebugMode($options)
    {
        $unlimited = false;

        if (isset($options['requestType']) && $options['requestType'] === 'debug') {
            $unlimited = true;
        }

        $this->unlimited = $unlimited;
    }

    /**
     * [isUnlimited Check if the Responsible API is set to unlimited]
     * @return boolean
     */
    protected function isUnlimited()
    {
        return $this->unlimited;
    }
}
