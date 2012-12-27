<?php

namespace Wix\GoogleAdSenseAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
}
