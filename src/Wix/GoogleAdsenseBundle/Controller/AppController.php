<?php
/**
 * Ronen Amiel <ronen.amiel@gmail.com>
 * 01/12/12, 08:23
 * AppController.php
 */

namespace Wix\GoogleAdsenseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

use Wix\APIBundle\Base\Instance;

use Wix\GoogleAdsenseBundle\Document\Component;
use Wix\GoogleAdsenseBundle\Document\User;
use Wix\GoogleAdsenseBundle\Exceptions\MissingParametersException;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;

class AppController extends Controller
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Component
     */
    protected $component;

    /**
     * returns a google client
     * @return \Google_Client
     */
    protected function getClient()
    {
        return $this
            ->get('google_api.oauth2.client');
    }

    /**
     * returns a google service
     * @return \Google_AdsensehostService
     */
    protected function getService()
    {
        return $this
            ->get('google_api.oauth2.adsense_host_service');
    }

    /**
     * returns the config array for the adsense app
     * @return mixed
     */
    protected function getConfig()
    {
        return $this
            ->container
            ->getParameter('wix_google_adsense.config');
    }

    /**
     * returns a document manager
     * @return DocumentManager
     */
    protected function getDocumentManager()
    {
        return $this
            ->get('doctrine.odm.mongodb.document_manager');
    }

    /**
     * returns a serializer object
     * @return Serializer
     */
    protected function getSerializer()
    {
        return $this
            ->get('jms_serializer');
    }

    /**
     * returns a repository for a class
     * @param $class
     * @return DocumentRepository
     */
    protected function getRepository($class)
    {
        return $this
            ->getDocumentManager()
            ->getRepository($class);
    }

    /**
     * @return mixed
     */
    protected function getInstanceId()
    {
        return $this
            ->getInstance()
            ->getInstanceId();
    }

    /**
     * returns an instance object
     * @return Instance
     * @throws \Exception
     */
    protected function getInstance()
    {
        if (!$instance = $this->getRequest()->query->get('instance')) {
            throw new \Exception('Missing instance query string parameter.');
        }

        return $this->get('wix_bridge')->parse(
            $instance
        );
    }

    /**
     * @return mixed|null
     */
    private function getCorrectComponentId()
    {
        $query = $this->getRequest()->query;

        return $query->has('origCompId')
            ? $query->get('origCompId')
            : $query->get('compId');
    }

    /**
     * returns a component id
     * @param bool $full
     * @throws MissingParametersException
     * @return mixed
     */
    protected function getComponentId($full = false)
    {
        $componentId = $this->parseComponentId(
            $this->getCorrectComponentId()
        );

        if (!$full) {
            $componentId = preg_replace("/^(TPWdgt|TPSttngs)/", "", $componentId);
        }

        return $componentId;
    }

    /**
     * @param mixed $data
     * @return Response
     */
    protected function jsonResponse($data)
    {
        $data = $this->getSerializer()->serialize($data, 'json');

        return new Response($data, 200, array(
            'Content-Type' => 'application/json',
        ));
    }

    /**
     * @param $componentId
     * @return mixed
     * @throws \Wix\GoogleAdsenseBundle\Exceptions\MissingParametersException
     */
    protected function parseComponentId($componentId)
    {
        if (!$componentId) {
            throw new MissingParametersException('Could not find a component id (originCompId or compId query string parameter).');
        }

        if (false && preg_match("/^(TPWdgt|TPSttngs)/", $componentId) == false) {
            throw new MissingParametersException('Invalid component id. should be in the format of "TPWdgt" or "TPSttngs" with a digit appended to it.');
        }

        return $componentId;
    }

    /**
     * returns a user document that represents the current user
     * @return User
     */
    protected function getUserDocument()
    {
        if ($this->user) {
            return $this->user;
        }

        $instanceId = $this->getInstanceId();

        $user = $this->getRepository('WixGoogleAdsenseBundle:User')
            ->findOneBy(array('instanceId' => $instanceId));

        if (!$user) {
            $user = new User($instanceId);
        }

        $this->user = $user;

        return $user;
    }

    /**
     * returns a user document that represents the current user
     * @return Component
     */
    protected function getComponentDocument()
    {
        if ($this->component) {
            return $this->component;
        }

        $instanceId = $this->getInstanceId();
        $componentId = $this->getComponentId();

        $component = $this->getRepository('WixGoogleAdsenseBundle:Component')
            ->findOneBy(array('instanceId' => $instanceId, 'componentId' => $componentId));

        if (!$component) {
            $component = new Component($instanceId, $componentId);
        }

        $this->component = $component;

        return $component;
    }

    /**
     * @return object
     */
    protected function getAllComponents()
    {
        $instanceId = $this->getInstanceId();
        $componentId = $this->getComponentId();

        return $this->getRepository('WixGoogleAdsenseBundle:Component')
            ->findBy(array('instanceId' => $instanceId));
    }

    /**
     * generates a name for the current ad unit
     * @return string
     */
    protected function getAdUnitName()
    {
        return sprintf('Wix ad unit for user %s #%s',
            $this->getInstanceId(),
            $this->getComponentId()
        );
    }
}
