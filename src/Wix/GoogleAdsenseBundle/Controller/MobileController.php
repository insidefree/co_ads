<?php

namespace Wix\GoogleAdsenseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/mobile")
 */
class MobileController extends AppController
{
	/**
	 * @Route("", name="mobile", options={"expose"=true})
	 * @Template()
	 */
	public function indexAction()
	{
		$params = array(
			'adUnit' => $this
					->getComponentDocument()
					->getAdUnit(),
			'mobile' => array(
				"regular" => array("width" => 200, "height" => 200),
			)
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
