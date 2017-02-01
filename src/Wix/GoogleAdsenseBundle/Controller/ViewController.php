<?php

namespace Wix\GoogleAdsenseBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Wix\GoogleAdsenseBundle\Document\Component;
use Wix\GoogleAdsenseBundle\Document\User;

/**
 * @Route("/view")
 */
class ViewController extends AppController
{
    /**
     * @Route("", name="view", options={"expose"=true})
     * @Template()
     */
    public function indexAction()
    {
        $componentLocal = $this->getComponentDocument();
        $userLocal      = $this->getUserDocument();

        $params = array(
            'adUnit' => $componentLocal->getAdUnit(),
            'mobile' => array("width" => 320, "height" => 50, "regular" => array("width" => 202, "height" => 202))
        );

        if ( $userLocal->getDomain() ) {
            $params['domain'] = $userLocal->getDomain();
        }

        if ($componentLocal->hasAdUnit()) {
            $params['code'] = $componentLocal->getAdCode();
        }

        return $params;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/ad", name="ad", options={"expose"=true})
     * @Method({"GET"})
     */
    public function getAdAction()
    {
        $componentLocal = $this->getComponentDocument();
        $userLocal      = $this->getUserDocument();
        $params = array(
            'adUnit' => $componentLocal->getAdUnit(),
            'width'  =>$componentLocal->getAdUnit()->getWidth(),
            'height' =>$componentLocal->getAdUnit()->getHeight(),
            'mobile' => array("width" => 320, "height" => 50, "regular" => array("width" => 202, "height" => 202))
        );

        if ( $userLocal->getDomain() ) {
            $params['domain'] = $userLocal->getDomain();
        }

        if ($componentLocal->hasAdUnit()) {
            $params['code'] = $componentLocal->getAdCode();
        }

        return $this->jsonResponse($params);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/demo", name="demo", options={"expose"=true})
     * @Method({"GET"})
     */
    public function getDemoAction()
    {
        $componentLocal = $this->getComponentDocument();
        $userLocal      = $this->getUserDocument();
        $params = array(
            'adUnit' => $componentLocal->getAdUnit(),
            'width'  =>$componentLocal->getAdUnit()->getWidth(),
            'height' =>$componentLocal->getAdUnit()->getHeight(),
            'mobile' => array("width" => 320, "height" => 50, "regular" => array("width" => 202, "height" => 202))
        );

        if ( $userLocal->getDomain() ) {
            $params['domain'] = $userLocal->getDomain();
        }

        return $this->jsonResponse($params);
    }

    /**
     * @Route("/placeholder", name="placeholder", options={"expose"=true})
     * @Method({"GET"})
     * @Template("WixGoogleAdsenseBundle:View:placeholder.html.twig")
     */
    public function getPlaceholderAction()
    {
        $componentLocal = $this->getComponentDocument();
        $userLocal      = $this->getUserDocument();
        $params = array(
            'adUnit' => $componentLocal->getAdUnit(),
            'mobile' => array("width" => 320, "height" => 50, "regular" => array("width" => 202, "height" => 202))
        );

        if ( $userLocal->getDomain() ) {
            $params['domain'] = $userLocal->getDomain();
        }

        return $params;
    }
}
