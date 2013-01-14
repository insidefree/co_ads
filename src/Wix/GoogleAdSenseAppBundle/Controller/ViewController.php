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
        $adUnit = $this->getAdUnit();

        if ($adUnit === null) {
            return array();
        }

        $code = $this->getService()->accounts_adunits->getAdCode($this->getUserDocument()->getAccountId(), $this->getAfcClientId(), $adUnit->getId());

        return array(
            'code' => $code->getAdCode(),
        );
    }
}
