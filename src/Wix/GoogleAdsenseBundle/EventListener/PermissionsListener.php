<?php

namespace Wix\GoogleAdsenseBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Wix\APIBundle\Base\Bridge;
use Wix\GoogleAdsenseBundle\Exceptions\PermissionsDeniedException;
use Wix\GoogleAdsenseBundle\Configuration\Permission;

/**
 * Listens to KernelController events and makes sure users that access controllers and actions with the @permission
 * annotations have permissions to do so.
 */
class PermissionsListener
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var Decoder
     */
    protected $decoder;

    /**
     * Constructor.
     *
     * @param Reader $reader An Reader instance
     * @param Bridge $decoder
     */
    public function __construct(Reader $reader, Bridge $decoder)
    {
        $this->reader = $reader;
        $this->decoder = $decoder;
    }

    /**
     * Makes sure only wix users with the appropriate permissions can access an action with limited permissions
     * (defined through the permissions annotation).
     *
     * @param FilterControllerEvent $event A FilterControllerEvent instance
     * @throws AccessDeniedException Thrown if the wix user does not have permission to access to page
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $className = get_class($controller[0]);
        $object = new \ReflectionClass($className);
        $method = $object->getMethod($controller[1]);

        $classPermissions  = $this->getPermissions($this->reader->getClassAnnotations($object));
        $methodPermissions = $this->getPermissions($this->reader->getMethodAnnotations($method));

        if (empty($classPermissions) && empty($methodPermissions)) {
            return;
        }

        $permissions = array_merge($classPermissions, $methodPermissions);

        if (!in_array($this->getInstance($event)->getPermissions(), $permissions)) {
            throw new PermissionsDeniedException('Access denied');
        }
    }

    /**
     * Returns an Instance object that represents the current instance.
     *
     * @param FilterControllerEvent $event A FilterControllerEvent instance
     * @return Instance An instance object that represents the current instance
     */
    protected function getInstance(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        $instance = $request->get('instance');

        return $this->decoder->parse($instance);
    }

    /**
     * Returns an array of permissions out of an array of annotations
     *
     * @param array $annotations An array of annotations
     * @return array An array of permissions
     */
    protected function getPermissions(array $annotations)
    {
        foreach($annotations as $annotation) {
            if ($annotation instanceof Permission) {
                return $annotation->getPermissions();
            }
        }

        return array();
    }
}