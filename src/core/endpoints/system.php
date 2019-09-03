<?php

/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\endpoints
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\endpoints;

use responsible\core\endpoints;
use responsible\core\exception;
use responsible\core\headers;
use responsible\responsible;

class system extends map
{
    /**
     * [$RESPONSE Set the internal system response]
     * @var [array]
     */
    protected $RESPONSE;

    /**
     * [__construct Silence..]
     */
    public function __construct()
    {}

    /**
     * [settings Inherited settings]
     * @return [void]
     */
    public function settings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * [headerMethods]
     * @return [void]
     */
    public function headerMethods()
    {
        $headers = new headers\header;
        $headers->setAllowedMethods(
            ['GET', 'POST', 'PATCH']
        );
    }

    /**
     * [userCreate Create a new user]
     * @return [$account]
     */
    public function userCreate()
    {
        $options = array(
            'jwt' => [
                'issuedAt' => time(),
                'expires' => time() + 86400, // Default 86400
                'notBeFor' => time() + 10,
            ],
        );
        $responsibleUser = responsible::createUser(
            'Vince 1978', // Unique user name
            'vinnie@example.com', // Unique email address
            $options
        );

        $this->RESPONSE = $responsibleUser;
    }

    /**
     * [userCreate Load a user account]
     * @return [$account]
     */
    public function userLoad()
    {
        $options = array(
            'jwt' => [
                'issuedAt' => time(),
                'expires' => time() + 600,
                'notBeFor' => time() + 10,
            ],
            'loadBy' => 'email',
        );
        $responsibleUser = responsible::loadUser('vinnie@example.com', $options);

        $this->RESPONSE = $responsibleUser;

        return $responsibleUser;
    }

    /**
     * [tokenAccess_token Empty function, not handled in this class. See header for method]
     * @see [header->accessCredentialsHeaders()]
     * @return [void]
     */
    public function tokenAccessToken()
    {}

    /**
     * [run Get the system response]
     * @return [array]
     */
    public function run()
    {
        /**
         * Method exists
         */
        if (isset($this->settings['model']['method'])) {
            $method = $this->settings['model']['method'];
            if (method_exists($this, $method)) {
                return call_user_func(array($this, $method));
            }
        }

        /**
         * Method doesn't exists throw an application error
         */
        (new exception\errorException)
            ->message('The requested method `' . $this->settings['model']['method'] . '` dosen\'t exist. Please read the documentation on supported request types.')
            ->error('APPLICATION_ERROR');
    }
}
