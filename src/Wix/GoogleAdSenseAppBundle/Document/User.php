<?php
/**
 * Ronen Amiel <ronen.amiel@gmail.com>
 * 01/12/12, 14:55
 * UserComponent.php
 */

namespace Wix\GoogleAdSenseAppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="users")
 * @MongoDB\UniqueIndex(keys={"instanceId", "componentId"})
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
     * @MongoDB\ReferenceOne(targetDocument="Token")
     */
    protected $token;

    /**
     * @MongoDB\String
     */
    protected $type;

    /**
     * @MongoDB\Int
     */
    protected $width;

    /**
     * @MongoDB\Int
     */
    protected $height;

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
     * @param $token
     * @return User
     */
    public function setToken($token) {
        $this->token = $token;
        return $this;
    }

    /**
     * @return Token
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * @return bool
     */
    public function connected()
    {
        return $this->token !== null;
    }

    /**
     * @return mixed
     */
    public function getInstanceId()
    {
        return $this->instanceId;
    }

    /**
     * Get componentId
     *
     * @return custom_id $componentId
     */
    public function getComponentId()
    {
        return $this->componentId;
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
}
