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
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

use Wix\APIBundle\Base\Instance;

use Wix\GoogleAdSenseAppBundle\Document\AdUnit;
use Wix\GoogleAdSenseAppBundle\Document\User;
use Wix\GoogleAdSenseAppBundle\Document\Token;
use Wix\GoogleAdSenseAppBundle\Exceptions\MissingParametersException;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;

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
     * @return null|\Google_AdUnit
     * @throws \Exception
     */
    protected function getAdUnit()
    {
        $adUnit = $this->getUserDocument()->getAdUnit();

        if ($adUnit === null) {
            $adUnit = new AdUnit();
        }

        return $adUnit;
    }

    /**
     * @return Serializer
     */
    protected function getSerializer()
    {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array(new JsonEncoder()));

        return $serializer;
    }

    /**
     * @param AdUnit $adUnit
     * @return \Google_AdUnit
     */
    protected function encodeAdUnit(AdUnit $adUnit)
    {
        /* backup option */
        $backupOption = new \Google_AdUnitContentAdsSettingsBackupOption();
        $backupOption->setType('COLOR');
        $backupOption->setColor('ffffff');

        /* ads settings */
        $contentAdsSettings = new \Google_AdUnitContentAdsSettings();
        $contentAdsSettings->setSize($adUnit->getSize());
        $contentAdsSettings->setType($adUnit->getType());
        $contentAdsSettings->setBackupOption($backupOption);

        /* colors */
        $colors = new \Google_AdStyleColors();
        $colors->setBackground($adUnit->getBackgroundColor());
        $colors->setBorder($adUnit->getBorderColor());
        $colors->setText($adUnit->getTextColor());
        $colors->setTitle($adUnit->getTitleColor());
        $colors->setUrl($adUnit->getUrlColor());

        /* font */
        $font = new \Google_AdStyleFont();
        $font->setFamily($adUnit->getFontFamily());
        $font->setSize($adUnit->getFontSize());

        /* custom style */
        $customStyle = new \Google_AdStyle();
        $customStyle->setCorners($adUnit->getCornerStyle());
        $customStyle->setColors($colors);
        $customStyle->setFont($font);

        /* ad unit */
        $googleAdUnit = new \Google_AdUnit();
        $googleAdUnit->setContentAdsSettings($contentAdsSettings);
        $googleAdUnit->setCustomStyle($customStyle);
        $googleAdUnit->setName($this->getAdUnitName());

        return $googleAdUnit;
    }

    /**
     * @param AdUnit $adUnit
     * @param \Google_AdUnit $googleAdUnit
     */
    protected function updateAdUnit(AdUnit $adUnit, \Google_AdUnit $googleAdUnit)
    {
        $googleAdUnit->getContentAdsSettings()->setType(
            $adUnit->getType()
        );

        $googleAdUnit->getCustomStyle()->setCorners(
            $adUnit->getCornerStyle()
        );

        /* font */
        $googleAdUnit->getCustomStyle()->getFont()->setFamily(
            $adUnit->getFontFamily()
        );
        $googleAdUnit->getCustomStyle()->getFont()->setSize(
            $adUnit->getFontSize()
        );

        /* colors */
        $googleAdUnit->getCustomStyle()->getColors()->setBackground(
            $adUnit->getBackgroundColor()
        );
        $googleAdUnit->getCustomStyle()->getColors()->setBorder(
            $adUnit->getBorderColor()
        );
        $googleAdUnit->getCustomStyle()->getColors()->setText(
            $adUnit->getTextColor()
        );
        $googleAdUnit->getCustomStyle()->getColors()->setTitle(
            $adUnit->getTitleColor()
        );
        $googleAdUnit->getCustomStyle()->getColors()->setUrl(
            $adUnit->getUrlColor()
        );

        return $googleAdUnit;
    }

    /**
     * @param \Google_AdUnit $googleAdUnit
     * @return AdUnit
     */
    protected function decodeAdUnit(\Google_AdUnit $googleAdUnit)
    {
        $adUnit = new AdUnit();

        /* ads settings */
        $adUnit->setType(
            $googleAdUnit->getContentAdsSettings()->getType()
        );
        $adUnit->setSize(
            $googleAdUnit->getContentAdsSettings()->getSize()
        );

        /* style */
        $adUnit->setCornerStyle(
            $googleAdUnit->getCustomStyle()->getCorners()
        );

        /* font */
        $adUnit->setFontFamily(
            $googleAdUnit->getCustomStyle()->getFont()->getFamily()
        );
        $adUnit->setFontSize(
            $googleAdUnit->getCustomStyle()->getFont()->getSize()
        );

        /* colors */
        $adUnit->setBackgroundColor(
            $googleAdUnit->getCustomStyle()->getColors()->getBackground()
        );
        $adUnit->setBorderColor(
            $googleAdUnit->getCustomStyle()->getColors()->getBorder()
        );
        $adUnit->setTextColor(
            $googleAdUnit->getCustomStyle()->getColors()->getText()
        );
        $adUnit->setTitleColor(
            $googleAdUnit->getCustomStyle()->getColors()->getTitle()
        );
        $adUnit->setUrlColor(
            $googleAdUnit->getCustomStyle()->getColors()->getUrl()
        );

        return $adUnit;
    }

    /**
     * @return string
     */
    protected function getAdUnitName()
    {
        return sprintf(
            'Wix ad unit for user %s #%s',
            $this->getInstance()->getInstanceId(),
            $this->getComponentId()
        );
    }
}