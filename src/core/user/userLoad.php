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
use responsible\core\helpers\help as helper;
use responsible\core\headers;
use responsible\core\interfaces\UserStateAdaptorInterface;
use responsible\core\keys;
use responsible\core\route;

class userLoad extends user
{
    /**
     * [$column Load user by what column]
     * @var string
     */
    private $column;

    /**
     * [$requestRefreshToken Request a new refresh JWT]
     * @var boolean
     */
    private $requestRefreshToken = false;

    /**
     * [$requestRefreshToken Request a new refresh JWT, request from authorization headers]
     * @var boolean
     */
    private $authorizationRefresh = false;

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
     * [$keys]
     */
    private $keys;

    /**
     * [$jwt]
     * @var object
     */
    private $jwt;

    /**
     * [$property]
     * @var string
     */
    protected $property;

    /**
     * [$secret request by system to append the users secret from DB]
     * @var boolean
     */
    private $secretAppend = false;

    public function __construct($property, $options)
    {
        if (is_null($property) || empty($property)) {
            (new exception\errorException())
                ->setOptions($this->options)
                ->message('No load property was provided!')
                ->error('ACCOUNT_ID');
        }

        $loadBy = $this->checkVal($options, 'loadBy', 'account_id');

        $this->getToken = $this->checkVal($options, 'getJWT');
        $this->requestRefreshToken = $this->checkVal($options, 'refreshToken');
        $this->authorizationRefresh = $this->checkVal($options, 'authorizationRefresh');

        $this->keys = new keys\key();
        $this->jwt = new auth\jwt();

        $this->setColumn($loadBy);
        $this->setProperty($property);
        $this->timeNow();

        $this->secret = $this->getDefaults()['config']['MASTER_KEY'];

        if (isset($options['secret']) && $options['secret'] == 'append') {
            $this->secretAppend = true;
        }
    }

    /**
     * [account Get the account]
     * @return array
     */
    public function account()
    {
        $adaptor = $this->getOptions()['userStateAdaptor'] ?? null;
        $fromCache = false;

        $cached = ($adaptor instanceof UserStateAdaptorInterface)
            ? $adaptor->getUser($this->property)
            : null;

        if ($cached !== null) {
            $account = $cached;
            $fromCache = true;
        } else {
            /**
             * [Validate the requested account exists]
             */
            $account = $this->DB()
                ->row(
                    "SELECT
                    USR.uid,
                    USR.account_id,
                    USR.name,
                    USR.mail,
                    USR.status,
                    USR.access,
                    USR.secret,
                    USR.refresh_token,
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

            if ($this->secretAppend && !empty($account)) {
                $this->secret = $account->secret;
            }
        }

        if (!empty($account)) {
            $this->setAccountID($account->account_id);

            if (!$fromCache && $adaptor instanceof UserStateAdaptorInterface) {
                $ttl = $this->resolveCacheTtl();
                $adaptor->setUser($this->property, $account, $ttl);
            }

            if (
                isset($this->getOptions()['jwt']['signWith']) &&
                !empty($this->getOptions()['jwt']['signWith']) &&
                !$this->secretAppend
            ) {
                $this->secret = $this->getOptions()['jwt']['signWith'];
            }

            if ($this->requestRefreshToken) {
                $account->refresh_token = $this->refreshTokenGenerate($account);
                $headers = new headers\header();
                $headers->setOptions($this->getOptions());
                $sentToken = $headers->hasBearerToken();

                if ($sentToken) {
                    /**
                     * [$jwt Decode the JWT]
                     * @var auth\jwt
                     */
                    $jwt = new auth\jwt();
                    $decoded = $jwt
                        ->setOptions($this->getOptions())
                        ->token($sentToken)
                        ->key('payloadOnly')
                        ->decode()
                    ;

                    $leeway = ($this->checkVal($this->options['jwt'], 'leeway'))
                        ?: $this->jwt->getLeeway()
                    ;
                    $absSeconds = ($decoded['exp'] - ($this->timeNow() - $leeway));

                    if ($absSeconds > 0) {
                        $account->JWT = $sentToken;
                    }

                    $account->tokenExpire = [
                        'tokenExpire' => [
                            'leeway' => $leeway,
                            'expiresIn' => $absSeconds,
                            'expiresString' => $this->tokenExpiresIn($absSeconds),
                        ]
                    ];
                }

                $account->refreshToken = [
                    'token' => $sentToken,
                    'refresh' => $account->refresh_token
                ];
            }

            if (isset($account->tokenExpire['tokenExpire']['expiresIn'])) {
                $account->refreshToken['expiresIn'] = $account->tokenExpire['tokenExpire']['expiresIn'];
            }

            if ($this->getToken) {
                $account->JWT = $this->getUserJWT();
                $account->refresh_token = $this->refreshTokenGenerate($account);
                $account->refreshToken = ['token' => $account->refresh_token];
            }

            return (array) $account;
        }

        (new exception\errorException())
            ->setOptions($this->options)
            ->error('UNAUTHORIZED');

        return [];
    }

    /**
     * [refreshToken New way to request refresh token]
     * @return string
     */
    public function refreshTokenGenerate($account)
    {
        $expiresAt = $this->timeNow() + $this->resolveRefreshDurationSeconds();

        $payload = [
            'sub' => \strval($account->account_id),
            'exp' => $expiresAt,
            'iss' => (new route\router())->getIssuer(),
            'iat' => $this->timeNow(),
            'nbf' => $this->timeNow(),
            'token_type' => 'refresh',
        ];

        $refreshKey = $this->getRefreshSigningKey();
        $refreshToken = $this->jwt
            ->setOptions($this->getOptions())
            ->key($refreshKey)
            ->setPayload($payload)
            ->encode($payload)
        ;

        $helper = new helper();
        $refreshHash = $helper->hashRefreshToken($refreshToken, $refreshKey);
        $account->refreshToken = $refreshToken;

        $updateProp = [
            'where' => [
                'account_id' => $account->account_id
            ],
            'update' => [
                'refresh_token' => $refreshHash,
            ]
        ];
        parent::update($updateProp);

        return $refreshToken;
    }

    /**
     * [resolveRefreshDurationSeconds Get refresh token TTL in seconds]
     * @return int
     */
    private function resolveRefreshDurationSeconds(): int
    {
        $options = $this->getOptions();
        $duration = $options['refreshToken']['duration'] ?? "30d"; // Default to 30 days
        $helper = new helper();
        return $helper->parseDurationSeconds($duration, 86400);
    }

    /**
     * [getRefreshSigningKey Resolve the refresh token signing key]
     * @return string
     */
    private function getRefreshSigningKey(): string
    {
        $jwtOptions = $this->getOptions()['jwt'] ?? [];

        if (isset($jwtOptions['refreshSignWith']) && !empty($jwtOptions['refreshSignWith'])) {
            return $jwtOptions['refreshSignWith'];
        }

        if (isset($jwtOptions['signWith']) && !empty($jwtOptions['signWith'])) {
            return $jwtOptions['signWith'];
        }

        return $this->secret;
    }


    /**
     * [refreshJWT Get a refresh JWT]
     * @return array
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
     * @return array|null
     */
    public function futureToken()
    {
        if (!isset($this->secret)) {
            (new exception\errorException())
                ->setOptions($this->options)
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
        if ($this->checkVal($userPayload['payload'], 'exp') && !$skipExpiry) {
            return $this->refreshJWT($userPayload);
        }

        return;
    }

    /**
     * [tokenExpiresIn Get the token expiry as a string]
     * @param  integer $seconds
     * @return string
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
     * @return string
     */
    public function getUserJWT($refresh = false)
    {
        if (!isset($this->secret)) {
            (new exception\errorException())
                ->setOptions($this->options)
                ->message('There was an error trying to retrieve the server master key. Please read the documentation on setting up a configuration file')
                ->error('NO_CONTENT');
        }

        $key = $this->secret;

        /**
         * [$payload Set the default payload]
         * @var array
         */
        $payload = array(
            "iss" => (new route\router())->getIssuer(),
            "sub" => $this->getAccountID(),
            "iat" => $this->timeNow(),
            "nbf" => $this->timeNow() + 10,
        );

        /**
         * [$jwtOptions JWT options may be set as Responsible option overrides]
         * @var array
         */
        $exp = false;
        if (false !== ($jwtOptions = $this->checkVal($this->getOptions(), 'jwt'))) {
            if (false !== ($exp = $this->checkVal($jwtOptions, 'expires'))) {
                $payload['exp'] = $exp;
            }
            if (false !== ($iat = $this->checkVal($jwtOptions, 'issuedAt'))) {
                $payload['iat'] = $iat;
            }
            if (false !== ($nbf = $this->checkVal($jwtOptions, 'notBeFor'))) {
                if (strtolower($nbf) == 'issuedat' && isset($payload['iat'])) {
                    $nbf = $payload['iat'] + 10;
                }
                $payload['nbf'] = $nbf;
            }
        }

        if ($refresh && $exp) {
            $refreshPayload = $payload;

            $offset = $exp - $this->timeNow();
            $leeway = ($this->checkVal($this->options['jwt'], 'leeway')) ?: $this->jwt->getLeeway();

            $refreshPayload['exp'] = $exp + $offset + $leeway;

            $refreshJWT = $this->refreshJWT([
                'payload' => $refreshPayload
            ]);

            if (isset($refreshJWT['refresh'])) {
                return $refreshJWT['refresh'];
            }
        }

        /**
         * Return the encoded JWT
         */
        return $this->jwt
            ->setOptions($this->getOptions())
            ->key($key)
            ->setPayload($payload)
            ->encode($payload)
        ;
    }

    /**
     * Derive a cache TTL from the JWT exp claim, falling back to null (adaptor default).
     */
    private function resolveCacheTtl(): ?int
    {
        $exp = $this->getOptions()['jwt']['expires'] ?? null;

        if (is_int($exp) && $exp > time()) {
            return $exp - time();
        }

        return null;
    }

    /**
     * [setOptions Set the Responsible API options]
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * [setProperty Set the property we want the Responsible API load an account by]
     * @param string $property
     */
    private function setProperty($property)
    {
        $this->property = $property;
    }

    /**
     * [setColumn Set the column type we want the Responsible API load an account by]
     * @param string $column
     */
    private function setColumn($column)
    {
        switch ($column) {
            case ($column == 'account_id' || strtolower($column == 'accountid')):
                $this->column = 'BINARY USR.account_id';
                break;

            case ($column == 'username' || $column == 'name'):
                $this->column = 'USR.name';
                break;

            case ($column == 'email' || $column == 'mail'):
                $this->column = 'USR.mail';
                break;

            case ($column == 'refresh_token'):
                $this->column = 'USR.refresh_token';
                break;

            default:
                $this->column = '';
                break;
        }
    }
}
