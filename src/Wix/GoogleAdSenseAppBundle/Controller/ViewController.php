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
        $service = $this->getService();

        $adUnits = $service->accounts_adunits->listAccountsAdunits($this->getAccountId(), $this->getAfcClientId());

        $adUnit = $adUnits->items[0];

        $code = $service->accounts_adunits->getAdCode($this->getAccountId(), $this->getAfcClientId(), $adUnit->getId());

        return array('code' => $code->getAdCode());
    }
}
