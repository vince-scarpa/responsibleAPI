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

class tokenBucket
{
    /**
     * [$capacity The Buckets limit]
     * @var integer
     */
    private $capacity;

    /**
     * [$drops How many drops in a bucket]
     * @var integer
     */
    private $drops = 0;

    /**
     * [$leakage Leak rate]
     * @var float
     */
    private $leakage;

    /**
     * [$lastAccess Timestamp of lat access]
     * @var integer
     */
    private $lastAccess;

    /**
     * [$timeframe The window timeframe]
     * @var integer
     */
    private $timeframe;

    /**
     * [$pauseAccess Pause access for a given period]
     * @var boolean
     */
    private $pauseAccess = false;

    /**
     * [__construct Set the last access this overridden if already accessed]
     */
    public function __construct()
    {
        $this->lastAccess = $this->timeNow();
    }

    /**
     * [capacity Check if the bucket can still be filled]
     * @return boolean
     */
    public function capacity()
    {
        if ($this->full()) {
            $this->leak();
        }

        return (floor($this->drops) < $this->capacity);
    }

    /**
     * [leak Create a leak in the bucket]
     * @return void
     */
    public function leak()
    {
        $now = $this->timeNow();
        $difference = $now - $this->lastAccess;

        if ($difference > 0 && $this->getLeakage() > 0) {
            $this->drops -= ($difference * $this->leakage);
            $this->lastAccess = $now;

            if ($this->drops < 0) {
                $this->drops = 0;
            }
        }
    }

    /**
     * [pour Pour drops in the bucket, essentially filling the bucket with drops]
     * @param  integer $drops
     * @param  integer $lastAccess
     * @return self
     */
    public function pour($drops, $lastAccess)
    {
        $this->fill($drops);
        $this->lastAccess = $lastAccess;
        return $this;
    }

    /**
     * [fill fill the bucket with drops]
     * @param integer $drops
     */
    public function fill($drops = 1)
    {
        $this->drops += $drops;
    }

    /**
     * [full Check if the bucket is full]
     * @return boolean
     */
    public function full()
    {
        return ceil($this->drops >= $this->capacity);
    }

    /**
     * [refill Refill the bucket tokens]
     * @return array|boolean
     */
    public function refill($accessed)
    {
        $now = $this->timeNow();
        $difference = $now - $accessed;

        if (($difference + 1) > $this->getTimeframe()) {
            $this->drops = 0;
            $this->lastAccess = $now;
            return [
                'drops' => $this->drops,
                'time' => $this->lastAccess,
                'pauseAccess' => $this->pauseAccess,
            ];
        }
        return;
    }

    /**
     * [pause Pause access to the bucket]
     * @param  boolean $state
     */
    public function pause($state = false)
    {
        $this->pauseAccess = $state;
    }

    /**
     * [timeNow Create a timestamp of now]
     * @return integer
     */
    public function timeNow()
    {
        return (new \DateTime('now'))->getTimestamp();
    }

    /**
     * [setCapacity Set the buckets overall capacity]
     * @param integer $capacity
     * @return self
     */
    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
        return $this;
    }

    /**
     * [setLeakRate Set the buckets leak rate]
     * @param string|integer $leakRate
     * @return self
     */
    public function setLeakRate($leakRate = null)
    {
        $capacity = $this->getCapacity();
        $timeframe = $this->getTimeframe();

        if (!is_null($leakRate)) {
            if (is_string($leakRate)) {
                switch ($leakRate) {
                    case 'slow':
                        $this->leakage = ($capacity / $timeframe) / 4;
                        break;

                    case 'medium':
                        $this->leakage = (($capacity / $timeframe) / 2);
                        break;

                    case 'normal':
                    case 'default':
                        $this->leakage = ($capacity / $timeframe);
                        break;

                    case 'fast':
                        $this->leakage = 1;
                        break;

                    default:
                        $this->leakage = ($capacity / $timeframe);
                        break;
                }
            }

            if (is_numeric($leakRate)) {
                if ($leakRate < 0) {
                    $leakRate = 0;
                }
                $this->leakage = $leakRate;
            }
        }

        return $this;
    }

    /**
     * [setTimeframe Set the buckets window access]
     * @param string|integer $timeframe
     */
    public function setTimeframe($timeframe)
    {
        $this->timeframe = $timeframe;
        return $this;
    }

    /**
     * [getCapacity Get capacity]
     * @return integer
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * [getLeakage Get the buckets leak rate]
     * @return integer|float
     */
    public function getLeakage()
    {
        return $this->leakage;
    }

    /**
     * [getDrops Get the buckets current drops]
     * @param  boolean $leak
     * @return integer
     */
    public function getDrops($leak = true)
    {
        if ($leak) {
            $this->leak();
        }
        return $this->drops;
    }

    /**
     * [getTokenData Get the token data]
     * @return array
     */
    public function getTokenData()
    {
        return [
            'drops' => floor($this->getDrops()),
            'time' => $this->lastAccess,
            'pauseAccess' => $this->pauseAccess,
        ];
    }

    /**
     * [getTimeframe Get the buckets window timeframe]
     * @return string|integer
     */
    public function getTimeframe()
    {
        return $this->timeframe;
    }
}
