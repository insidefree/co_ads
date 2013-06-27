<?php

namespace Wix\GoogleAdsenseBundle\Controller;

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
        $params = array(
            'adUnit' => $this->getAdUnit(),
        );

        $user = $this->getUserDocument();
        $settings = $this->getSettingsDocument();

        if ($settings->hasAdUnit()) {
            $code = $this->getService()->accounts_adunits->getAdCode($user->getAccountId(), $user->getClientId(), $settings->getAdUnitId());

            $params = array_merge($params, array(
                'code' => $code,
                'domain' => $user->getDomain(),
            ));
        }

        return $params;
    }
}
