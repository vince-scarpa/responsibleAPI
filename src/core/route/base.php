<?php
/**
 * ==================================
 * Responsible PHP API
 * ==================================
 *
 * @link Git https://github.com/vince-scarpa/responsibleAPI.git
 *
 * @api Responible API
 * @package responsible\core\route
 *
 * @author Vince scarpa <vince.in2net@gmail.com>
 *
 */
namespace responsible\core\route;

class base
{
    /**
     * [base_url]
     * @return [string]
     */
    public function url()
    {
        $protocol = $this->protocol();

        $base_url = $protocol . '://' . $_SERVER['HTTP_HOST'];

        return $base_url;
    }

    /**
     * [base_uri]
     * @return [string]
     */
    public function uri()
    {
        $basepath = $this->basepath();

        $uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));

        if (strstr($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        $uri = '/' . trim($uri, '/');

        return $uri;
    }

    /**
     * [basepath]
     * @return [string]
     */
    public function basepath()
    {
        return implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1));
    }

    /**
     * [protocol]
     * @return [string]
     */
    public function protocol()
    {
        $https = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';
        return ($https) ? 'https' : 'http';
    }
}
