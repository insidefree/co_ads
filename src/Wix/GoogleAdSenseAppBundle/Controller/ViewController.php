<?php

namespace Wix\GoogleAdSenseAppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/view")
 */
class ViewController extends Controller
{
    /**
     * @Route("/", name="view", options={"expose"=true})
     * @Template()
     */
    public function indexAction()
    {
        return array('name' => 'Ronen');
    }
}
