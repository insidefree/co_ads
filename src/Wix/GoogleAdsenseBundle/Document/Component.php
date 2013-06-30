<?php
/**
 * Ronen Amiel <ronen.amiel@gmail.com>
 * 01/12/12, 14:55
 * Component.php
 */

namespace Wix\GoogleAdsenseBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="components")
 * @MongoDB\UniqueIndex(keys={"instanceId"="asc", "componentId"="asc"})
 */
class Component
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\String
     */
    protected $instanceId;

    /**
     * @MongoDB\String
     */
    protected $componentId;

    /**
     * @MongoDB\String
     */
    protected $adUnitId;

    /**
     * @MongoDB\String
     */
    protected $adUnitCode;

    /**
     * @MongoDB\EmbedOne(targetDocument="AdUnit")
     */
    protected $adUnit;

    /**
     * @param $instanceId
     * @param $componentId
     */
    public function __construct($instanceId, $componentId)
    {
        $this->instanceId = $instanceId;
        $this->componentId = $componentId;

        $this->adUnit = new AdUnit();

        $this->onPostLoad();
    }

    /**
     * @MongoDB\PostLoad
     */
    public function onPostLoad()
    {
        $this->adUnit->setHasAdUnit((bool) $this->adUnitId);
    }

    /**
     * @return bool
     */
    public function hasAdUnit()
    {
        if (!$this->getAdUnitId()) {
            return false;
        }

        return true;
    }

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set instanceId
     *
     * @param string $instanceId
     * @return Component
     */
    public function setInstanceId($instanceId)
    {
        $this->instanceId = $instanceId;
        return $this;
    }

    /**
     * Get instanceId
     *
     * @return $instanceId
     */
    public function getInstanceId()
    {
        return $this->instanceId;
    }

    /**
     * Set componentId
     *
     * @param string $componentId
     * @return Component
     */
    public function setComponentId($componentId)
    {
        $this->componentId = $componentId;
        return $this;
    }

    /**
     * Get componentId
     *
     * @return $componentId
     */
    public function getComponentId()
    {
        return $this->componentId;
    }

    /**
     * Set adUnit
     *
     * @param AdUnit $adUnit
     * @return Component
     */
    public function setAdUnit(\Wix\GoogleAdsenseBundle\Document\AdUnit $adUnit)
    {
        $this->adUnit = $adUnit;
        return $this;
    }

    /**
     * Get adUnit
     *
     * @return AdUnit $adUnit
     */
    public function getAdUnit()
    {
        return $this->adUnit;
    }

    /**
     * Set adUnitId
     *
     * @param string $adUnitId
     * @return Component
     */
    public function setAdUnitId($adUnitId)
    {
        $this->adUnitId = $adUnitId;
        return $this;
    }

    /**
     * Get adUnitId
     *
     * @return string $adUnitId
     */
    public function getAdUnitId()
    {
        return $this->adUnitId;
    }

    /**
     * Set adUnitCode
     *
     * @param string $adUnitCode
     * @return Component
     */
    public function setAdUnitCode($adUnitCode)
    {
        $this->adUnitCode = $adUnitCode;
        return $this;
    }

    /**
     * Get adUnitCode
     *
     * @return string $adUnitCode
     */
    public function getAdUnitCode()
    {
        return $this->adUnitCode;
    }
}
