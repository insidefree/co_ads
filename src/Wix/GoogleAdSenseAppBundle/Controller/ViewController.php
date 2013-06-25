<?php

namespace Wix\GoogleAdSenseAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/view")
 */
class ViewController extends AppController
{
    /**
     * @Route("/", name="view", options={"expose"=true})
     * @Template()
     */
    public function indexAction()
    {
        $user = $this->getUserDocument();

        if ($user->hasAdUnit()) {
            $code = $this->getService()->accounts_adunits->getAdCode($user->getAccountId(), $user->getClientId(), $user->getAdUnitId());

            return array(
                'code' => $code,
                'domain' => $user->getDomain(),
            );
        }

        return array(
            'adUnit' => $this->getAdUnit(),
        );
    }
}
