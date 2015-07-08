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
        $user = $this->getUserDocument();

        $component = $this->getComponentDocument();

        $params = array(
            'adUnit' => $component->getAdUnit(),
            'mobile' => array("width" => 320, "height" => 50)
        );

        if ( $user->getDomain() ) {
            $params['domain'] = $user->getDomain();
        }

        if ($component->hasAdUnit()) {
            $params = array_merge($params, array(
                'code' => $component->getAdCode()
            ));
        }

        return $params;
    }
}
