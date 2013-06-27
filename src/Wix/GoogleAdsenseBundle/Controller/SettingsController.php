<?php

namespace Wix\GoogleAdsenseBundle\Controller;

use Wix\GoogleAdsenseBundle\Document\AdUnit;
use Wix\GoogleAdsenseBundle\Document\User;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Wix\GoogleAdsenseBundle\Exceptions\PermissionsDeniedException;
use Wix\GoogleAdsenseBundle\Exceptions\MissingTokenException;
use Wix\GoogleAdsenseBundle\Exceptions\MissingParametersException;
use Wix\GoogleAdsenseBundle\Exceptions\AssociationRejectedException;
use Wix\GoogleAdsenseBundle\Exceptions\InvalidAssociationIdException;
use Wix\GoogleAdsenseBundle\Exceptions\AccountConnectionRequiredException;
use Wix\GoogleAdsenseBundle\Exceptions\AdUnitAlreadyExistsException;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @Route("/settings")
 */
class SettingsController extends AppController
{
    /**
     * sends html back to the user
     * @Route("/", name="settings", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * redirects the user to Google Adsense for authentication
     * @Route("/authenticate", name="authenticate", options={"expose"=true})
     * @Method({"GET"})
     */
    public function authenticateAction()
    {
        if ($this->getInstance()->isOwner() === false) {
            throw new PermissionsDeniedException('access denied.');
        }

        $websiteUrl = $this->getRequest()->query->get('websiteUrl');

        if ($websiteUrl === null) {
            throw new  MissingParametersException('websiteUrl query string parameter is missing.');
        }

        $session = $this->getService()->associationsessions->start(
            'AFC', $websiteUrl
        );

        $this->authenticateUser($session);

        return new RedirectResponse(
            $session->getRedirectUrl()
        );
    }

    /**
     * authenticates a user with a session
     * @param \Google_AssociationSession $session
     */
    protected function authenticateUser(\Google_AssociationSession $session)
    {
        $user = $this->getUserDocument();
        $user->setAssociationId($session->getId());
        $user->setDomain($session->getWebsiteUrl());

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();
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

        $session = $this->getService()->associationsessions->verify($token);

        if ($session->getStatus() === 'REJECTED') {
            throw new AssociationRejectedException('the association was rejected.');
        }

        $account = $this->getService()->accounts->get($session->getAccountId());

        if ($account->status === 'APPROVED') {
            $this->saveUserInformation($session);
        }

        return array();
    }

    /**
     * @param \Google_AssociationSession $session
     * @throws InvalidAssociationIdException
     */
    protected function saveUserInformation(\Google_AssociationSession $session)
    {
        $user = $this->getRepository('WixGoogleAdsenseBundle:User')
            ->findOneBy(array('associationId' => $session->getId()));

        if ($user === null) {
            throw new InvalidAssociationIdException('could not find a matching association Id in the database.');
        }

        $user->setAccountId($session->getAccountId());
        $adClients = $this->getService()->accounts_adclients->listAccountsAdclients($user->getAccountId());

        if (count($adClients->items) === 0) {
            throw new InvalidAssociationIdException('could not find ad client id');
        }

        $user->setClientId($adClients->items[0]->getId());

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();
    }

    /**
     * @Route("/disconnect", name="disconnect", options={"expose"=true})
     * @Method({"POST"})
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

        $this->deleteUserInformation($user);

        return new JsonResponse('OK');
    }

    /**
     * removes information related to this user's google account
     * @param User $user
     */
    protected function deleteUserInformation(User $user)
    {
        if ($user->hasAdUnit()) {
            $this->getService()->accounts_adunits->delete($user->getAccountId(), $user->getClientId(), $user->getAdUnitId());
        }

        $user->setAccountId(null);
        $user->setClientId(null);
        $user->setAdUnitId(null);

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();
    }

    /**
     * returns a JSON representation of a user
     * @Route("/user", name="getUser", options={"expose"=true})
     * @Method({"GET"})
     */
    public function getUserAction()
    {
        $settings = $this->getSerializer()->normalize($this->getSettingsDocument());
        unset($settings['adUnit']);

        return new JsonResponse($settings);
    }

    /**
     * returns a JSON representation of an ad unit
     * @Route("/adunit", name="getAdUnit", options={"expose"=true})
     * @Method({"GET"})
     */
    public function getAdUnitAction()
    {
        $user = $this->getUserDocument();
        $settings = $this->getSettingsDocument();

        if ($settings->hasAdUnit()) {
            $adUnit = $this->getService()->accounts_adunits->get($user->getAccountId(), $user->getClientId(), $settings->getAdUnitId());
            $adUnit = $this->decodeAdUnit($adUnit);
        } else {
            $adUnit = $this->getAdUnit();
        }

        return new JsonResponse(
            $this->getSerializer()->normalize($adUnit)
        );
    }

    /**
    * updates an ad unit with new width and height
    * @Route("/adunit/size", name="saveAdUnitSize", options={"expose"=true})
    * @Method({"POST"})
    */
    public function saveAdUnitSizeAction()
    {
        if ($this->getInstance()->isOwner() === false) {
            throw new PermissionsDeniedException('access denied.');
        }

        $size = $this->getRequest()->getContent();
        $settings = $this->getSettingsDocument();

        $this->saveAdUnitSize($settings, $size);

        $data = $this->getSerializer()->normalize($settings->getAdUnit(), 'json');

        return new JsonResponse($data);
    }

    /**
     * updates a user's ad unit with new sizes
     * @param Settings $settings
     * @param array $size
     */
    protected function saveAdUnitSize(Settings $settings, array $size)
    {
        $settings->getAdUnit()->setWidth($size['width']);
        $settings->getAdUnit()->setHeight($size['height']);

        $this->getDocumentManager()->persist($settings);
        $this->getDocumentManager()->flush();
    }

    /**
     * updates or creates an ad unit with the provided data
     * @Route("/adunit", name="saveAdUnit", options={"expose"=true})
     * @Method({"POST"})
     */
    public function saveAdUnitAction()
    {
        if ($this->getInstance()->isOwner() === false) {
            throw new PermissionsDeniedException('access denied.');
        }

        $data = $this->getRequest()->getContent();

        if (empty($data)) {
            throw new MissingParametersException('could not find request data (expecting request payload to be sent)');
        }

        $adUnit = $this->getSerializer()->deserialize($data, 'Wix\GoogleAdsenseBundle\Document\AdUnit', 'json');
        $settings = $this->getSettingsDocument();

        $this->saveAdUnit($adUnit);

        // if the user has an ad unit created on google, update it too
        if ($settings->hasAdUnit()) {
            $this->updateAdUnit($adUnit);
        }

        return new JsonResponse(
            $this->getSerializer()->normalize($settings->getAdUnit())
        );
    }

    /**
     * saves an ad unit for this user
     * @param AdUnit $adUnit
     */
    protected function saveAdUnit(AdUnit $adUnit)
    {
        $settings = $this->getSettingsDocument();
        $settings->setAdUnit($adUnit);

        $this->getDocumentManager()->persist($settings);
        $this->getDocumentManager()->flush();
    }

    /**
     * updates an ad unit on google
     * @param AdUnit $adUnit
     * @return \Google_AdUnit
     */
    protected function updateAdUnit(AdUnit $adUnit)
    {
        $user = $this->getUserDocument();
        $settings = $this->getSettingsDocument();

        $googleAdUnit = $this->getService()->accounts_adunits->get($user->getAccountId(), $user->getClientId(), $settings->getAdUnitId());

        $googleAdUnit = $this->populateAdUnit($adUnit, $googleAdUnit);

        $this->getService()->accounts_adunits->update($user->getAccountId(), $user->getClientId(), $googleAdUnit);
    }

    /**
     * populates a google ad unit with the values of an ad unit
     * @param AdUnit $adUnit
     * @param \Google_AdUnit $googleAdUnit
     * @return \Google_AdUnit
     */
    protected function populateAdUnit(AdUnit $adUnit, \Google_AdUnit $googleAdUnit)
    {
        $googleAdUnit->getContentAdsSettings()->setType($adUnit->getType());
        $googleAdUnit->getCustomStyle()->setCorners($adUnit->getCornerStyle());

        /* font */
        $googleAdUnit->getCustomStyle()->getFont()->setFamily($adUnit->getFontFamily());
        $googleAdUnit->getCustomStyle()->getFont()->setSize($adUnit->getFontSize());

        /* colors */
        $googleAdUnit->getCustomStyle()->getColors()->setBackground($adUnit->getBackgroundColor());
        $googleAdUnit->getCustomStyle()->getColors()->setBorder($adUnit->getBorderColor());
        $googleAdUnit->getCustomStyle()->getColors()->setText($adUnit->getTextColor());
        $googleAdUnit->getCustomStyle()->getColors()->setTitle($adUnit->getTitleColor());
        $googleAdUnit->getCustomStyle()->getColors()->setUrl($adUnit->getUrlColor());

        return $googleAdUnit;

    }

    /**
     * @Route("/submit", name="submit", options={"expose"=true})
     * @Method({"POST"})
     */
    public function submitAction()
    {
        if ($this->getInstance()->isOwner() === false) {
            throw new PermissionsDeniedException('access denied.');
        }

        if ($this->getUserDocument()->connected() === false) {
            throw new AccountConnectionRequiredException('you have to connect your account before you can submit an ad creation request.');
        }

        if ($this->getSettingsDocument()->getAdUnitId() !== null) {
            throw new AdUnitAlreadyExistsException('an ad unit already exists for this component id. you can only submit an ad unit once per component.');
        }

        $adUnit = $this->insertNewAdUnit();

        return new JsonResponse(
            $this->getSerializer()->normalize(
                $this->decodeAdUnit($adUnit),
                'json'
            )
        );
    }

    /**
     * inserts a new ad unit for this account and returns an object representation of it
     * @return \Google_AdUnit
     */
    protected function insertNewAdUnit()
    {
        $adUnit = $this->encodeAdUnit($this->getAdUnit());
        $adUnit = $this->getService()->accounts_adunits->insert($this->getUserDocument()->getAccountId(), $this->getUserDocument()->getClientId(), $adUnit);
        $this->getSettingsDocument()->setAdUnitId($adUnit->getId());

        $this->getDocumentManager()->persist($this->getUserDocument());
        $this->getDocumentManager()->flush();

        return $adUnit;
    }
}
