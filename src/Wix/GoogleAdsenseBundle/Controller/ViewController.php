<?php

namespace Wix\GoogleAdsenseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
        $user = $this->getUserDocument();

        $component = $this->getComponentDocument();

        if ( $component->getDeletedAt() ) {
            $this->checkIfNeedToReprovision($component);
        }

        $params = array(
            'adUnit' => $component->getAdUnit(),
            'mobile' => array("width" => 320, "height" => 50),
            'reachedCompLimit' => false
        );

        if ( $component->getDeletedAt() ) {
            $params['component_deleted'] = true;
        }

        //Get all components that belong to the same page as the current component
        $pageComponents = $this->getPageComponents($component);
        if ( ($componentLocation = array_search($component, $pageComponents)) !== FALSE ) {
            if ( $componentLocation > 2 ) {
                $params['reachedCompLimit'] = true;
            }
        }

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
        if ( !in_array($request->get('viewMode'), ['editor', 'preview']) ) {
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
