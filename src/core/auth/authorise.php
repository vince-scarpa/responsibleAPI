<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\oauth
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\auth;

use responsible\core\auth;
use responsible\core\configuration;
use responsible\core\user;
use responsible\core\server;

class authorise extends server
{
    /**
     * [$user]
     * @var object
     */
    public $user;

    /**
     * [__construct Inherit Responsible API options]
     */
    public function __construct($options)
    {
        $this->setOptions($options);
        $this->config = new configuration\config;
        $this->config->responsibleDefault($options);
    }

    /**
     * [auth]
     * @return boolean|object
     */
    public function authorise()
    {
        /**
         * Ignore if debug mode is initiated in Responsible API options
         */
        if ($this->getRequestType() == 'debug') {
            $this->grantAccess = true;
            return true;
        }

        /**
         * Check if a custom scope is set
         */
        if( isset($this->header->getMethod()->data['scope']) && 
            ($this->header->getMethod()->data['scope'] == 'anonymous')
        ) {
            $this->grantAccess = true;
            return true;
        }

        if (isset($this->getOptions()['systemUser']) && !empty($this->getOptions()['systemUser'])) {
            $this->header
                ->setHeader('Authorization', array(
                    'Bearer', $this->getOptions()['systemUser']['token'],
                ), "", "");
        }

        /**
         * Scan for a header Authorization Bearer Json Web Token
         * -- If not set header will return an unauthorised message
         */
        $token = $this->header->authorizationHeaders();

        if (isset($token['client_access_request']) && !empty($token['client_access_request'])) {
            $this->user = (object) $token['client_access_request'];
            $this->grantAccess = true;

        } else {

            /**
             * [$jwt Decode the JWT]
             * @var auth\jwt
             */
            $jwt = new auth\jwt;
            $decoded = $jwt
                ->setOptions($this->getOptions())
                ->token($token)
                ->key('payloadOnly')
                ->decode()
            ;

            if( isset($decoded['sub']) && !empty($decoded['sub']) ) {

                $this->user = (object) (new user\user)
                    ->setOptions($this->getOptions())
                    ->load($decoded['sub'], ['refreshToken' => true])
                ;

                if ( !empty($this->user) ) {
                    $jwt = new auth\jwt;
                    $decoded = $jwt
                        ->setOptions($this->getOptions())
                        ->token($token)
                        ->key($this->user->secret)
                        ->decode()
                    ;
                }
            }else{

                $this->header->unauthorised();
            }
        }

        /**
         * [$user Check user account]
         * @var [object]
         */
        if ( (isset($decoded['sub']) && !empty($decoded['sub'])) && !$this->user ) {
            $this->user = (object) (new user\user)
                ->setOptions($this->getOptions())
                ->load($decoded['sub'], ['refreshToken' => true])
            ;
        }

        /**
         *  Account not found / doesn't exist
         */
        if (empty($this->user)) {
            $this->header->unauthorised();
        }
    }

    /**
     * [user]
     * @return object
     */
    public function user()
    {
        if( $this->isGrantType() ) {
            return (object) [
                'uid' => -1,
                'account_id' => 0,
                'scope' => 'anonymous',
            ];
        }
        return $this->user;
    }

    /**
     * [isGrantType If grant type is set then allow system scope override]
     * @return boolean
     */
    public function isGrantType()
    {
        return $this->grantAccess;
    }

    /**
     * [getJWTToken Get the user JWT refresh object]
     * @return boolean|null
     */
    public function getJWTObject($objectKey, $array = null)
    {
        if ($this->getRequestType() == 'debug') {
            return;
        }

        if( isset($this->header->getMethod()->data['scope']) && 
            ($this->header->getMethod()->data['scope'] == 'anonymous')
        ) {
            return;
        }

        if (is_null($this->user)) {
            return;
        }

        $haystack = (is_null($array)) ? $this->user->refreshToken : $array;

        if (isset($haystack[$objectKey])) {
            return $haystack[$objectKey];
        }

        if (is_array($haystack)) {
            foreach ($haystack as $key => $value) {
                if (is_array($value)) {
                    return $this->getJWTObject($objectKey, $value);
                }
                if (false !== stripos($key, $objectKey)) {
                    return $haystack[$key];
                }
            }
        }
        return false;
    }
}
