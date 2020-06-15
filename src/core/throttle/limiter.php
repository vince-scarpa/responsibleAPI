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

use responsible\core\exception;
use responsible\core\throttle;
use responsible\core\user;

class limiter
{
    /**
     * [$capacity Bucket volume]
     * @var integer
     */
    private $capacity = 100;

    /**
     * [$leakRate Constant rate at which the bucket will leak]
     * @var string|integer
     */
    private $leakRate = 1;

    /**
     * [$unpacked]
     */
    private $unpacked;

    /**
     * [$packed]
     * @var string
     */
    private $packed;

    /**
     * [$bucket]
     */
    private $bucket;

    /**
     * [$account User account object]
     */
    private $account;

    /**
     * [$options Responisble options]
     */
    private $options;

    /**
     * [$timeframe Durations are in seconds]
     * @var array
     */
    private static $timeframe = [
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
    private $window = 'MINUTE';

    /**
     * [$unlimited Rate limiter bypass if true]
     * @var boolean
     */
    private $unlimited = false;

    /**
     * [$scope Set the default scope]
     * @var string
     */
    private $scope = 'private';

    /**
     * [setupOptions Set any Responsible API options]
     * @return self
     */
    public function setupOptions()
    {
        $options = $this->getOptions();

        $this->setCapacity($options);

        $this->setTimeframe($options);

        $this->setLeakRate($options);

        $this->setUnlimited($options);

        if( isset($this->account->scope) &&
            ($this->account->scope == 'anonymous' || $this->account->scope == 'public')
        ) {
           $this->scope = $this->account->scope;
        }

        return $this;
    }

    /**
     * [throttleRequest Build the Responsible API throttle]
     * @return boolean|void
     */
    public function throttleRequest()
    {
        if ($this->isUnlimited() || $this->scope !== 'private') {
            return true;
        }

        $account = $this->getAccount();

        if (empty($account)) {
            return false;
        }

        /**
         * [$unpack Unpack the account bucket data]
         */
        $this->unpacked = (new throttle\tokenPack)->unpack(
            $account->bucket
        );
        if (empty($this->unpacked)) {
            $this->unpacked = array(
                'drops' => 1,
                'time' => $account->access,
            );
        }

        $this->bucket = (new throttle\tokenBucket())
            ->setTimeframe($this->getTimeframe())
            ->setCapacity($this->getCapacity())
            ->setLeakRate($this->getLeakRate())
            ->pour($this->unpacked['drops'], $this->unpacked['time'])
        ;

        /**
         * Check if the bucket still has capacity to fill
         */
        if ($this->bucket->capacity()) {
            $this->bucket->pause(false);
            $this->bucket->fill();
        } else {
            if ($this->getLeakRate() <= 0) {
                if ($this->unpacked['pauseAccess'] == false) {
                    $this->bucket->pause(true);
                    $this->save();
                }

                if ($this->bucket->refill($account->access)) {
                    $this->save();
                }
            }

            (new exception\errorException)->error('TOO_MANY_REQUESTS');
        }

        $this->save();
    }

    /**
     * [updateBucket Store the buckets token data and user access time]
     * @return void
     */
    private function save()
    {
        $this->packed = (new throttle\tokenPack)->pack(
            $this->bucket->getTokenData()
        );

        /**
         * [Update account access]
         */
        (new user\user)
            ->setAccountID($this->getAccount()->account_id)
            ->setBucketToken($this->packed)
            ->updateAccountAccess()
        ;
    }

    /**
     * [getThrottle Return a list of the throttled results]
     * @return array
     */
    public function getThrottle()
    {
        if ($this->isUnlimited() || $this->scope !== 'private') {
            return array(
                'unlimited' => true,
            );
        }

        $windowFrame = (is_string($this->getTimeframe()))
        ? $this->getTimeframe()
        : $this->getTimeframe() . 'secs'
        ;

        if (is_null($this->bucket)) {
            return;
        }

        return array(
            'limit' => $this->getCapacity(),
            'leakRate' => $this->getLeakRate(),
            'leak' => $this->bucket->getLeakage(),
            'lastAccess' => $this->getLastAccessDate(),
            'description' => $this->getCapacity() . ' requests per ' . $windowFrame,
            'bucket' => $this->bucket->getTokenData(),
        );
    }

    /**
     * [getLastAccessDate Get the last recorded access in date format]
     * @return string
     */
    private function getLastAccessDate()
    {
        if (isset($this->bucket->getTokenData()['time'])) {
            return date('m/d/y h:i:sa', $this->bucket->getTokenData()['time']);
        }

        return 'Can\'t be converted';
    }

    /**
     * [setAccount Set the requests account]
     * @return self
     */
    public function setAccount($account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * [getAccount Get the requests account]
     */
    public function getAccount()
    {
        if(is_null($this->account)) {
            (new exception\errorException)
                ->setOptions($this->getOptions())
                ->error('UNAUTHORIZED');
            return;
        }

        return $this->account;
    }

    /**
     * [options Responsible API options]
     * @param array $options
     */
    public function options($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * [getOptions Get the stored Responsible API options]
     * @return array
     */
    private function getOptions()
    {
        return $this->options;
    }

    /**
     * [hasOptionProperty Check if an option property is set]
     * @param  array  $options
     * @param  string  $property
     * @return boolean
     */
    private function hasOptionProperty(array $options, $property, $default = false)
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
    public function setCapacity($options)
    {
        $hasCapacityOption = $this->hasOptionProperty($options, 'rateLimit');

        if ($hasCapacityOption) {
            if (!is_integer($hasCapacityOption)||empty($hasCapacityOption)) {
                $hasCapacityOption = false;
            }
        }

        $this->capacity = ($hasCapacityOption) ? $hasCapacityOption : $this->capacity;
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
    public function setTimeframe($options)
    {
        $timeframe = $this->hasOptionProperty($options, 'rateWindow');

        if (!is_string($timeframe) && !is_numeric($timeframe)) {
            $timeframe = $this->window;
        }

        if (!$timeframe) {
            $timeframe = $this->window;
        }

        if (is_numeric($timeframe)) {
            self::$timeframe['CUSTOM'] = $timeframe;
            $this->window = self::$timeframe['CUSTOM'];
            return;
        }

        if (isset(self::$timeframe[$timeframe])) {
            $this->window = self::$timeframe[$timeframe];
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
    private function setLeakRate($options)
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
    private function getLeakRate()
    {
        return $this->leakRate;
    }

    /**
     * [setUnlimited Rate limiter bypass]
     * @param array $options
     */
    private function setUnlimited($options)
    {
        $unlimited = false;

        if(isset($options['unlimited']) && ($options['unlimited'] == 1 || $options['unlimited'] == true)) {
            $unlimited = true;
        }

        if (isset($options['requestType']) && $options['requestType'] === 'debug') {
            $unlimited = true;
        }

        $this->unlimited = $unlimited;
    }

    /**
     * [isUnlimited Check if the Responsible API is set to unlimited]
     * @return boolean
     */
    private function isUnlimited()
    {
        return $this->unlimited;
    }
}
