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
use Wix\GoogleAdSenseAppBundle\Exceptions\MissingParametersException;
use Wix\GoogleAdSenseAppBundle\Document\Token;

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
        $user = $this->getUserDocument();

        $serializer = $this->getSerializer();

        return array('user' => $serializer->serialize($user, 'json'));
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
     * @Route("/user", name="user", options={"expose"=true})
     * @Method({"GET"})
     * @Template()
     */
    public function userAction()
    {
        $user = $this->getUserDocument();

        $serializer = $this->getSerializer();

        return new JsonResponse(json_decode($serializer->serialize($user, 'json')));
    }

    /**
     * @Route("/user", name="save", options={"expose"=true})
     * @Method({"POST"})
     */
    public function saveAction()
    {
        if ($this->getInstance()->isOwner() === false) {
            throw new PermissionsDeniedException('access denied.');
        }

        $data = json_decode($this->getRequest()->getContent());

        if ($data === null) {
            throw new MissingParametersException(sprintf('Missing or not properly configured data (%s).', $data));
        }

        $user = $this->getUserDocument();

        $user->setUpdatedAt(new \DateTime());

        // update stuff for the user
        $user->setBackgroundTransparent($data->backgroundTransparent);
        $user->setBackgroundColor($data->backgroundColor);

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

        $serializer = $this->getSerializer();

        return new JsonResponse(json_decode($serializer->serialize($user, 'json')));
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
     * @return Serializer
     */
    protected function getSerializer()
    {
        $serializer = new Serializer(array(new GetSetMethodNormalizer()), array(new JsonEncoder()));

        return $serializer;
    }

    /**
     * @throws MissingParametersException
     */
    protected function setInstanceAndCompIdFromState()
    {
        $state = $this->getRequest()->query->get('state');

        if ($state === null) {
            throw new MissingParametersException('Missing state query string parameter.');
        }

        parse_str($state, $params);

        $this->getRequest()->query->set('instance', $params['instance']);
        $this->getRequest()->query->set('compId', $params['compId']);
    }
}
