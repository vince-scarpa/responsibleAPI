<?php

/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\user
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */

namespace responsible\core\user;

use responsible\core\auth;
use responsible\core\configuration;
use responsible\core\connect;
use responsible\core\exception;
use responsible\core\headers;

class user
{
    /**
     * [$DB Data base object]
     * @var object
     */
    private $DB;

    /**
     * [$name Account username]
     * @var string
     */
    private $name;

    /**
     * [$name Account email address]
     * @var string
     */
    private $mail;

    /**
     * [$ACCOUNT_ID]
     * @var string
     */
    private $ACCOUNT_ID;

    /**
     * [$timestamp Time now]
     * @var integer
     */
    protected $timestamp;

    /**
     * [$bucketToken]
     * @var string
     */
    private $bucketToken;

    /**
     * [$options Resposible API options]
     * @var array
     */
    protected $options;

    /**
     * [$credentials User credentials]
     * @var array
     */
    protected $credentials;

    /**
     * [create Create a new access account]
     * @return array
     */
    public function create()
    {
        return (new userCreate($this->credentials))
            ->setOptions($this->getOptions())
            ->createAccount()
        ;
    }

    /**
     * [update Update an access account]
     * @return array
     */
    public function update($properties)
    {
        return $this->updateAccount($properties);
    }

    /**
     * [load Load a stored account]
     * @return array
     */
    public function load($property, array $options)
    {
        return (new userLoad($property, $options))
            ->setOptions($this->getOptions())
            ->account()
        ;
    }

    /**
     * [updateAccountAccess Update the requests account access]
     * @return void
     */
    public function updateAccountAccess($ACCOUNT_ID = null)
    {
        if (is_null($ACCOUNT_ID) && empty($this->ACCOUNT_ID)) {
            (new exception\errorException())
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
     * @return boolean
     */
    private function updateAccess()
    {
        return $this->DB()->
            query(
                "
                UPDATE responsible_api_users USR
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
     * [updateAccount Update access for limit requests]
     * @return mixed
     */
    private function updateAccount($properties)
    {
        if (is_array($properties)) {
            $properties = (object) $properties;
        }

        $this->checkUpdateProperties($properties);

        $updateSet = $this->buildUpdateSet($properties);

        return $this->DB()->query(
            "
            UPDATE responsible_api_users USR
                        set {$updateSet['set']}
                        WHERE {$updateSet['where']}
                ;",
            $updateSet['binds']
        );
    }

    /**
     * [checkUpdateProperties Check if we have the correct update properties]
     * @param  object $properties
     * @return void
     */
    private function checkUpdateProperties($properties)
    {
        if (
            !isset($properties->update) ||
            !isset($properties->where) ||
            (isset($properties->update) && !is_array($properties->update)) ||
            (isset($properties->where) && !is_array($properties->where))
        ) {
            (new exception\errorException())
                ->message('No update property was provided. Please read the documentation on updating user accounts.')
                ->error('ACCOUNT_UPDATE');
        }
    }

    /**
     * [buildUpdateSet description]
     * @param  object $properties
     * @return array
     */
    private function buildUpdateSet($properties)
    {
        $allowedFileds = $binds = [];
        $set = '';

        $columns = $this->DB()->query("SHOW COLUMNS FROM responsible_api_users");

        foreach ($columns as $f => $field) {
            $allowedFileds[] = $field['Field'];
        }

        foreach ($properties->update as $u => $update) {
            if (!in_array($u, $allowedFileds)) {
                unset($properties->update[$u]);
            } else {
                $set .= $u . ' = :' . $u . ',';
                $binds[$u] = $update;
            }
        }

        $set = rtrim($set, ',');
        $where = key($properties->where) . ' = ' . $properties->where[key($properties->where)];

        return [
            'set' => $set,
            'where' => $where,
            'binds' => $binds,
        ];
    }

    /**
     * [credentials Set the new account credentials]
     * @param  string $name
     *         username
     * @param  string $mail
     *         email address
     */
    public function credentials($name, $mail)
    {
        if (!$this->validate('name', $name) || !$this->validate('mail', $mail)) {
            (new exception\errorException())
                ->message(
                    'Username or email address validation error! Username must be a string and email must be valid.'
                )
                ->error('APPLICATION_ERROR');
        }

        $this->credentials = [
            'name' => $this->name,
            'mail' => $this->mail,
        ];

        return $this;
    }

    /**
     * [validate - Validate the new account credentials]
     * @return boolean
     */
    private function validate($type, $property)
    {
        $options = $this->getOptions();
        $valid = false;
        $skipValidatation = (isset($options['validate']) && $options['validate'] == false);

        switch ($type) {
            case 'name':
                $valid = $this->validateName($property, $skipValidatation);
                break;

            case 'mail':
                $valid = $this->validateMail($property, $skipValidatation);
                break;
        }

        return $valid;
    }


    private function validateName($property, $skipValidatation)
    {
        if (!is_string($property) && !$skipValidatation) {
            return false;
        }
        $this->name = preg_replace('/\s+/', '-', strtolower($property));

        return true;
    }

    private function validateMail($property, $skipValidatation)
    {
        if (!filter_var($property, FILTER_VALIDATE_EMAIL) && !$skipValidatation) {
            return false;
        }
        $this->mail = $property;

        return true;
    }

    /**
     * [getJWT Get the JWT payload]
     * @return array
     */
    protected function getJWT($key)
    {
        $token = (new headers\header())->authorizationHeaders(true);
        $jwt = new auth\jwt();
        $decoded = $jwt->token($token)
            ->key($key)
            ->decode()
        ;

        return [
            'token' => $token,
            'payload' => $decoded,
        ];
    }

    /**
     * [DB Get DB object]
     */
    protected function DB()
    {
        if (($this->options['db'] ?? null) && $this->options['db'] instanceof \responsible\core\connect\DB) {
            return $this->options['db'];
        }

        if (is_null($this->DB)) {
            $defaults = $this->getDefaults();
            $config = $defaults['config'];

            $this->DB = new connect\DB(
                $config['DB_HOST'],
                $config['DB_NAME'],
                $config['DB_USER'],
                $config['DB_PASSWORD']
            );
        }
        return $this->DB;
    }

    /**
     * [getDefaults Get the Responsible API defaults ]
     * @return array
     */
    protected function getDefaults()
    {
        $config = new configuration\config();
        $config->responsibleDefault();
        return $config->getDefaults();
    }

    /**
     * [setOptions Set the REsponsible API options]
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * [getOptions Get the Responsible API options]
     * @return array
     */
    protected function getOptions()
    {
        return $this->options;
    }

    /**
     * [timeNow Create a timestamp of now]
     * @return integer
     */
    protected function timeNow()
    {
        $this->timestamp = (new \DateTime('now'))->getTimestamp();
        return $this->timestamp;
    }

    /**
     * [setAccountID]
     */
    public function setAccountID($ACCOUNT_ID)
    {
        $this->ACCOUNT_ID = $ACCOUNT_ID;
        return $this;
    }

    /**
     * [getAccountID]
     * @return string
     */
    protected function getAccountID()
    {
        return $this->ACCOUNT_ID;
    }

    /**
     * [setBucket Bucket data token]
     * @param string $packed
     */
    public function setBucketToken($packed)
    {
        $this->bucketToken = $packed;
        return $this;
    }

    /**
     * [getBucketToken Bucket data token]
     * @param string $packed
     */
    public function getBucketToken()
    {
        return $this->bucketToken;
    }

    /**
     * [getClaim Check if a claim is set and not empty]
     * @param  string $claim
     * @return mixed
     */
    public function checkVal($option, $key, $default = false)
    {
        $val = isset($option[$key]) ? $option[$key] : $default;

        if ($val && empty($option[$key])) {
            $val = $default;
        }

        return $val;
    }
}
