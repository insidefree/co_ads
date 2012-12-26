<?php
/**
 * Ronen Amiel <ronen.amiel@gmail.com>
 * 01/12/12, 14:55
 * UserComponent.php
 */

namespace Wix\GoogleDriveBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\Document(collection="tokens")
 */
class Token
{
    /**
     * @MongoDB\Id(strategy="NONE")
     */
    protected $refreshToken;

    /**
     * @MongoDB\String
     */
    protected $accessToken;

    /**
     * @param $id
     */
    public function __construct($id)
    {
        $this->refreshToken = $id;
    }

    /**
     * Set refreshToken
     *
     * @param string $refreshToken
     * @return \User
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    /**
     * Get refreshToken
     *
     * @return string $refreshToken
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * Set accessToken
     *
     * @param string $accessToken
     * @return \User
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Get accessToken
     *
     * @return string $accessToken
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
}
