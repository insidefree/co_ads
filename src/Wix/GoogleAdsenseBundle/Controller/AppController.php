<?php
/**
 * Ronen Amiel <ronen.amiel@gmail.com>
 * 01/12/12, 08:23
 * AppController.php
 */

namespace Wix\GoogleAdsenseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;

use Wix\APIBundle\Base\Instance;

use Wix\GoogleAdsenseBundle\Document\AdUnit;
use Wix\GoogleAdsenseBundle\Document\User;
use Wix\GoogleAdsenseBundle\Document\Token;
use Wix\GoogleAdsenseBundle\Exceptions\MissingParametersException;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;

class AppController extends Controller
{
    /**
     * returns a google client
     * @return \Google_Client
     */
    protected function getClient()
    {
        return $this->get('google_api.oauth2.client');
    }

    /**
     * returns a google service
     * @return \Google_AdsensehostService
     */
    protected function getService()
    {
        return $this->get('google_api.oauth2.adsense_host_service');
    }

    /**
     * returns the config array for the adsense app
     * @return mixed
     */
    protected function getConfig()
    {
        return $this->container->getParameter('wix_google_adsense.config');
    }

    /**
     * returns a document manager
     * @return DocumentManager
     */
    protected function getDocumentManager()
    {
        return $this->get('doctrine.odm.mongodb.document_manager');
    }

    /**
     * returns a repository for a class
     * @param $class
     * @return DocumentRepository
     */
    protected function getRepository($class)
    {
        return $this->getDocumentManager()->getRepository($class);
    }

    /**
     * @return mixed
     */
    protected function getInstanceId()
    {
        return $this->getInstance()->getInstanceId();
    }

    /**
     * returns an instance object
     * @return Instance
     * @throws \Exception
     */
    protected function getInstance()
    {
        $instance = $this->getRequest()->query->get('instance');

        if ($instance === null) {
            throw new \Exception('Missing instance query string parameter.');
        }

        return $this->get('wix_bridge')->parse(
            $instance
        );
    }

    /**
     * @return mixed|null
     */
    private function getCorrectComponentId()
    {
        $query = $this->getRequest()->query;

        return $query->has('origCompId')
            ? $query->get('origCompId')
            : $query->get('compId');
    }

    /**
     * returns a component id
     * @param bool $full
     * @throws MissingParametersException
     * @return mixed
     */
    protected function getComponentId($full = false)
    {
        $componentId = $this->parseComponentId(
            $this->getCorrectComponentId()
        );

        if ($full === false) {
            $componentId = preg_replace("/^(TPWdgt|TPSttngs)/", "", $componentId);
        }

        return $componentId;
    }

    /**
     * @param $componentId
     * @return mixed
     * @throws \Wix\GoogleAdsenseBundle\Exceptions\MissingParametersException
     */
    protected function parseComponentId($componentId)
    {
        if (!$componentId) {
            throw new MissingParametersException('Could not find a component id (originCompId or compId query string parameter).');
        }

        if (preg_match("/^(TPWdgt|TPSttngs)/", $componentId) == false) {
            throw new MissingParametersException('Invalid component id. should be in the format of "TPWdgt" or "TPSttngs" with a digit appended to it.');
        }

        return $componentId;
    }

    /**
     * returns a user document that represents the current user
     * @return User
     */
    protected function getUserDocument()
    {
        $instanceId = $this->getInstanceId();
        $componentId = $this->getComponentId();

        $user = $this->findUserDocument($instanceId, $componentId);

        if ($user === null) {
            $user = new User($instanceId, $componentId);
        }

        return $user;
    }

    /**
     * @param $instanceId
     * @param $componentId
     * @return object
     */
    protected function findUserDocument($instanceId, $componentId)
    {
        return $this->getRepository('WixGoogleAdsenseBundle:User')
            ->findOneBy(array('instanceId' => $instanceId, 'componentId' => $componentId));
    }

    /**
     * returns the current user's ad unit
     * @return AdUnit
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
     * returns a serializer object
     * @return Serializer
     */
    protected function getSerializer()
    {
        return new Serializer(array(new GetSetMethodNormalizer()), array(new JsonEncoder()));
    }

    /**
     * encodes an ad unit to a google ad unit format
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
     * decodes a google ad unit to an ad unit
     * @param \Google_AdUnit $googleAdUnit
     * @return AdUnit
     */
    protected function decodeAdUnit(\Google_AdUnit $googleAdUnit)
    {
        $adUnit = new AdUnit();

        /* ads settings */
        $adUnit->setType($googleAdUnit->getContentAdsSettings()->getType());
        $adUnit->setSize($googleAdUnit->getContentAdsSettings()->getSize());

        /* style */
        $adUnit->setCornerStyle($googleAdUnit->getCustomStyle()->getCorners());

        /* font */
        $adUnit->setFontFamily($googleAdUnit->getCustomStyle()->getFont()->getFamily());
        $adUnit->setFontSize($googleAdUnit->getCustomStyle()->getFont()->getSize());

        /* colors */
        $adUnit->setBackgroundColor($googleAdUnit->getCustomStyle()->getColors()->getBackground());
        $adUnit->setBorderColor($googleAdUnit->getCustomStyle()->getColors()->getBorder());
        $adUnit->setTextColor($googleAdUnit->getCustomStyle()->getColors()->getText());
        $adUnit->setTitleColor($googleAdUnit->getCustomStyle()->getColors()->getTitle());
        $adUnit->setUrlColor($googleAdUnit->getCustomStyle()->getColors()->getUrl());

        return $adUnit;
    }

    /**
     * generates a name for the current ad unit
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