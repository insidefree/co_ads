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
     * @Route("/", name="view", options={"expose"=true})
     * @Template()
     */
    public function indexAction()
    {
        $componentLocal = $this->getComponentDocument();
        $userLocal      = $this->getUserDocument();
        if ( $componentLocal->getDeletedAt() ) {
            $this->checkIfNeedToReprovision($componentLocal);
        }

        $params = array(
            'adUnit' => $componentLocal->getAdUnit(),
            'mobile' => array("width" => 320, "height" => 50, "regular" => array("width" => 202, "height" => 202))
        );

        if ( $componentLocal->getDeletedAt() ) {
            $params['component_deleted'] = true;
        }

        if ( $userLocal->getDomain() ) {
            $params['domain'] = $userLocal->getDomain();
        }

        if ($componentLocal->hasAdUnit()) {
            $params = array_merge($params, array(
                'code' => $componentLocal->getAdCode()
            ));
        }

        return $params;
    }

    /**
     * @Route("/ad", name="ad", options={"expose"=true})
     * @Method({"GET"})
     * @Template("WixGoogleAdsenseBundle:View:ad.html.twig")
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
            $params = array_merge($params, array(
                'code' => $componentLocal->getAdCode()
            ));
        }

        return $params;
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


    /**
     * When user deletes his app from the editor we mark the component as soft-deleted
     * When he removed the component but didn't click 'Save' in editor he will get the application again
     * In such case (and only if the user itself did the action from the editor) we want to remove the
     * soft-delete flag ("Re-enabled" the app)
     * @param Component $component
     */
    private function checkIfNeedToReprovision(Component $component) {
        $request = $this->getRequest();
        $instance = $request->get('instance');

        //Do not re-enable the component if the source of the request is not from the editor
        if ( !in_array($request->get('viewMode'), array('editor', 'preview')) ) {
            return;
        }

        $wixData = $this->get('wix_bridge')->parse($instance);

        if ( $wixData->getPermissions() == "OWNER" ) {
            if ( $component->getDeletedAt() != '' && $component->getDeletedAt() != null ) {
                $component->setDeletedAt(null);
                $this->getDocumentManager()->persist($component);
                $this->getDocumentManager()->flush();
            }
        }
    }
}
