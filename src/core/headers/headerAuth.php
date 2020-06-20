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

use responsible\core\encoder;
use responsible\core\user;
use responsible\core\helpers\help as helper;

class headerAuth extends header
{
    use \responsible\core\traits\optionsTrait;

    /**
     * [__construct]
     */
    public function __construct() {}

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
            $this->unauthorised();
        }
    }

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
            
            list($type, $clientToken) = explode(" ", $auth_headers["Authorization"], 2);

            if (strcasecmp(trim($type), "Bearer") == 0) {
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

        if( $this->hasBearerValue() ) {

            list($type, $clientToken) = explode(" ", $auth_headers["Authorization"], 2);

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
        $helper = new helper;

        if (isset($auth_headers["Authorization"]) && !empty($auth_headers["Authorization"])) {
            if( $grantType = $helper->checkVal($_REQUEST, 'grant_type') ) {

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
        list($type, $clientToken) = explode(" ", $auth_headers["Authorization"], 2);

        if (strcasecmp($type, "Bearer") == 0 && !empty($clientToken)) {

            $user = new user\user;
            $account = $user
                ->setOptions($this->options)
                ->load(
                    $clientToken,
                    array(
                        'loadBy' => 'refresh_token',
                        'getJWT' => true,
                        'authorizationRefresh' => true,
                    )
                );

            if( empty($account) ) {
                $this->unauthorised();
            }

            $tokens = [
                'token' => $account['JWT'],
                'refresh_token' => $account['refreshToken']['token']
            ];

            $account['refreshToken'] = $tokens;

            return $account;

        } else {
            $this->unauthorised();
        }
    }

    /**
     * [accessCredentialHeaders Check if the credentials are correct]
     * @param  array $auth_headers
     * @return string|array [mixed: string / error]
     */
    private function accessCredentialHeaders($auth_headers)
    {
        $cipher = new encoder\cipher;

        list($type, $clientCredentials) = explode(" ", $auth_headers["Authorization"], 2);

        if (strcasecmp($type, "Basic") == 0 && !empty($clientCredentials)) {
            $credentails = explode('/', $clientCredentials);
            if (!empty($credentails) && is_array($credentails)) {
                $credentails = explode(':', $cipher->decode($clientCredentials));

                if (!empty($credentails) && is_array($credentails) && sizeof($credentails) == 2) {
                    $user = new user\user;
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
                        'refresh_token' => $account['refreshToken']['token']
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
            $this->unauthorised();
        }
    }

    /**
     * [unauthorised Set an unauthorised header]
     * @throws Exception 
     *         UNAUTHORIZED 401
     * @return void
     */
    public function setUnauthorised()
    {
        $this->setHeaders();

        $this->setHeader('HTTP/1.1', array(
            'Unauthorized',
        ), 401);

        (new exception\errorException)->error('UNAUTHORIZED');
    }
}
