<?php

namespace Wix\GoogleAdsenseBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wix\GoogleAdsenseBundle\Document\AdUnit;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Wix\GoogleAdsenseBundle\Configuration\Permission;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Wix\GoogleAdsenseBundle\Document\Component;
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
     * sends the initial angularjs application.
     *
     * @Route("/", name="settings", options={"expose"=true})
     * @Method({"GET"})
     * @Permission({"OWNER"})
     * @Template()
     */
    public function indexAction()
    {
        $component = $this->getComponentDocument();

        $pageComponents = $this->getPageComponents($component);

        $params = array();
        if ( array_search($component, $pageComponents) > 2 ) {
            $params['reachedCompLimit'] = true;
        }

        return $params;
    }

    /**
     * redirects users to google adsense for authentication.
     *
     * @Route("/authenticate", name="authenticate", options={"expose"=true})
     * @Method({"GET"})
     * @Permission({"OWNER"})
     */
    public function authenticateAction()
    {
        if (!$websiteUrl = $this->getRequest()->query->get('websiteUrl')) {
            throw new  MissingParametersException('websiteUrl query string parameter is missing.');
        }

        if ( $this->getRequest()->query->get('userId') !== $this->getRequest()->query->get('siteOwnerId')) {
            throw new AccessDeniedHttpException('no permission would be brought to user contributor.');
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
     * after a successful authentication google will redirect users to this route that will save the association to the
     * database.
     *
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
     * disconnects a user and removes the information about it's google adsense account.
     *
     * @Route("/disconnect", name="disconnect", options={"expose"=true})
     * @Method({"POST"})
     * @Permission({"OWNER"})
     */
    public function disconnectAction()
    {
        if ( $this->getRequest()->query->get('userId') != $this->getRequest()->query->get('siteOwnerId')) {
            throw new  AccessDeniedHttpException('no permission would be brought to user contributor.');
        }

        $this->getUserDocument()
            ->setAccountId(null)
            ->setClientId(null);

        foreach ($this->getAllSiteComponents() as $component) {
            if ( !$component instanceof Component ) {
                continue;
            }

            $component
                ->setAdUnitId(null)
                ->setAdCode(null);

            if ($component->hasAdUnit()) {
                $this->removeAdUnit($component->getAdUnitId());
            }
        }

        $this->persist();

        return new JsonResponse('OK');
    }

    /**
     * returns a JSON representation of a user in the database.
     *
     * @Route("/user", name="getUser", options={"expose"=true})
     * @Method({"GET"})
     * @Permission({"OWNER"})
     */
    public function getUserAction()
    {
        $user = $this
            ->getUserDocument();

        return $this->jsonResponse($user);
    }

    /**
     * returns a JSON representation of an ad unit in the database.
     *
     * @Route("/adunit", name="getAdUnit", options={"expose"=true})
     * @Method({"GET"})
     * @Permission({"OWNER"})
     */
    public function getAdUnitAction()
    {
        $component = $this
            ->getComponentDocument()
            ->getAdUnit();

        return $this->jsonResponse($component);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/component", name="getComponent", options={"expose"=true})
     * @Method({"GET"})
     */
    public function getComponentAction()
    {
        $component = $this
            ->getComponentDocument();

        if (!$component instanceof Component) {
            throw new NotFoundHttpException("Component not found");
        }

        return $this->jsonResponse($component);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/component", name="deleteComponent", options={"expose"=true})
     * @Method({"DELETE"})
     * @Permission({"OWNER"})
     */
    public function deleteComponentAction() {
        $component = $this->getComponentDocument();
        if (!$component instanceof Component) {
            throw new NotFoundHttpException("Component not found");
        }

        $component->setDeletedAt(new \DateTime());
        $this->getDocumentManager()->persist($component);
        $this->getDocumentManager()->flush($component);

        return new Response();
    }

    /**
     * updates an ad unit in the database. if the user has an ad unit connected on google it will also get updated.
     *
     * @Route("/component/page_id", name="patchPageId", options={"expose"=true})
     * @Method({"PATCH"})
     */
    public function patchPageId() {
        $component = $this
            ->getComponentDocument();

        if (!$component instanceof Component) {
            throw new NotFoundHttpException("Component not found");
        }
        $data = $this->getRequest()->getContent();
        $data = (array) json_decode($data);

        $component->setPageId($data['page_id']);

        $documentManager = $this->getDocumentManager();
        $documentManager->persist($component);
        $documentManager->flush();

        return $this->jsonResponse($component);
    }

    /**
     * updates an ad unit in the database. if the user has an ad unit connected on google it will also get updated.
     *
     * @Route("/component/updated_date", name="patchUpdatedDate", options={"expose"=true})
     * @Method({"PATCH"})
     */
    public function patchUpdatedDate() {
        $component = $this
            ->getComponentDocument();

        if (!$component instanceof Component) {
            throw new NotFoundHttpException("Component not found");
        }

        $component->setUpdateDate(new \DateTime());

        $documentManager = $this->getDocumentManager();
        $documentManager->persist($component);
        $documentManager->flush();

        return $this->jsonResponse($component);
    }

    /**
     * updates an ad unit in the database. if the user has an ad unit connected on google it will also get updated.
     *
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
     * creates an ad unit on google and associates it with the current component.
     *
     * @Route("/submit", name="submit", options={"expose"=true})
     * @Method({"POST"})
     * @Permission({"OWNER"})
     */
    public function submitAction()
    {
        if (!$this->getUserDocument()->connected()) {
            throw new AccountConnectionRequiredException('you have to connect your account before you can submit an ad creation request.');
        }

        if ($this->getComponentDocument()->hasAdUnit()) {
            throw new AdUnitAlreadyExistsException('an ad unit already exists for this component id. you can only submit an ad unit once per component.');
        }

        $adUnit = $this
            ->getComponentDocument()
            ->getAdUnit();

        $this->insertAdUnit($adUnit);

        return $this->jsonResponse($adUnit);
    }

    /**
     * saves the current user and component documents into the database.
     */
    protected function persist()
    {
        $this->getDocumentManager()->persist($this->getUserDocument());
        $this->getDocumentManager()->persist($this->getComponentDocument());
        $this->getDocumentManager()->flush();
    }

    /**
     * authenticates a user with a session.
     *
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
     * saves information on a user.
     *
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

        $user->setSignedAt(new \DateTime());
        $user->setClientId($adClients->items[0]->getId());

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();
    }

    /**
     * saves an ad unit for this user.
     *
     * @param AdUnit $adUnit
     */
    protected function saveAdUnit(AdUnit $adUnit)
    {
        $component = $this
            ->getComponentDocument()
            ->setAdUnit($adUnit);

        $this->getDocumentManager()->persist($component);
        $this->getDocumentManager()->flush();

        if ($component->hasAdUnit()) {
            $this->updateAdUnit($adUnit);
        }
    }

    /**
     * populates a google ad unit with the values of an ad unit.
     *
     * @param AdUnit $adUnit
     * @param \Google_AdUnit $googleAdUnit
     * @return \Google_AdUnit
     */
    protected function populateAdUnit(AdUnit $adUnit, \Google_AdUnit $googleAdUnit)
    {
        $googleAdUnit
            ->setId($this->getComponentDocument()->getAdUnitId());

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
    protected function createEmptyAdUnit()
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
     * inserts a new ad unit for this account and returns an object representation of it.
     *
     * @param AdUnit $adUnit
     * @return mixed
     */
    protected function insertAdUnit(AdUnit $adUnit)
    {
        $googleAdUnit = $this
            ->populateAdUnit($adUnit, $this->createEmptyAdUnit());

        $googleAdUnit = $this
            ->getService()
            ->accounts_adunits
            ->insert($this->getUserDocument()->getAccountId(), $this->getUserDocument()->getClientId(), $googleAdUnit);

        $adCode = $this
            ->getService()
            ->accounts_adunits
            ->getAdCode($this->getUserDocument()->getAccountId(), $this->getUserDocument()->getClientId(), $googleAdUnit->getId());

        $this
            ->getComponentDocument()
            ->setAdUnitId($googleAdUnit->getId())
            ->setAdCode($adCode->getAdCode());

        $this->persist();
    }

    /**
     * removes an ad unit.
     *
     * @param $adUnitId
     */
    protected function removeAdUnit($adUnitId)
    {
        $this
            ->getService()
            ->accounts_adunits
            ->delete(
                $this->getUserDocument()->getAccountId(),
                $this->getUserDocument()->getClientId(),
                $adUnitId
            );
    }

    /**
     * updates an ad unit on google.
     *
     * @param AdUnit $adUnit
     * @return $this
     */
    protected function updateAdUnit(AdUnit $adUnit)
    {
        $user = $this
            ->getUserDocument();

        $googleAdUnit = $this
            ->getService()
            ->accounts_adunits
            ->get($user->getAccountId(), $user->getClientId(), $this->getComponentDocument()->getAdUnitId());

        $adUnit = $this
            ->populateAdUnit($adUnit, $googleAdUnit);

        $this
            ->getService()
            ->accounts_adunits
            ->update($user->getAccountId(), $user->getClientId(), $adUnit);

        return $this;
    }
}
