<?php
/**
 * Ronen Amiel <ronen.amiel@gmail.com>
 * 01/12/12, 14:55
 * User.php
 */

namespace Wix\GoogleAdsenseBundle\Document;

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
    protected $domain;

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
    protected $clientId;

    /**
     * @param $instanceId
     */
    public function __construct($instanceId)
    {
        $this->instanceId = $instanceId;
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
     * Set domain
     *
     * @param string $domain
     * @return User
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Get domain
     *
     * @return $domain
     */
    public function getDomain()
    {
        return $this->domain;
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
