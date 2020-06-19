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
use responsible\core\server;

class limiter extends limiterOptions
{
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
     * @var object
     */
    private $bucket;

    /**
     * [$tokenPacker Token packer class object]
     * @var object
     */
    private $tokenPacker;

    public function __construct($limit = null, $rate = null)
    {
        if (!is_null($limit)) {
            $this->setCapacity(['rateLimit' => $limit]);
        }

        if (!is_null($rate)) {
            $this->setTimeframe(['rateWindow' => $rate]);
        }

        $this->bucket = new throttle\tokenBucket;
        $this->tokenPacker = new throttle\tokenPack;
    }

    /**
     * [setupOptions Set any Responsible API options]
     * @return self
     */
    public function setupOptions()
    {
        $options = $this->getOptions();

        $server = new server([], $options);
        $this->isMockTest = $server->isMockTest();

        $this->setCapacity($options);

        $this->setTimeframe($options);

        $this->setLeakRate($options);

        $this->setUnlimited($options);

        $this->setDebugMode($options);

        if (isset($this->account->scope) &&
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

        $bucket = $this->bucketObj();

        $this->unpackBucket();
        
        if ($bucket->capacity()) {
            $bucket->pause(false);
            $bucket->fill();

        } else {
            $this->throttlePause();
        }

        $this->save();
    }

    /**
     * [throttlePause Throttle the limiter when there are too many requests]
     * @return void
     */
    private function throttlePause()
    {
        $account = $this->getAccount();
        $bucket = $this->bucketObj();

        if ($this->getLeakRate() <= 0) {
            if ($this->unpacked['pauseAccess'] == false) {
                $bucket->pause(true);
                $this->save();
            }

            if ($bucket->refill($account->access)) {
                $this->save();
            }
        }

        (new exception\errorException)
                ->setOptions($this->getOptions())
                ->error('TOO_MANY_REQUESTS');
    }

    /**
     * Unpack the account bucket data
     */
    private function unpackBucket()
    {
        $account = $this->getAccount();
        $bucket = $this->bucketObj();
        $packer = $this->packerObj();

        $this->unpacked = $packer->unpack(
            $account->bucket
        );
        if (empty($this->unpacked)) {
            $this->unpacked = array(
                'drops' => 1,
                'time' => $account->access,
            );
        }

        $bucket->setTimeframe($this->getTimeframe())
            ->setCapacity($this->getCapacity())
            ->setLeakRate($this->getLeakRate())
            ->pour($this->unpacked['drops'], $this->unpacked['time'])
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

        $bucket = $this->bucketObj();

        $windowFrame = (is_string($this->getTimeframe()))
        ? $this->getTimeframe()
        : $this->getTimeframe() . 'secs'
        ;

        if (is_null($bucket)) {
            return;
        }

        return array(
            'limit' => $this->getCapacity(),
            'leakRate' => $this->getLeakRate(),
            'leak' => $bucket->getLeakage(),
            'lastAccess' => $this->getLastAccessDate(),
            'description' => $this->getCapacity() . ' requests per ' . $windowFrame,
            'bucket' => $bucket->getTokenData(),
        );
    }

    /**
     * [updateBucket Store the buckets token data and user access time]
     * @return void
     */
    private function save()
    {
        $bucket = $this->bucketObj();
        $packer = $this->packerObj();

        $this->packed = $packer->pack(
            $bucket->getTokenData()
        );

        if($this->isMockTest) {
            return;
        }

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
     * [getLastAccessDate Get the last recorded access in date format]
     * @return string
     */
    private function getLastAccessDate()
    {
        $bucket = $this->bucketObj();

        if (isset($bucket->getTokenData()['time'])) {
            return date('m/d/y h:i:sa', $bucket->getTokenData()['time']);
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
        if($this->isMockTest) {
            $this->getMockAccount();
            return $this->mockAccount;
        }

        if (is_null($this->account)||empty($this->account)) {
            (new exception\errorException)
                ->setOptions($this->getOptions())
                ->error('UNAUTHORIZED');
            return;
        }

        return $this->account;
    }

    /**
     * Build a mock account for testing
     * @return void
     */
    private function getMockAccount()
    {
        $bucket = $this->bucketObj();
        $packer = $this->packerObj();

        $mockAccount = [];

        if(!isset($mockAccount['bucket'])) {
            $mockAccount['bucket'] = $packer->pack(
                $bucket->getTokenData()
            );
        }

        $mockAccount['access'] = time();

        $this->mockAccount = (object)$mockAccount;
    }

    /**
     * [bucketObj Get the bucket class object]
     * @return object
     */
    private function bucketObj()
    {
        return $this->bucket;
    }

    /**
     * [packerObj Get the token packer class object]
     * @return object
     */
    private function packerObj()
    {
        return $this->tokenPacker;
    }
}
