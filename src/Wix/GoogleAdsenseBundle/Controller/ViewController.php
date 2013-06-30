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
            'adUnit' => $this
                ->getComponentDocument()
                ->getAdUnit(),
        );

        $user = $this
            ->getUserDocument();

        $component = $this
            ->getComponentDocument();

        if ($component->hasAdUnit()) {
            $params = array_merge($params, array(
                'code' => $component->getAdCode(),
                'domain' => $user->getDomain(),
            ));
        }

        return $params;
    }
}
