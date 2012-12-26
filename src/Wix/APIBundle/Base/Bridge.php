<?php

namespace Wix\APIBundle\Base;

use Wix\APIBundle\Exceptions\InvalidInstanceException;
use Monolog\Logger;

/**
 * Helper class for interacting with Wix
 * Keeps a Wix instance and manipulates it to return various parameters.
 * @author ronena
 *
 */
class Bridge
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Caches each instance
     * @var array
     */
    protected $cache = array();

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param $instance
     * @return Instance
     */
    public function parse($instance)
    {
        // cache the parsed instance and return it if it's asked again
        if (isset($this->cache[$instance]) === false) {
            $class = $this->config['classes']['instance'];
            $this->cache[$instance] = new $class($this->parseSignedRequest($instance));
        }

        return $this->cache[$instance];
    }

    /**
     * Helper method used for decryption
     * @param $input
     * @return string
     */
    protected function base64UrlDecode($input)
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Takes an encrypted instance and returns a decrypted object representing the instance
     * @param $signed_request
     * @return mixed
     * @throws InvalidInstanceException
     */
    protected function parseSignedRequest($signed_request)
    {
        try {
            list($encoded_sig, $payload) = explode('.', $signed_request, 2);
        }
        catch(\Exception $e) {
            throw new InvalidInstanceException(sprintf('Provided instance is not formatted properly (%s)', $signed_request));
        }

        $sig = $this->base64UrlDecode($encoded_sig);

        $expected_sig = hash_hmac('sha256', $payload, $this->config['keys']['app_secret'], $raw = true);
        if ($sig !== $expected_sig) {
            throw new InvalidInstanceException(sprintf('Provided instance is invalid (%s)', $signed_request));
        }
        $data = json_decode($this->base64UrlDecode($payload));

        return $data;
    }
}