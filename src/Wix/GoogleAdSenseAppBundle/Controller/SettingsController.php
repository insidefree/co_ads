<?php

namespace Wix\GoogleAdSenseAppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Wix\GoogleAdSenseAppBundle\Document\AdUnit;
use Wix\GoogleAdSenseAppBundle\Exceptions\PermissionsDeniedException;
use Wix\GoogleAdSenseAppBundle\Exceptions\MissingTokenException;
use Wix\GoogleAdSenseAppBundle\Exceptions\MissingParametersException;
use Wix\GoogleAdSenseAppBundle\Exceptions\AssociationRejectedException;
use Wix\GoogleAdSenseAppBundle\Exceptions\InvalidAssociationIdException;
use Wix\GoogleAdSenseAppBundle\Exceptions\AccountConnectionRequiredException;
use Wix\GoogleAdSenseAppBundle\Exceptions\AdUnitAlreadyExistsException;

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
     */
    public function authenticateAction()
    {
        $session = $this->getService()->associationsessions->start(
            'AFC', 'http://local.adsense.apps.wix.com/app_dev.php/view/'
        );

        $user = $this->getUserDocument();
        $user->setAssociationIdd($session->getId());

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

        return new RedirectResponse(
            $session->getRedirectUrl()
        );
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

        $user->setAccountId(null);

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

        return new JsonResponse('OK');
    }

    /**
     * @Route("/user", name="getUser", options={"expose"=true})
     * @Method({"GET"})
     */
    public function getUserAction()
    {
        $user = $this->getSerializer()->normalize(
            $this->getUserDocument()
        );

        unset($user['adUnit']);

        return new JsonResponse($user);
    }

    /**
     * @Route("/adunit", name="getAdUnit", options={"expose"=true})
     * @Method({"GET"})
     */
    public function getAdUnitAction()
    {
        $user = $this->getUserDocument();

        if ($user->getAdUnitId() !== null) {
            $adUnit = $this->getService()->accounts_adunits->get(
                $user->getAccountId(),
                $user->getClientId(),
                $user->getAdUnitId()
            );

            $adUnit = $this->decodeAdUnit($adUnit);
        } else {
            $adUnit = $this->getAdUnit();
        }


        return new JsonResponse(
            $this->getSerializer()->normalize(
                $adUnit
            )
        );
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

        $data = $this->getRequest()->getContent();

        if (empty($data)) {
            throw new MissingParametersException('could not find request data (expecting request payload to be sent)');
        }

        $adUnit = $this->getSerializer()->deserialize(
            $data,
            'Wix\GoogleAdSenseAppBundle\Document\AdUnit',
            'json'
        );

        $user = $this->getUserDocument();
        $user->setAdUnit($adUnit);

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

        if ($user->getAdUnitId() !== null) {
            $googleAdUnit = $this->getService()->accounts_adunits->get(
                $user->getAccountId(),
                $user->getClientId(),
                $user->getAdUnitId()
            );

            $this->getService()->accounts_adunits->update(
                $user->getAccountId(),
                $user->getClientId(),
                $this->updateAdUnit(
                    $adUnit,
                    $googleAdUnit
                )
            );
        }

        return new JsonResponse(
            $this->getSerializer()->normalize($user->getAdUnit())
        );
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
            throw new AssociationRejectedException('the association was rejected.');
        }

        $user = $this->getRepository('WixGoogleAdSenseAppBundle:User')
            ->findOneBy(array('associationId' => $association->getId()));

        if ($user === null) {
            throw new InvalidAssociationIdException('could not find a matching association Id in the database.');
        }

        // set information
        $user->setAccountId($association->getAccountId());
        $adClients = $this->getService()->accounts_adclients->listAccountsAdclients(
            $user->getAccountId()
        );
        $user->setClientId($adClients->items[0]->getId());

        // persist it
        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

        return array();
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

        if ($this->getUserDocument()->getAdUnitId() !== null) {
            throw new AdUnitAlreadyExistsException('an ad unit already exists for this component id. you can only submit an ad unit once per component.');
        }

        $adUnit = $this->encodeAdUnit(
            $this->getAdUnit()
        );

        $adUnit = $this->getService()->accounts_adunits->insert(
            $this->getUserDocument()->getAccountId(),
            $this->getUserDocument()->getClientId(),
            $adUnit
        );

        $this->getUserDocument()->setAdUnitId($adUnit->getId());

        $this->getDocumentManager()->persist($this->getUserDocument());
        $this->getDocumentManager()->flush();

        return new JsonResponse(
            $this->getSerializer()->normalize(
                $this->decodeAdUnit($adUnit),
                'json'
            )
        );
    }
}
