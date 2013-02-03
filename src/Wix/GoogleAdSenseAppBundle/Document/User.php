<?php
/**
 * Ronen Amiel <ronen.amiel@gmail.com>
 * 01/12/12, 14:55
 * User.php
 */

namespace Wix\GoogleAdSenseAppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="users")
 * @MongoDB\UniqueIndex(keys={"instanceId"="asc", "componentId"="asc"})
 */
class User
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
     * @MongoDB\Date
     */
    protected $signedAt;

    /**
     * @MongoDB\Date
     */
    protected $createdAt;

    /**
     * @MongoDB\Date
     */
    protected $updatedAt;

    /**
     * @MongoDB\String
     */
    protected $accountId;

    /**
     * @MongoDB\String
     */
    protected $associationId;

    /**
     * @MongoDB\String
     */
    protected $adUnitId;

    /**
     * @MongoDB\String
     */
    protected $clientId;
    
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

        $this->createdAt = new \DateTime();
    }

    /**
     * @return bool
     */
    public function connected()
    {
        return $this->accountId !== null;
    }

    /**
     * @return bool
     */
    public function hasAdUnit()
    {
        $adUnitId = $this->getAdUnitId();

        if ($adUnitId === null) {
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
     * Set createdAt
     *
     * @param $createdAt
     * @return User
     */
    public function setCreatedAt($createdAt)
    {
      $this->createdAt = $createdAt;
      return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime $createdAt
     */
    public function getCreatedAt()
    {
      return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param $updatedAt
     * @return User
     */
    public function setUpdatedAt($updatedAt)
    {
      $this->updatedAt = $updatedAt;
      return $this;
    }

    /**
     * Get updatedAt
     *
     * @return $updatedAt
     */
    public function getUpdatedAt()
    {
      return $this->updatedAt;
    }

    /**
     * Set signedAt
     *
     * @param $signedAt
     * @return User
     */
    public function setSignedAt($signedAt)
    {
      $this->signedAt = $signedAt;
      return $this;
    }

    /**
     * Get signedAt
     *
     * @return $signedAt
     */
    public function getSignedAt()
    {
      return $this->signedAt;
    }

    /**
     * Set instanceId
     *
     * @param string $instanceId
     * @return User
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
     * @return User
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
     * Set accountId
     *
     * @param string $accountId
     * @return User
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;
        return $this;
    }

    /**
     * Get accountId
     *
     * @return string $accountId
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * Set associationId
     *
     * @param string $associationId
     * @return User
     */
    public function setAssociationIdd($associationId)
    {
        $this->associationId = $associationId;
        return $this;
    }

    /**
     * Get associationId
     *
     * @return string $associationId
     */
    public function getAssociationId()
    {
        return $this->associationId;
    }

    /**
     * Set associationId
     *
     * @param string $associationId
     * @return User
     */
    public function setAssociationId($associationId)
    {
        $this->associationId = $associationId;
        return $this;
    }

    /**
     * Set adUnit
     *
     * @param AdUnit $adUnit
     * @return User
     */
    public function setAdUnit(\Wix\GoogleAdSenseAppBundle\Document\AdUnit $adUnit)
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
     * @return \User
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
     * Set clientId
     *
     * @param string $clientId
     * @return \User
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * Get clientId
     *
     * @return string $clientId
     */
    public function getClientId()
    {
        return $this->clientId;
    }
}
