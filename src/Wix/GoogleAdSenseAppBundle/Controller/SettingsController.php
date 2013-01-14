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
        $this->getDocumentManager()->flush();

        return new JsonResponse('OK');
    }

    /**
     * @Route("/user", name="getUser", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
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
     * @Template()
     */
    public function getAdUnitAction()
    {
        $adUnit = $this->getSerializer()->normalize(
            $this->getAdUnit()
        );

        return new JsonResponse($adUnit);
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

        $adUnit = $this->updateAdUnit(
            $this->getAdUnit(),
            $data
        );

        if ($this->getUserDocument()->connected() === false) {
            // todo refactor
            $decodedAdUnit = $this->decodeAdUnit($adUnit);

            $this->getDocumentManager()->persist(
                $this->getUserDocument()
            );

            $this->getDocumentManager()->flush();

            $this->getDocumentManager()->persist(
                $decodedAdUnit
            );

            $this->getDocumentManager()->flush();

            $this->getUserDocument()->setAdUnit($decodedAdUnit);

            $this->getDocumentManager()->persist(
                $this->getUserDocument()
            );

            $this->getDocumentManager()->flush();
        } else {
            $this->getService()->accounts_adunits->update(
                $this->getUserDocument()->getAccountId(),
                $this->getAfcClientId(), $adUnit
            );
        }

        return new JsonResponse(
            $this->getSerializer()->normalize($adUnit)
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
}
