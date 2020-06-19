<?php 
use responsible\core\server;

class options
{
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
}