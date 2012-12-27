<?php
/**
 * Ronen Amiel <ronena@codeoasis.com>
 * 12/27/12, 10:03 AM
 * GoogleService.php
 */

namespace GoogleAPI\OAuth2Bundle\Service;

/**
 * Service for retrieving Google client objects
 */
class GoogleService
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @param $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return \Google_Client
     */
    public function getClient()
    {
        $client = new \Google_Client();

        $client->setClientId($this->config['keys']['client_id']);
        $client->setClientSecret($this->config['keys']['client_secret']);
        $client->setRedirectUri($this->config['keys']['redirect_uri']);
        $client->setScopes($this->config['scopes']);
        $client->setUseObjects($this->config['preferences']['use_objects']);

        return $client;
    }

    /**
     * @param \Google_Client $client
     * @return \Google_AdsensehostService
     */
    public function getAdSenseHostService(\Google_Client $client)
    {
        $service = new \Google_AdsensehostService($client);

        return $service;
    }
}