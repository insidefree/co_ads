<?php

namespace Wix\GoogleAdSenseAppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Wix\GoogleAdSenseAppBundle\Exceptions\PermissionsDeniedException;
use Wix\GoogleAdSenseAppBundle\Exceptions\MissingTokenException;
use Wix\GoogleAdSenseAppBundle\Exceptions\MissingParametersException;
use Wix\GoogleAdSenseAppBundle\Exceptions\AssociationRejectedException;
use Wix\GoogleAdSenseAppBundle\Exceptions\InvalidAssociationIdException;

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
        return array();
    }

    /**
     * @Route("/authenticate", name="authenticate", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
     */
    public function authenticateAction()
    {
        $session = $this->getService()->associationsessions->start('AFC', 'http://local.adsense.apps.wix.com/app_dev.php/view/');

        $user = $this->getUserDocument();
        $user->setAssociationIdd($session->getId());

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

        return new RedirectResponse($session->getRedirectUrl());
    }

    /**
     * @Route("/disconnect", name="disconnect", options={"expose"=true})
     * @Method({"POST"})
     * @Template()
     */
    public function disconnectAction()
    {
        if ($this->getInstance()->isOwner() === false) {
            throw new PermissionsDeniedException('access denied.');
        }

        $user = $this->getUserDocument();

        if ($user->connected() === false) {
            throw new \Exception('the associated user is not connected to an AdSense account.');
        }

        $user->setAccountId(null);

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush($user);

        return new JsonResponse(array(
            'code' => 200,
            'message' => 'disconnect successful',
        ));
    }

    /**
     * @Route("/adunit", name="getAdUnit", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
     */
    public function getAdUnitAction()
    {
        $adUnit = $this->getAdUnit();

        return new JsonResponse($adUnit);
    }

    /**
     * @Route("/user", name="getUser", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
     */
    public function getUserAction()
    {
        $user = $this->getUserDocument();

        $user = $this->getSerializer()->normalize($user);

        return new JsonResponse($user);
    }

    /**
     * @Route("/account", name="getAccount", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
     */
    public function getAccountAction()
    {
        $user = $this->getUserDocument();

        $account = $this->getService()->accounts->get($user->getAccountId());

        return new JsonResponse($account);
    }

    /**
     * @Route("/adunit", name="saveAdUnit", options={"expose"=true})
     * @Method({"POST"})
     */
    public function saveAdUnitAction()
    {
        if ($this->getInstance()->isOwner() === false) {
            throw new PermissionsDeniedException('access denied.');
        }

        $data = json_decode(
            $this->getRequest()->getContent()
        );

        if (empty($data)) {
            throw new MissingParametersException('could not find request data (expecting request payload to be sent)');
        }

        $adUnit = $this->getAdUnit();
        $adUnit = $this->updateAdUnit($adUnit, $data);

        $result = $this->getService()->accounts_adunits->update($this->getUserDocument()->getAccountId(), $this->getAfcClientId(), $adUnit);

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
            // todo handle rejection in a more gentle way
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
     * @param \Google_AdUnit $adUnit
     * @param $data
     * @return \Google_AdUnit
     */
    protected function updateAdUnit(\Google_AdUnit $adUnit, $data)
    {
        // todo write a normalizer for the serializer component as soon as I get some time
        $adUnit->getContentAdsSettings()->setType($data->contentAdsSettings->type);
        $adUnit->getContentAdsSettings()->setSize($data->contentAdsSettings->size);
        $adUnit->getCustomStyle()->setCorners($data->customStyle->corners);
        $adUnit->getCustomStyle()->setColors(
            $this->getSerializer()->deserialize(json_encode($data->customStyle->colors), '\Google_AdStyleColors', 'json')
        );
        $adUnit->getCustomStyle()->setFont(
            $this->getSerializer()->deserialize(json_encode($data->customStyle->font), '\Google_AdStyleFont', 'json')
        );

        return $adUnit;
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
     * @return Serializer
     */
    protected function getSerializer()
    {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array(new JsonEncoder()));

        return $serializer;
    }
}
