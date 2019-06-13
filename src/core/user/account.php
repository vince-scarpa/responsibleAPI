<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsible.git
 *
 * @api Responible API
 * @package responsible\core\user
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\user;

use responsible\core\configuration;
use responsible\core\connect;
use responsible\core\exception;
use responsible\core\keys;
use responsible\core\auth;

class account
{
    /**
     * [$DB]
     * @var [object]
     */
    protected $DB;

    /**
     * [$ACCOUNT_ID Users account id]
     * @var [integer]
     */
    private $ACCOUNT_ID;

    /**
     * [__construct description]
     * @param [type] $ACCOUNT_ID [description]
     */
    public function __construct($ACCOUNT_ID = null)
    {
        if (!is_null($ACCOUNT_ID)) {
            $this->setAccountID($ACCOUNT_ID);
        }
    }

    /**
     * [load User account details]
     * @return [object]
     */
    public function load($ACCOUNT_ID = null)
    {
        if (is_null($ACCOUNT_ID) && empty($this->ACCOUNT_ID)) {
            (new exception\errorException)
                ->message('No ACCOUNT_ID provided!')
                ->error('ACCOUNT_ID');
        }

        if (!is_null($ACCOUNT_ID)) {
            $this->setAccountID($ACCOUNT_ID);
        }

        /**
         * [Validate the requested account exists]
         */
        $account = $this->DB()
            ->row(
                "SELECT
                USR.uid, USR.account_id, USR.status, USR.access,
                TKN.bucket
                FROM responsible_api_users USR
                INNER JOIN responsible_token_bucket TKN
                    ON USR.account_id = TKN.account_id
                    WHERE USR.account_id = ?
                    AND status = 1
            ;",
                array(
                    $this->getAccountID(),
                ),
                \PDO::FETCH_OBJ
            );

        if (!empty($account)) {
            return $account;
        }

        return;
    }

    /**
     * [updateAccountAccess Update the requests account access]
     * @return [boolean]
     */
    public function updateAccountAccess($ACCOUNT_ID = null)
    {
        if (is_null($ACCOUNT_ID) && empty($this->ACCOUNT_ID)) {
            (new exception\errorException)
                ->message('No ACCOUNT_ID provided!')
                ->error('ACCOUNT_ID');
        }

        if (!is_null($ACCOUNT_ID)) {
            $this->setAccountID($ACCOUNT_ID);
        }
        /**
         * Upate the users access
         */
        $this->updateAccess();
    }

    /**
     * [updateAccess Update access for limit requests]
     * @return [boolean]
     */
    private function updateAccess()
    {
        return $this->DB()->
            query(
                "UPDATE responsible_api_users USR
                        JOIN responsible_token_bucket TKN
                            ON (USR.account_id = TKN.account_id)
                        set
                            USR.access = :unix,
                            TKN.bucket = :bkt
                        WHERE USR.account_id = :aid;",
                array(
                'unix' => (new \DateTime('now'))->getTimestamp(),
                'aid' => $this->getAccountID(),
                'bkt' => $this->getBucketToken(),
            )
        );
    }

    /**
     * [setDB]
     */
    public function setDB($DB)
    {
        $this->DB = $DB;
        return $this;
    }

    /**
     * [DB Get DB object]
     */
    public function DB()
    {
        if (!isset($this->DB)) {
            $config = new configuration\config;
            $config->responsibleDefault();
            $config = $config->getConfig();
            $this->DB = new connect\DB($config['DB_HOST'], $config['DB_NAME'], $config['DB_USER'], $config['DB_PASSWORD']);
        }
        return $this->DB;
    }

    /**
     * [setAccountID]
     */
    private function setAccountID($ACCOUNT_ID)
    {
        $this->ACCOUNT_ID = $ACCOUNT_ID;
    }

    /**
     * [getAccountID]
     * @return [integer]
     */
    private function getAccountID()
    {
        return $this->ACCOUNT_ID;
    }

    /**
     * [setBucket Bucket data token]
     * @param [string] $packed
     */
    public function setBucketToken($packed)
    {
        $this->bucketToken = $packed;
        return $this;
    }

    /**
     * [getBucketToken Bucket data token]
     * @param [string] $packed
     */
    public function getBucketToken()
    {
        return $this->bucketToken;
    }
}
