<?php

namespace Wix\GoogleAdsenseBundle\Controller;

use Wix\GoogleAdsenseBundle\Document\AdUnit;
use Wix\GoogleAdsenseBundle\Document\Component;
use Wix\GoogleAdsenseBundle\Document\User;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Wix\GoogleAdsenseBundle\Configuration\Permission;

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
     * @Permission({"OWNER"})
     */
    public function authenticateAction()
    {
        if (!$websiteUrl = $this->getRequest()->query->get('websiteUrl')) {
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
     * @Route("/redirect", name="redirect")
     * @Template()
     * @Method({"GET"})
     */
    public function redirectAction()
    {
        if (!$token = $this->getRequest()->query->get('token')) {
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
     * @Route("/disconnect", name="disconnect", options={"expose"=true})
     * @Method({"POST"})
     * @Permission({"OWNER"})
     */
    public function disconnectAction()
    {
        $user = $this
            ->getUserDocument();

        $component = $this
            ->getComponentDocument();

        $this->deleteUserInformation($user, $component);

        return new JsonResponse('OK');
    }

    /**
     * returns a JSON representation of a user
     * @Route("/user", name="getUser", options={"expose"=true})
     * @Method({"GET"})
     */
    public function getUserAction()
    {
        $user = $this
            ->getUserDocument();

        return $this->jsonResponse($user);
    }

    /**
     * returns a JSON representation of an ad unit
     * @Route("/adunit", name="getAdUnit", options={"expose"=true})
     * @Method({"GET"})
     */
    public function getAdUnitAction()
    {
        $component = $this
            ->getComponentDocument()
            ->getAdUnit();

        return $this->jsonResponse($component);
    }

    /**
     * updates or creates an ad unit with the provided data
     * @Route("/adunit", name="updateAdUnit", options={"expose"=true})
     * @Method({"POST"})
     * @Permission({"OWNER"})
     */
    public function updateAdUnitAction()
    {
        if (!$data = $this->getRequest()->getContent()) {
            throw new MissingParametersException('could not find request data (expecting request payload to be sent)');
        }

        /** @var AdUnit $adUnit */
        $adUnit = $this
            ->getSerializer()
            ->deserialize($data, 'Wix\GoogleAdsenseBundle\Document\AdUnit', 'json');

        $this->saveAdUnit($adUnit);

        return $this->jsonResponse($adUnit);
    }

    /**
     * @Route("/submit", name="submit", options={"expose"=true})
     * @Method({"POST"})
     * @Permission({"OWNER"})
     */
    public function submitAction()
    {
        if (!$this->getUserDocument()->connected()) {
            throw new AccountConnectionRequiredException('you have to connect your account before you can submit an ad creation request.');
        }

        if ($this->getComponentDocument()->getAdUnitId()) {
            throw new AdUnitAlreadyExistsException('an ad unit already exists for this component id. you can only submit an ad unit once per component.');
        }

        $adUnit = $this
            ->insertNewAdUnit();

        return $this->jsonResponse($adUnit);
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
     * @param \Google_AssociationSession $session
     * @throws InvalidAssociationIdException
     */
    protected function saveUserInformation(\Google_AssociationSession $session)
    {
        $user = $this->getRepository('WixGoogleAdsenseBundle:User')
            ->findOneBy(array('associationId' => $session->getId()));

        if (!$user) {
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
     * @param User $user
     * @param Component $component
     */
    protected function deleteUserInformation(User $user, Component $component)
    {
        if ($component->hasAdUnit()) {
            $this
                ->getService()
                ->accounts_adunits
                ->delete($user->getAccountId(), $user->getClientId(), $component->getAdUnitId());
        }

        $user
            ->setAccountId(null)
            ->setClientId(null);

        $component
            ->setAdUnitId(null);

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->persist($component);
        $this->getDocumentManager()->flush();
    }

    /**
     * saves an ad unit for this user
     * @param AdUnit $adUnit
     */
    protected function saveAdUnit(AdUnit $adUnit)
    {
        $component = $this
            ->getComponentDocument()
            ->setAdUnit($adUnit);

        $this->getDocumentManager()->persist($component);
        $this->getDocumentManager()->flush();

        // if the user has an ad unit created on google, update it too
        $component = $this
            ->getComponentDocument();

        if ($component->hasAdUnit()) {
            $this->updateAdUnit($adUnit);
        }
    }

    /**
     * updates an ad unit on google
     * @param AdUnit $adUnit
     * @return $this
     */
    protected function updateAdUnit(AdUnit $adUnit)
    {
        $user = $this
            ->getUserDocument();

        $component = $this
            ->getComponentDocument();

        $googleAdUnit = $this
            ->getService()
            ->accounts_adunits
            ->get($user->getAccountId(), $user->getClientId(), $component->getAdUnitId());

        $googleAdUnit = $this
            ->populateAdUnit($adUnit, $googleAdUnit);

        $this
            ->getService()
            ->accounts_adunits
            ->update($user->getAccountId(), $user->getClientId(), $googleAdUnit);

        return $this;
    }

    /**
     * populates a google ad unit with the values of an ad unit
     * @param AdUnit $adUnit
     * @param \Google_AdUnit $googleAdUnit
     * @return \Google_AdUnit
     */
    protected function populateAdUnit(AdUnit $adUnit, \Google_AdUnit $googleAdUnit)
    {
        $googleAdUnit
            ->getContentAdsSettings()
            ->setType($adUnit->getType());

        $googleAdUnit
            ->getContentAdsSettings()
            ->setSize($adUnit->getSize());

        $googleAdUnit
            ->getCustomStyle()
            ->setCorners($adUnit->getCornerStyle());

        /* font */
        $googleAdUnit
            ->getCustomStyle()
            ->getFont()
            ->setFamily($adUnit->getFontFamily());

        $googleAdUnit
            ->getCustomStyle()
            ->getFont()
            ->setSize($adUnit->getFontSize());

        /* colors */
        $googleAdUnit
            ->getCustomStyle()
            ->getColors()
            ->setBackground($adUnit->getBackgroundColor());

        $googleAdUnit
            ->getCustomStyle()
            ->getColors()
            ->setBorder($adUnit->getBorderColor());

        $googleAdUnit
            ->getCustomStyle()
            ->getColors()
            ->setText($adUnit->getTextColor());

        $googleAdUnit
            ->getCustomStyle()
            ->getColors()
            ->setTitle($adUnit->getTitleColor());

        $googleAdUnit
            ->getCustomStyle()
            ->getColors()
            ->setUrl($adUnit->getUrlColor());

        return $googleAdUnit;
    }

    /**
     * @return \Google_AdUnit
     */
    protected function getEmptyAdUnit()
    {
        /* backup option */
        $backupOption = new \Google_AdUnitContentAdsSettingsBackupOption();
        $backupOption->setType('COLOR');
        $backupOption->setColor('ffffff');

        /* ads settings */
        $contentAdsSettings = new \Google_AdUnitContentAdsSettings();
        $contentAdsSettings->setBackupOption($backupOption);

        /* colors */
        $colors = new \Google_AdStyleColors();

        /* font */
        $font = new \Google_AdStyleFont();

        /* custom style */
        $customStyle = new \Google_AdStyle();
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
     * inserts a new ad unit for this account and returns an object representation of it
     * @return \Google_AdUnit
     */
    protected function insertNewAdUnit()
    {
        $adUnit = $this
            ->getComponentDocument()
            ->getAdUnit();

        $googleAdUnit = $this
            ->populateAdUnit($adUnit, $this->getEmptyAdUnit());

        $googleAdUnit = $this
            ->getService()
            ->accounts_adunits
            ->insert($this->getUserDocument()->getAccountId(), $this->getUserDocument()->getClientId(), $googleAdUnit);

        $this
            ->getComponentDocument()
            ->setAdUnitId($googleAdUnit->getId());

        $this->getDocumentManager()->persist($this->getUserDocument());
        $this->getDocumentManager()->flush();

        return $adUnit;
    }
}
