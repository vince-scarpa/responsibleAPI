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

use responsible\core\auth;
use responsible\core\configuration;
use responsible\core\connect;
use responsible\core\exception;
use responsible\core\headers;

class user
{
    /**
     * [$name Account username]
     * @var [string]
     */
    private $name;

    /**
     * [$name Account email address]
     * @var [string]
     */
    private $mail;

    /**
     * [$timestamp Time now]
     * @var [integer]
     */
    protected $timestamp;

    /**
     * [$options Resposible API options]
     * @var [array]
     */
    protected $options;

    /**
     * [create Create a new access account]
     * @return [array]
     */
    public function create()
    {
        return (new userCreate($this->credentials))
            ->setOptions($this->getOptions())
            ->createAccount()
        ;
    }

    /**
     * [load Load a stored account]
     * @return [array]
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
     * [credentials Set the new account credentials]
     * @param  [string] $name [username]
     * @param  [string] $mail [email address]
     */
    public function credentials($name, $mail)
    {
        if (!$this->validate('name', $name) || !$this->validate('mail', $mail)) {
            (new exception\errorException)
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
     * @return [boolean]
     */
    private function validate($type, $property)
    {
        $options = $this->getOptions();
        $skipValidatation = false;

        if( isset($options['validate']) && $options['validate'] === false ) {
            $skipValidatation = true;
        }

        switch ($type) {

            case 'name':
                if (!is_string($property) && !$skipValidatation) {
                    return;
                }
                $this->name = preg_replace('/\s+/', '-', strtolower($property));

                return true;
                break;

            case 'mail':
                if( !filter_var($property, FILTER_VALIDATE_EMAIL) && !$skipValidatation) {
                    return;
                }
                $this->mail = $property;

                return true;
                break;
        }
    }

    /**
     * [getJWT Get the JWT payload]
     * @return [array]
     */
    protected function getJWT($key)
    {
        $token = (new headers\header)->authorizationHeaders(true);
        $jwt = new auth\jwt;
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
        if (!isset($this->DB)) {
            $defaults = $this->getDefaults();
            $config = $defaults['config'];

            var_dump( class_exists('responsible\core\connect\DB') );

            $this->DB = new \responsible\core\connect\DB($config['DB_HOST'], $config['DB_NAME'], $config['DB_USER'], $config['DB_PASSWORD']);
        }
        return $this->DB;
    }

    /**
     * [getDefaults Get the Responsible API defaults ]
     * @return [array]
     */
    protected function getDefaults()
    {
        $config = new configuration\config;
        $config->responsibleDefault();
        return $config->getDefaults();
    }

    /**
     * [setOptions Set the REsponsible API options]
     * @param [array] $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * [getOptions Get the Responsible API options]
     * @return [array]
     */
    protected function getOptions()
    {
        return $this->options;
    }

    /**
     * [timeNow Create a timestamp of now]
     * @return [integer]
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
     * @return [integer]
     */
    protected function getAccountID()
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

    /**
     * [getClaim Check if a claim is set and not empty]
     * @param  [string] $claim
     * @return [mixed]
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
