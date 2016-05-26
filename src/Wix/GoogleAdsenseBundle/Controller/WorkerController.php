<?php

namespace Wix\GoogleAdsenseBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/worker")
 */
class WorkerController extends AppController
{
    /**
     * @Route("", name="worker", options={"expose"=true})
     * @Method({"GET"})
     * @Template("WixGoogleAdsenseBundle:Worker:index.html.twig")
     */
    public function getIndexAction()
    {
        return [];
    }
}
