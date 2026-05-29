<?php

/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\headers
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */

namespace responsible\core\headers;

use responsible\core\server;
use responsible\core\auth;
use responsible\core\configuration;
use responsible\core\encoder;
use responsible\core\exception;
use responsible\core\helpers\help as helper;
use responsible\core\user;

class headerAuth extends header
{
    use \responsible\core\traits\optionsTrait;

    /**
     * [__construct]
     */
    public function __construct()
    {
    }

    /**
     * [authorizationHeaders Scan for "Authorization" header]
     * @return string|array [mixed: string / error]
     */
    public function authorizationHeaders($skipError = false)
    {
        if ($grant = $this->isGrantRequest()) {
            return $grant;
        }

        if ($clientToken = $this->hasBearerToken()) {
            return $clientToken;
        }

        if (!$skipError) {
            $this->setUnauthorised();
        }
    // @codeCoverageIgnoreStart
    }
    // @codeCoverageIgnoreEnd

    /**
     * [hasBearerValue Check if Authorization headers has Bearer value]
     * @throws Exception
     *         Unauthorised
     * @return boolean
     */
    private function hasBearerValue()
    {
        $auth_headers = $this->getHeaders();

        if (isset($auth_headers["Authorization"]) && !empty($auth_headers["Authorization"])) {
            [$type, $clientToken] = array_pad(explode(" ", $auth_headers["Authorization"], 2), 2, '');

            if (strcasecmp(trim($type), "Bearer") == 0 && !empty($clientToken)) {
                return true;
            }
        }

        return false;
    }

    /**
     * [hasBearerToken Check if bearer token is present]
     * @return string|null
     */
    public function hasBearerToken()
    {
        $auth_headers = $this->getHeaders();

        if ($this->hasBearerValue()) {
            [$type, $clientToken] = array_pad(explode(" ", $auth_headers["Authorization"], 2), 2, '');

            if (strcasecmp(trim($type), "Bearer") == 0 && !empty($clientToken)) {
                return $clientToken;
            }
        }

        return;
    }

    /**
     * Check if the request is a token grant
     * @return array|boolean
     */
    public function isGrantRequest()
    {
        $auth_headers = $this->getHeaders();
        $helper = new helper();

        if (isset($auth_headers["Authorization"]) && !empty($auth_headers["Authorization"])) {
            if ($grantType = $helper->checkVal($_REQUEST, 'grant_type')) {
                $refreshToken = false;

                if ($grantType == 'client_credentials') {
                    $refreshToken = $this->accessCredentialHeaders($auth_headers);
                }

                if ($grantType == 'refresh_token') {
                    $refreshToken = $this->accessRefreshHeaders($auth_headers);
                }

                if ($refreshToken) {
                    return [
                        'client_access_request' => $refreshToken,
                    ];
                }
            }
        }

        return false;
    }

    /**
     * [accessRefreshHeaders description]
     * @return string|array [mixed: string / error]
     */
    private function accessRefreshHeaders($auth_headers)
    {
        [$type, $clientToken] = array_pad(explode(" ", $auth_headers["Authorization"], 2), 2, '');

        $server = new server([], $this->getOptions());
        $mockTest = $server->isMockTest();

        // @codeCoverageIgnoreStart
        if (strcasecmp($type, "Bearer") == 0 && !empty($clientToken) && !$mockTest) {
            $refreshKey = $this->getRefreshSigningKey();
            $jwt = new auth\jwt();
            $payload = $jwt
                ->setOptions($this->options)
                ->token($clientToken)
                ->key($refreshKey)
                ->decode()
            ;

            if (isset($payload['token_type']) && $payload['token_type'] !== 'refresh') {
                $this->setUnauthorised();
            }

            if (!isset($payload['sub']) || empty($payload['sub'])) {
                $this->setUnauthorised();
            }

            $user = new user\user();
            $account = $user
                ->setOptions($this->options)
                ->load(
                    $payload['sub'],
                    array(
                        'loadBy' => 'account_id',
                        'getJWT' => false,
                        'authorizationRefresh' => true,
                    )
                );

            if (empty($account) || empty($account['refresh_token'])) {
                $this->setUnauthorised();
            }

            $helper = new helper();
            $hashedToken = $helper->hashRefreshToken($clientToken, $refreshKey);
            if (!hash_equals($account['refresh_token'], $hashedToken)) {
                $this->setUnauthorised();
            }

            $account = $user
                ->setOptions($this->options)
                ->load(
                    $payload['sub'],
                    array(
                        'loadBy' => 'account_id',
                        'getJWT' => true,
                        'authorizationRefresh' => true,
                    )
                );

            if (empty($account)) {
                $this->setUnauthorised();
            }

            $tokens = [
                'token' => $account['JWT'],
                'refresh_token' => $account['refreshToken']['token'],
            ];

            $account['refreshToken'] = $tokens;

            return $account;
        } else {
            if ($mockTest) {
                return [
                    'mock_access' => true
                ];
            }
            $this->setUnauthorised();
            return [];
        }
    }
    // @codeCoverageIgnoreEnd

    /**
     * [accessCredentialHeaders Check if the credentials are correct]
     * @param  array $auth_headers
     * @return string|array [mixed: string / error]
     */
    private function accessCredentialHeaders($auth_headers)
    {
        $cipher = new encoder\cipher();

        [$type, $clientCredentials] = array_pad(explode(" ", $auth_headers["Authorization"], 2), 2, '');

        if (strcasecmp($type, "Basic") == 0 && !empty($clientCredentials)) {
            $credentails = explode('/', $clientCredentials);
            if (!empty($credentails) && is_array($credentails)) {
                $credentails = explode(':', $cipher->decode($clientCredentials));

                $server = new server([], $this->getOptions());
                $mockTest = $server->isMockTest();

                if (
                    $mockTest &&
                    (in_array('mockusername', $credentails) && in_array('mockpassword', $credentails))
                ) {
                    return [
                        'uid' => -1,
                        'account_id' => 0,
                        'access_token' => '',
                        'refreshToken' => [
                            'refresh_token' => '',
                        ]
                    ];
                }

                // @codeCoverageIgnoreStart
                if (
                    !empty($credentails) && is_array($credentails) && sizeof($credentails) == 2
                    && !$mockTest
                ) {
                    $user = new user\user();
                    $user->setAccountID($credentails[0]);

                    $account = $user
                        ->setOptions($this->options)
                        ->load(
                            $credentails[0],
                            array(
                                'loadBy' => 'account_id',
                                'getJWT' => true,
                                'authorizationRefresh' => true,
                            )
                        );

                    $tokens = [
                        'token' => $account['JWT'],
                        'refresh_token' => $account['refreshToken']['token'],
                    ];

                    $account['refreshToken'] = $tokens;

                    if (!empty($account)) {
                        if (strcasecmp($account['secret'], $credentails[1]) == 0) {
                            return $account;
                        }
                    }
                }
            }
        } else {
            $this->setUnauthorised();
            return [];
        }
        return [];
    }
    // @codeCoverageIgnoreEnd

    /**
     * [unauthorised Set an unauthorised header]
     * @throws Exception
     *         UNAUTHORIZED 401
     * @return void
     */
    public function setUnauthorised()
    {
        $corsAllowed = ($this->getOptions()['cors']) ?? false;
        $isCorsRequest = ($_SERVER['HTTP_ORIGIN']) ?? false;
        $this->setHeaders($corsAllowed && $isCorsRequest);

        $this->setHeader('HTTP/1.1', array(
            'Unauthorized',
        ), 401);

        (new exception\errorException())
            ->setOptions($this->getOptions())
            ->error('UNAUTHORIZED');
    // @codeCoverageIgnoreStart
    }

    /**
     * [getRefreshSigningKey Resolve the refresh token signing key]
     * @return string
     */
    private function getRefreshSigningKey()
    {
        $jwtOptions = $this->getOptions()['jwt'] ?? [];

        if (isset($jwtOptions['refreshSignWith']) && !empty($jwtOptions['refreshSignWith'])) {
            return $jwtOptions['refreshSignWith'];
        }

        if (isset($jwtOptions['signWith']) && !empty($jwtOptions['signWith'])) {
            return $jwtOptions['signWith'];
        }

        $config = new configuration\config();
        $config->responsibleDefault($this->getOptions());
        $defaults = $config->getDefaults();

        return $defaults['config']['MASTER_KEY'] ?? '';
    }

    // @codeCoverageIgnoreEnd
}
