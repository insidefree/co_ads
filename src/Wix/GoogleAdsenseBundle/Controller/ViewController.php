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
            'mobile' => array("width" => 320, "height" => 50)
        );

        $user = $this->getUserDocument();

        if ($user->hasAdUnit()) {
            $code = $this->getService()->accounts_adunits->getAdCode($user->getAccountId(), $user->getClientId(), $user->getAdUnitId());

            $params = array_merge($params, array(
                'code' => $code,
                'domain' => $user->getDomain(),
            ));
        }

        return $params;
    }
}
