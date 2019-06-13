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
use responsible\core\exception;
use responsible\core\keys;
use responsible\core\route;

class userCreate extends user
{
    /**
     * [$credentials]
     * @var array
     */
    private $credentials = array();

    /**
     * [$KEY Sectret key]
     * @var string
     */
    private $KEY = '';

    /**
     * @param $credentials
     */
    public function __construct($credentials)
    {
        $this->credentials = $credentials;
        $this->timeNow();

        $this->keys = new keys\key;
        $this->jwt = new auth\jwt;
    }

    /**
     * [create - Create a user account]
     * @return [array]
     */
    public function createAccount()
    {
        if (!isset($this->getDefaults()['config']['MASTER_KEY'])) {
            (new exception\errorException)
                ->message('There was an error trying to retrieve the server master key. Please read the documentation on setting up a configuration file')
                ->error('NO_CONTENT');
        }

        $this->KEY = $this->keys->secretGenerate();

        $this->accountExists();
        $this->setAccountID($this->keys->accountIdGenerate());

        $payload = $this->createPayload();

        $encoded = $this->jwt
            ->key($this->getDefaults()['config']['MASTER_KEY'])
            ->setPayload($payload)
            ->encode($payload)
        ;

        if ($encoded) {
            return [
                'ACCOUNT' => $this->accountInsert(),
                'JWT' => $encoded,
                'SECRET' => $this->KEY,
            ];
        }
    }

    /**
     * [createPayload Create a new payload for the new account]
     * @return [array]
     */
    private function createPayload()
    {
        /**
         * [$payload Set the default payload]
         * @var array
         */
        $payload = [
            'iss' => (new route\router)->getIssuer(),
            'sub' => $this->getAccountID(),
            'iat' => $this->timeNow(),
            'nbf' => $this->timeNow(),
            'exp' => $this->timeNow() + $this->jwt->getExpires(),
        ];

        /**
         * [$jwtOptions JWT options may be set as Responsible option overrides]
         * @var [array]
         */
        if (false !== ($jwtOptions = $this->checkVal($this->options, 'jwt'))) {
            if (false !== ($exp = $this->checkVal($jwtOptions, 'expires'))) {
                $payload['exp'] = $exp;
            }
            if (false !== ($iat = $this->checkVal($jwtOptions, 'issuedAt'))) {
                $payload['iat'] = $iat;
            }
            if (false !== ($nbf = $this->checkVal($jwtOptions, 'notBeFor'))) {
                $payload['nbf'] = $nbf;
            }
        }

        return $payload;
    }

    /**
     * [accountExists description]
     * @return [type] [description]
     */
    private function accountExists()
    {
        $account = $this->DB()
            ->row(
                "SELECT uid
                FROM responsible_api_users
                WHERE
                    name = :name
                OR
                    mail = :mail
            ;",
                array(
                    'name' => $this->credentials['name'],
                    'mail' => $this->credentials['mail'],
                ),
                \PDO::FETCH_OBJ
            );

        if ($account) {
            (new exception\errorException)
                ->message('The email or username supplied already exists!')
                ->error('NO_CONTENT');
        }

        return $account;
    }

    /**
     * [accountInsert description]
     * @return [type] [description]
     */
    private function accountInsert()
    {
        $newAccount = $this->DB()
            ->query(
                "INSERT INTO responsible_api_users
                    (`uid`, `account_id`, `name`, `mail`, `created`, `access`, `status`, `secret`)
                VALUES
                    (NULL, :accntid, :name, :mail, :tmestmp, :access, '1', :secret)
            ;",
                array(
                    'accntid' => $this->getAccountID(),
                    'name' => $this->credentials['name'],
                    'mail' => $this->credentials['mail'],
                    'tmestmp' => $this->timeNow(),
                    'access' => $this->timeNow(),
                    'secret' => $this->KEY,
                )
            );

        $newTokenBucket = $this->DB()
            ->query(
                "INSERT INTO responsible_token_bucket
                    (`id`, `bucket`, `account_id`)
                VALUES
                    (NULL, '', :accntid)
                ;",
                array(
                    'accntid' => $this->getAccountID(),
                )
            );

        if ($newAccount) {
            return $this->load($this->credentials['mail'], array('loadBy' => 'mail'));
        }

        (new exception\errorException)
            ->message('There was an error trying to create a new account! "accountInsert()" failed')
            ->error('ACCOUNT_ID');
    }

    /**
     * [setOptions Set the Responsible API options]
     * @param [array] $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * [setToken description]
     * @param [type] $token [description]
     */
    private function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * [getToken description]
     * @return [type] [description]
     */
    private function getToken()
    {
        return $this->token;
    }
}
