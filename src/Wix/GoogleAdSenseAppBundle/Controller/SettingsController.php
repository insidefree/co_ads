<?php

namespace Wix\GoogleAdSenseAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Wix\GoogleAdSenseAppBundle\Exceptions\PermissionsDeniedException;
use Wix\GoogleAdSenseAppBundle\Exceptions\MissingTokenException;
use Wix\GoogleAdSenseAppBundle\Exceptions\MissingParametersException;
use Wix\GoogleAdSenseAppBundle\Exceptions\MissingAfcAdClientException;
use Wix\GoogleAdSenseAppBundle\Exceptions\AssociationRejectedException;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/settings")
 */
class SettingsController extends AppController
{
    /**
     * @Route("/", name="settings", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
     */
    public function indexAction()
    {
        $service = $this->getService();

        $adClient = $this->getAfcAdClient($service->accounts_adclients->listAccountsAdclients($this->getAccountId()));

        if ($adClient === null) {
            throw new MissingAfcAdClientException('could not find an ad client with product code of: AFC (adsense for content)');
        }

        $adUnits = $service->accounts_adunits->listAccountsAdunits($this->getAccountId(), $adClient->getId());

        if ($adUnits->getItems() === 0) {
//        $adUnit = $this->getDefaultAdUnit();
//        $result = $service->accounts_adunits->insert($this->getAccountId(), $client->getId(), $adUnit);
        }

        $adUnit = $adUnits->getItems()[0];

        return array('adUnit' => $this->getSerializer()->serialize($adUnit, 'json'));
    }

    /**
     * @return \Google_AdUnit
     */
    protected function getDefaultAdUnit()
    {
        $adUnit = new \Google_AdUnit();

        $adUnit->setName(sprintf('Wix ad unit #%s', $this->getInstance()->getInstanceId()));

        $contentAdsSettings = new \Google_AdUnitContentAdsSettings();
        $backupOption = new \Google_AdUnitContentAdsSettingsBackupOption();
        $backupOption->setType('COLOR');
        $backupOption->setColor('ffffff');
        $contentAdsSettings->setBackupOption($backupOption);
        $contentAdsSettings->setSize('SIZE_300_250');
        $contentAdsSettings->setType('TEXT');
        $adUnit->setContentAdsSettings($contentAdsSettings);

        $customStyle = new \Google_AdStyle();
        $colors = new \Google_AdStyleColors();
        $colors->setBackground('ffffff');
        $colors->setBorder('000000');
        $colors->setText('000000');
        $colors->setTitle('000000');
        $colors->setUrl('0000ff');
        $customStyle->setColors($colors);
        $customStyle->setCorners('SQUARE');
        $font = new \Google_AdStyleFont();
        $font->setFamily('ACCOUNT_DEFAULT_FAMILY');
        $font->setSize('ACCOUNT_DEFAULT_SIZE');
        $customStyle->setFont($font);
        $adUnit->setCustomStyle($customStyle);

        return $adUnit;
    }

    /**
     * @param $clients
     * @internal param $adclients
     * @return null
     */
    protected function getAfcAdClient($clients)
    {
        foreach($clients->getItems() as $client) {
            if ($client->getProductCode() === 'AFC') {
                return $client;
            }
        }

        return null;
    }

    /**
     * @Route("/authenticate", name="authenticate", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
     */
    public function authenticateAction()
    {
        $service = $this->getService();
        $session = $service->associationsessions->start('AFC', 'http://www.just.a.test.com');

        return new RedirectResponse($session->getRedirectUrl());
    }

    /**
     * @Route("/adunit", name="adunit", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
     */
    public function adUnitAction()
    {
        $service = $this->getService();

        $adClient = $this->getAfcAdClient($service->accounts_adclients->listAccountsAdclients($this->getAccountId()));

        if ($adClient === null) {
            throw new MissingAfcAdClientException('could not find an ad client with product code of: AFC (adsense for content)');
        }

        $adUnits = $service->accounts_adunits->listAccountsAdunits($this->getAccountId(), $adClient->getId());

        if ($adUnits->getItems() === 0) {
//        $adUnit = $this->getDefaultAdUnit(sprintf('Wix ad unit #%s', $this->getInstance()->getInstanceId()));
//        $result = $service->accounts_adunits->insert($this->getAccountId(), $client->getId(), $adUnit);
        }

        $adUnit = $adUnits->getItems()[0];

        return new JsonResponse($adUnit);
    }

    /**
     * @Route("/adunit", name="save", options={"expose"=true})
     * @Method({"POST"})
     */
    public function saveAction()
    {
        if ($this->getInstance()->isOwner() === false) {
            throw new PermissionsDeniedException('access denied.');
        }

        $data = json_decode($this->getRequest()->getContent());

        if (empty($data)) {
            throw new MissingParametersException('could not find request data (expecting request payload to be sent)');
        }

        $service = $this->getService();
        $serializer = $this->getSerializer();

        $adUnits = $service->accounts_adunits->listAccountsAdunits($this->getAccountId(), $this->getClientId());
        $adUnit = $adUnits->getItems()[0];

        // todo build these objects in a better way, maybe by writing a normalizer for the serializer component
        $adUnit->getContentAdsSettings()->setType($data->contentAdsSettings->type);
        $adUnit->getContentAdsSettings()->setSize($data->contentAdsSettings->size);
        $adUnit->getCustomStyle()->setCorners($data->customStyle->corners);
        $adUnit->getCustomStyle()->setColors($serializer->deserialize(json_encode($data->customStyle->colors), '\Google_AdStyleColors', 'json'));
        $adUnit->getCustomStyle()->setFont($serializer->deserialize(json_encode($data->customStyle->font), '\Google_AdStyleFont', 'json'));

        $result = $service->accounts_adunits->update($this->getAccountId(), $this->getClientId(), $adUnit);

        return new JsonResponse($result);
    }

    /**
     * @Route("/redirect", name="redirect")
     * @Template()
     * @Method({"GET"})
     */
    public function redirectAction()
    {
       if ($this->getInstance()->isOwner() === false) {
            throw new PermissionsDeniedException('access denied.');
        }

//        $token = $this->getRequest()->query->get('token');
        $token = 'AKH2dBpeo9YghIlHGrVFLa9zQcXtuFe8ahZMgn_00rFinv2rggtKl3VLm0QXZ753Mphpwc-OCcFZBZYyl1YOzF3_0aXb8XZI52J5nNt0fmX1X5heEDEnn4B3uIQLf31qNTnJB-EpGKWmyGlaLBqAvB23sAYbe3cn422egvyzw1PX8Cxg1-2paCPx4HItQoNJFRLbtI9bdhsC4NihU1CYIgTkzmp2PYJf_Q';

        if ($token === null) {
            throw new MissingTokenException('could not find a token query string parameter.');
        }

        $association = $this->getService()->associationsessions->verify($token);

        if ($association->getStatus() === 'REJECTED') {
            // todo handle rejection in a more subtle way
            throw new AssociationRejectedException('the association was rejected.');
        }

        // do stuff with the association
    }

    /**
     * @return Serializer
     */
    protected function getSerializer()
    {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array(new JsonEncoder()));

        return $serializer;
    }
}
