<?php 
use responsible\core\server;
use responsible\core\auth;

class options
{
    private $jwtHeader;

    public function getApiOptions()
    {
        $options = array(
            /**
             * Output type
             */
            'requestType' => 'array', // json, array, object, xml, html, debug

            /**
             * Headers
             */
            // Max control age header "Access-Control-Max-Age"
            'maxWindow' => 86400, // Defaults to 3600 (1 hour)

            /**
             * Add custom headers
             * Custom headers must be create in the below format
             * nothing will happen if not, no errors no added headers
             */
            'addHeaders' => array(
                ['x-my-new-responsible-api', '1.2'],
            ),

            /**
             * JWT refresh options
             */
            'jwt' => [
                'leeway' => 10, // n seconds leeway for expiry
                'issuedAt' => time(),
                'expires' => time() + 300,
                'notBeFor' => 'issuedAt', // issuedAt, or e.g (time()+10)
            ],

            /**
             * Rate limiter is in conjunction with leaky bucket drip
             */
            'rateLimit' => 10, // API call Limit
            'rateWindow' => 'MINUTE', // Window timeframe SECOND, MINUTE, HOUR, DAY, [CUSTOM/ A POSITIVE INTEGER]

            /**
             * --- Warning ---
             *
             * This will override any rate limits
             * No maximum calls will be set and the Responsible API
             * can run for as many calls and as often as you like
             * This should only be used for system admin calls
             */
            'unlimited' => false, // Unlimited API calls true / false or don't include to default to false

            /**
             * Leaky bucket
             *
             */
            'leak' => true, // Use token bucket "defaults to true"
            'leakRate' => 'default', // slow, medium, normal, default, fast or custom positive integer

            'errors' => 'catchAll',
        );

        $server = new server([], $options);
        $config = $server->getConfig();
        $options['mock'] = $config['MASTER_KEY'];

        return $options;
    }

    public function getMockJwtHeader()
    {
        return $this->setJwtHeaderWith();
    }

    public function setJwtHeaderWith($header = null)
    {
        $options = $this->getApiOptions();
        $requestTime = $_SERVER['REQUEST_TIME'];

        $this->jwtHeader = [
            'sub' => $options['mock'],
            'iss' => 'http://localhost',
            'iat' => $requestTime,
            'exp' => $requestTime+300,
            'nbf' => $requestTime,
        ];

        if (!is_null($header)) {
            $this->jwtHeader = $header;
        }

        return $this->jwtHeader;
    }

    public function getAccessToken()
    {
        $jwt = new auth\jwt;
        $options = $this->getApiOptions();
        $payload = $this->getMockJwtHeader();

        $accessToken = $jwt->key($options['mock'])
            ->setOptions($options)
            ->setPayload($payload)
            ->encode()
        ;

        return $accessToken;
    }

    public function getExceptionMessage($messgageType)
    {
        $ERRORS = array(
            'APPLICATION_ERROR' => array(
                'ERROR_CODE' => 404,
                'ERROR_STATUS' => 'APPLICATION_ERROR',
                'MESSAGE' => '',
            ),

            'NOT_EXTENDED' => array(
                'ERROR_CODE' => 510,
                'ERROR_STATUS' => 'API_ERROR',
                'MESSAGE' => '',
            ),

            'NO_CONTENT' => array(
                'ERROR_CODE' => 200,
                'ERROR_STATUS' => 'NO_CONTENT',
                'MESSAGE' => [
                    'error' => 'empty',
                    'description' => 'No results'
                ],
            ),

            'NOT_FOUND' => array(
                'ERROR_CODE' => 404,
                'ERROR_STATUS' => 'NOT_FOUND',
                'MESSAGE' => [
                    'error' => 'not found',
                    'description' => 'We could not find the resource you requested or the request was not found'
                ],
            ),

            'METHOD_NOT_ALLOWED' => array(
                'ERROR_CODE' => 405,
                'ERROR_STATUS' => 'METHOD_NOT_ALLOWED',
                'MESSAGE' => [
                    'error' => 'not allowed',
                    'description' => 'The method request provided is not allowed'
                ],
            ),

            'UNAUTHORIZED' => array(
                'ERROR_CODE' => 401,
                'ERROR_STATUS' => 'UNAUTHORIZED',
                'MESSAGE' => [
                    'error' => 'denied',
                    'description' => 'Permission denied'
                ],
            ),

            'BAD_REQUEST' => array(
                'ERROR_CODE' => 400,
                'ERROR_STATUS' => 'BAD_REQUEST',
                'MESSAGE' => [
                    'error' => 'bad request',
                    'description' => 'The request provided was Invalid'
                ],
            ),

            'TOO_MANY_REQUESTS' => array(
                'ERROR_CODE' => 429,
                'ERROR_STATUS' => 'TOO_MANY_REQUESTS',
                'MESSAGE' => [
                    'error' => 'too many requests',
                    'description' => 'Too Many Requests'
                ],
            ),
        );

        return $ERRORS[$messgageType];
    }
}