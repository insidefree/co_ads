<?php

namespace Wix\APIBundle\Base;

/**
 * @author ronena
 */
class Instance
{
    /**
     * Keeps track of the current instance
     * @var object
     */
    private $instance;

    /**
     * @param $instance
     */
    public function __construct($instance)
    {
        $this->instance = $instance;
    }

    /**
     * Instance getter
     * @return object
     */
    public function getInstance()
    {
    	return $this->instance;
    }

    /**
     * @return bool
     */
    public function isInEditor()
    {
        return $this->isOwner();
    }
    
    /**
     * Returns the instance id or null on failure
     * @return mixed
     */
    public function getInstanceId()
    {
    	if (!isset($this->instance->instanceId)) {
    		return null;
        }
    	
    	return $this->instance->instanceId;
    }
    
    /**
     * Returns the instance sign date or null on failure
     * @return mixed
     */
    public function getSignDate()
    {
    	if (!isset($this->instance->signDate)) {
    		return null;
        }
    	 
    	return $this->instance->signDate;
    }
    
    /**
     * Returns the instance uid or null on failure
     * @return mixed
     */
    public function getUid()
    {
    	if (!isset($this->instance->uid)) {
    		return null;
        }
    
    	return $this->instance->uid;
    }
    
    /**
     * Returns the instance permissions or null on failure
     * @return mixed
     */
    public function getPermissions()
    {
    	if (!isset($this->instance->permissions)) {
    		return null;
        }
    	 
    	return $this->instance->permissions;
    }

    /**
     * @return bool
     */
    public function isOwner()
    {
        return $this->getPermissions() === 'OWNER';
    }

    /**
     * Returns the instance package or null if there is no package
     * @return mixed
     */
    public function getPackage()
    {
        if (!isset($this->instance->vendorProductId)) {
            return null;
        }

        return $this->instance->vendorProductId;
    }
}