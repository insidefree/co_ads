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
use Wix\GoogleAdSenseAppBundle\Exceptions\InvalidAssociationIdException;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Wix\GoogleAdSenseAppBundle\Document\User;

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

        $adUnits = $service->accounts_adunits->listAccountsAdunits($this->getAccountId(), $this->getAfcClientId());

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

        $adUnit->setName(sprintf('Wix ad unit for user %s #%s', $this->getInstance()->getInstanceId(), $this->getComponentId()));

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
        $font->setFamily('Arial');
        $font->setSize('Medium');
        $customStyle->setFont($font);
        $adUnit->setCustomStyle($customStyle);

        return $adUnit;
    }

    /**
     * @Route("/authenticate", name="authenticate", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
     */
    public function authenticateAction()
    {
        $service = $this->getService();
        $session = $service->associationsessions->start('AFC', 'http://local.adsense.apps.wix.com/app_dev.php/view/');

        $user = $this->getUserDocument();
        $user->setAssociationIdd($session->getId());

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

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

        $adUnits = $service->accounts_adunits->listAccountsAdunits($this->getAccountId(), $this->getAfcClientId());

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

        $adUnits = $service->accounts_adunits->listAccountsAdunits($this->getAccountId(), $this->getAfcClientId());
        $adUnit = $adUnits->getItems()[0];

        // todo build these objects in a better way, maybe by writing a normalizer for the serializer component
        $adUnit->getContentAdsSettings()->setType($data->contentAdsSettings->type);
        $adUnit->getContentAdsSettings()->setSize($data->contentAdsSettings->size);
        $adUnit->getCustomStyle()->setCorners($data->customStyle->corners);
        $adUnit->getCustomStyle()->setColors(
            $serializer->deserialize(json_encode($data->customStyle->colors), '\Google_AdStyleColors', 'json')
        );
        $adUnit->getCustomStyle()->setFont(
            $serializer->deserialize(json_encode($data->customStyle->font), '\Google_AdStyleFont', 'json')
        );

        $result = $service->accounts_adunits->update($this->getAccountId(), $this->getAfcClientId(), $adUnit);

        return new JsonResponse($result);
    }

    /**
     * @Route("/redirect", name="redirect")
     * @Template()
     * @Method({"GET"})
     */
    public function redirectAction()
    {
        $token = $this->getRequest()->query->get('token');

        if ($token === null) {
            throw new MissingTokenException('could not find a token query string parameter.');
        }

        $association = $this->getService()->associationsessions->verify($token);

        if ($association->getStatus() === 'REJECTED') {
            // todo handle rejection in a more subtle way
            throw new AssociationRejectedException('the association was rejected.');
        }

        $user = $this->getRepository('WixGoogleAdSenseAppBundle:User')
          ->findOneBy(array('associationId' => $association->getId()));

        if ($user === null) {
            throw new InvalidAssociationIdException('could not find a matching association Id in the database.');
        }

        $user->setAccountId($association->getAccountId());

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

        return array();
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
