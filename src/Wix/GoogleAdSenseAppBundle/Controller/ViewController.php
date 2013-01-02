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
        $adUnit = $this->getAdUnit();

        if ($adUnit === null) {
            return array();
        }

        $code = $this->getService()->accounts_adunits->getAdCode($this->getUserDocument()->getAccountId(), $this->getAfcClientId(), $adUnit->getId());

        preg_match_all('/\d+/', $adUnit->getContentAdsSettings()->getSize(), $size);

        switch($adUnit->getCustomStyle()->corners) {
            case 'SQUARE':
                $corners = 4;
            break;
            case 'ROUNDED':
                $corners = 6;
            break;
            case 'VERY_ROUNDED':
                $corners = 10;
            break;
            default:
                $corners = null;
            break;
        }

        return array(
            'code' => $code->getAdCode(),
            'adUnit' => $adUnit,
            'width' => $size[0][0],
            'height' => $size[0][1],
            'corners' => $corners
        );
    }
}
