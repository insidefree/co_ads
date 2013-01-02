<?php
/**
 * Ronen Amiel <ronen.amiel@gmail.com>
 * 01/12/12, 08:23
 * AppController.php
 */

namespace Wix\GoogleAdSenseAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wix\APIBundle\Base\Instance;
use Wix\GoogleAdSenseAppBundle\Document\User;
use Wix\GoogleAdSenseAppBundle\Document\Token;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Wix\GoogleAdSenseAppBundle\Exceptions\MissingParametersException;

class AppController extends Controller
{
    /**
     * @var \Google_Client
     */
    private $client;

    /**
     * @var \Google_AdsensehostService
     */
    private $service;

    /**
     * @var Instance
     */
    private $instance;

    /**
     * @var DocumentManager
     */
    private $manager;

    /**
     * @return \Google_Client
     */
    protected function getClient()
    {
        if ($this->client === null) {
            $this->client = $this->get('google_oauth2')->getClient();
            $config = $this->getConfig();
            $this->client->refreshToken($config['refresh_token']);
        }

        return $this->client;
    }

    /**
     * @return \Google_AdsensehostService
     */
    protected function getService()
    {
        if ($this->service === null) {
            $this->service = $this->get('google_oauth2')->getAdSenseHostService($this->getClient());
        }

        return $this->service;
    }

    /**
     * @return mixed
     */
    protected function getConfig()
    {
        $config = $this->container->getParameter('wix_google_ad_sense_app.config');

        return $config;
    }

    /**
     * @return DocumentManager
     */
    protected function getDocumentManager()
    {
        if ($this->manager === null) {
            $this->manager = $this->get('doctrine.odm.mongodb.document_manager');
        }

        return $this->manager;
    }

    /**
     * @param $class
     * @return DocumentRepository
     */
    protected function getRepository($class)
    {
        return $this->getDocumentManager()->getRepository($class);
    }

    /**
     * @return Instance
     * @throws \Exception
     */
    protected function getInstance()
    {
        if ($this->instance === null) {
            $instance = $this->getRequest()->query->get('instance');
            if ($instance === null) {
                throw new \Exception('Missing instance query string parameter.');
            }

            $this->instance = $this->get('wix_bridge')->parse($instance);
        }

        return $this->instance;
    }

    /**
     * @param bool $full
     * @throws MissingParametersException
     * @return mixed
     */
    protected function getComponentId($full = false)
    {
        $query = $this->getRequest()->query;

        $componentId = $query->has('origCompId') ? $query->get('origCompId') : $query->get('compId');

        if ($componentId === null) {
            throw new MissingParametersException('Could not find a component id (originCompId or compId query string parameter).');
        }

        if (preg_match("/^(TPWdgt|TPSttngs)/", $componentId) == false) {
            throw new MissingParametersException('Invalid component id. should be in the format of "TPWdgt" or "TPSttngs" with a digit appended to it.');
        }

        if ($full === false) {
            $componentId = intval(preg_replace("/^(TPWdgt|TPSttngs)/", "", $componentId));
        }

        return $componentId;
    }

    /**
     * @return null|\Google_AdUnit
     * @throws \Exception
     */
    protected function getAdUnit()
    {
        $user = $this->getUserDocument();

        if ($user->connected() === false) {
            return null;
        }

        $adUnits = $this->getService()->accounts_adunits->listAccountsAdunits(
            $user->getAccountId(),
            $this->getAfcClientId()
        );

        if ($adUnits->getItems() === 0) {
            return null;
        }

        return $adUnits->items[0];
    }

    /**
     * @return User
     */
    protected function getUserDocument()
    {
        $componentId = $this->getComponentId();
        $instanceId = $this->getInstance()->getInstanceId();

        $user = $this->getRepository('WixGoogleAdSenseAppBundle:User')
          ->findOneBy(array(
                  'instanceId' => $instanceId,
                  'componentId' => (string) $componentId,
              ));

        if ($user === null) {
            $user = new User($instanceId, $componentId);
        }

        return $user;
    }

    /**
     * @return string
     */
    protected function getAfcClientId()
    {
        return 'ca-pub-4373694264490992';
    }
}