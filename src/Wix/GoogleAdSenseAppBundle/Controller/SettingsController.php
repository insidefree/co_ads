<?php

namespace Wix\GoogleAdSenseAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Wix\GoogleAdSenseAppBundle\Exceptions\PermissionsDeniedException;
use Wix\GoogleAdSenseAppBundle\Exceptions\MissingAuthorizationCodeException;
use Wix\GoogleAdSenseAppBundle\Exceptions\InvalidTokenReceivedException;
use Wix\GoogleAdSenseAppBundle\Document\Token;

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
        return array('name' => 'Ronen');
    }

    /**
     * @Route("/authenticate", name="authenticate", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
     */
    public function authenticateAction()
    {
        $instance = $this->getRequest()->query->get('instance');
        $compId = $this->getComponentId(true);

        $this->getClient()->setState('instance=' . $instance . '&compId=' . $compId);

        return new RedirectResponse(
            $this->getClient()->createAuthUrl()
        );
    }

    /**
     * @Route("/redirect", name="redirect")
     * @Template()
     * @Method({"GET"})
     */
    public function redirectAction()
    {
        $this->setInstanceAndCompIdFromState();

        if ($this->getInstance()->isOwner() === false) {
            throw new PermissionsDeniedException('access denied.');
        }

        if (($code = $this->getRequest()->get('code')) === null) {
            throw new MissingAuthorizationCodeException('Missing or invalid authorization code.');
        }

        if (($jsonToken = $this->getClient()->authenticate($code)) === null) {
            throw new InvalidTokenReceivedException('Could not receive token from google.');
        }

        $user = $this->getUserDocument();

        $token = new Token(json_decode($jsonToken)->refresh_token);
        $token->setAccessToken($jsonToken);

        $user->setToken($token);
        $user->setSignedAt(new \DateTime());

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->persist($token);
        $this->getDocumentManager()->flush();

        return array();
    }

    /**
     * @throws MissingParametersException
     */
    private function setInstanceAndCompIdFromState() {
        $state = $this->getRequest()->query->get('state');

        if ($state === null) {
            throw new MissingParametersException('Missing state query string parameter.');
        }

        parse_str($state, $params);

        $this->getRequest()->query->set('instance', $params['instance']);
        $this->getRequest()->query->set('compId', $params['compId']);
    }
}
