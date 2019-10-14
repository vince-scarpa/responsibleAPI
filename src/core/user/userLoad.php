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
use responsible\core\exception;
use responsible\core\headers;
use responsible\core\keys;
use responsible\core\route;

class userLoad extends user
{
    /**
     * [$column Load user by what column]
     * @var [string]
     */
    private $column;

    /**
     * [$requestRefreshToken Request a new refresh JWT]
     * @var boolean
     */
    private $requestRefreshToken = false;

    /**
     * [$requestRefreshToken Get an encoded user token]
     * @var boolean
     */
    private $getToken = false;

    /**
     * [$secret]
     * @var string
     */
    private $secret = '';

    /**
     * [$secret request by system to append the users secret from DB]
     * @var boolean
     */
    private $secretAppend = false;

    /**
     * @param $credentials
     */
    public function __construct($property = null, $options)
    {
        if (is_null($property) || empty($property)) {
            (new exception\errorException)
                ->message('No load property was provided!')
                ->error('ACCOUNT_ID');
        }

        $loadBy = $this->checkVal($options, 'loadBy', 'account_id');
        $this->getToken = $this->checkVal($options, 'getJWT');
        $this->requestRefreshToken = $this->checkVal($options, 'refreshToken');

        $this->keys = new keys\key;
        $this->jwt = new auth\jwt;

        $this->setColumn($loadBy);
        $this->setProperty($property);
        $this->timeNow();

        $this->secret = $this->getDefaults()['config']['MASTER_KEY'];

        if( isset($options['secret']) && $options['secret'] == 'append' ) {
            $this->secretAppend = true;
        }
    }

    /**
     * [account Get the account]
     * @return [object]
     */
    public function account()
    {
        /**
         * [Validate the requested account exists]
         */
        $account = $this->DB()
            ->row(
                "SELECT
                USR.uid, USR.account_id, USR.name, USR.status, USR.access, USR.secret,
                TKN.bucket
                FROM responsible_api_users USR
                INNER JOIN responsible_token_bucket TKN
                    ON USR.account_id = TKN.account_id

                    WHERE {$this->column} = ?
                    AND status = 1
            ;",
                array(
                    $this->property,
                ),
                \PDO::FETCH_OBJ
            );

        if( $this->secretAppend ) {
            $this->secret = $account->secret;
        }

        if (!empty($account)) {
            $this->setAccountID($account->account_id);

            if ($this->requestRefreshToken) {
                $account->refreshToken = $this->futureToken();
                $account->refreshToken['token'] = (new headers\header)->authorizationHeaders(true);
            }

            if ($this->getToken) {
                $account->JWT = $this->getUserJWT();
            }

            return (array) $account;
        }

        // print_r($this);

        (new exception\errorException)->error('UNAUTHORIZED');
    }

    /**
     * [refreshJWT Get a refresh JWT]
     * @return [array]
     */
    public function refreshJWT($userPayload)
    {
        $leeway = ($this->checkVal($this->options['jwt'], 'leeway')) ?: $this->jwt->getLeeway();
        $expires = $userPayload['payload']['exp'] + $leeway;

        $this->options['jwt'] = [
            'leeway' => $leeway,
            'issuedAt' => $expires,
            'expires' => $expires,
            'notBeFor' => $expires - 10,
        ];

        $absSeconds = ($userPayload['payload']['exp'] - ($this->timeNow() - $leeway));

        return [
            'tokenExpire' => [
                'leeway' => $leeway,
                'expiresIn' => $absSeconds,
                'expiresDate' => date(\DateTime::ISO8601, ($userPayload['payload']['exp'] + $leeway)),
                'expiresString' => $this->tokenExpiresIn($absSeconds),
            ],
            'refresh' => $this->getUserJWT(),
        ];
    }

    /**
     * [futureToken Get a future refresh JWT]
     * @return [array]
     */
    public function futureToken()
    {
        if (!isset($this->secret)) {
            (new exception\errorException)
                ->message('There was an error trying to retrieve the server master key. Please read the documentation on setting up a configuration file')
                ->error('NO_CONTENT');
        }

        $key = $this->secret;
        $userPayload = $this->getJWT($key);

        if (empty($userPayload) || !isset($userPayload['payload'])) {
            return;
        }

        /**
         * Check unlimited access set
         */
        $skipExpiry = $this->checkVal($this->options, 'unlimited', true);

        /**
         * Check token expiry
         */
        if ($exp = $this->checkVal($userPayload['payload'], 'exp') && !$skipExpiry) {
            return $this->refreshJWT($userPayload);
        }

        return;
    }

    /**
     * [tokenExpiresIn Get the token expiry as a string]
     * @param  [integer] $seconds
     * @return [string]
     */
    private function tokenExpiresIn($seconds)
    {
        if ($seconds <= 0) {
            return 0;
        }

        $minutes = (float) $seconds / 60;
        $zero = new \DateTime('@0');
        $offset = new \DateTime('@' . $minutes * 60);
        $diff = $zero->diff($offset);

        return $diff->format('%a Days, %h Hours, %i Minutes, %s Seconds');
    }

    /**
     * [getUserJWT Get an ecoded user token]
     * @return [string]
     */
    public function getUserJWT()
    {
        if (!isset($this->secret)) {
            (new exception\errorException)
                ->message('There was an error trying to retrieve the server master key. Please read the documentation on setting up a configuration file')
                ->error('NO_CONTENT');
        }

        $key = $this->secret;

        /**
         * [$payload Set the default payload]
         * @var array
         */
        $payload = array(
            "iss" => (new route\router)->getIssuer(),
            "sub" => $this->getAccountID(),
            "iat" => $this->timeNow(),
            "nbf" => $this->timeNow() + 10,
        );

        /**
         * [$jwtOptions JWT options may be set as Responsible option overrides]
         * @var [array]
         */
        if (false !== ($jwtOptions = $this->checkVal($this->getOptions(), 'jwt'))) {
            if (false !== ($exp = $this->checkVal($jwtOptions, 'expires'))) {
                $payload['exp'] = $exp;
            }
            if (false !== ($iat = $this->checkVal($jwtOptions, 'issuedAt'))) {
                $payload['iat'] = $iat;
            }
            if (false !== ($nbf = $this->checkVal($jwtOptions, 'notBeFor'))) {
                if( strtolower($nbf) == 'issuedat' && isset($payload['iat']) ) {
                    $nbf = $payload['iat'] + 10;
                }
                $payload['nbf'] = $nbf;
            }
        }

        /**
         * Return the encoded JWT
         */
        return $this->jwt
            ->key($key)
            ->setPayload($payload)
            ->encode($payload)
        ;
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
     * [setProperty Set the property we want the Responsible API load an account by]
     * @param [type] $property
     */
    private function setProperty($property)
    {
        $this->property = $property;
    }

    /**
     * [setColumn Set the column type we want the Responsible API load an account by]
     * @param [string] $column
     */
    private function setColumn($column)
    {
        if ($column == 'account_id' || strtolower($column == 'accountid')) {
            $this->column = 'USR.account_id';
        }

        if ($column == 'username' || $column == 'name') {
            $this->column = 'USR.name';
        }

        if ($column == 'email' || $column == 'mail') {
            $this->column = 'USR.mail';
        }
    }

    /**
     * [getColumn Get the set column]
     * @return [string]
     */
    private function getColumn()
    {
        return $this->column;
    }
}
