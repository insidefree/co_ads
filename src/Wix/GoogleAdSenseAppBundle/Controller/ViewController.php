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

        $adUnits = $service->accounts_adunits->listAccountsAdunits($this->getAccountId(), $this->getClientId());

        if ($adUnits->getItems() === 0) {
//        $adUnit = $this->getDefaultAdUnit();
//        $result = $service->accounts_adunits->insert($this->getAccountId(), $client->getId(), $adUnit);
        }

        $adUnit = $adUnits->getItems()[0];

        $code = $service->accounts_adunits->getAdCode($this->getAccountId(), $this->getClientId(), $adUnit->getId());

        return array('code' => $code->getAdCode());
    }
}
