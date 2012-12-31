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
     * @MongoDB\ReferenceOne(targetDocument="Token")
     */
    protected $token;

    /**
     * @MongoDB\String
     */
    protected $type = 'text';

    /**
     * @MongoDB\Int
     */
    protected $width = 400;

    /**
     * @MongoDB\Int
     */
    protected $height = 400;

    /**
     * @MongoDB\Int
     */
    protected $cornerStyle = 0;

    /**
     * @MongoDB\String
     */
    protected $fontStyle = 'Verdana';

    /**
     * @MongoDB\Int
     */
    protected $fontSize = 12;

    /**
     * @MongoDB\String;
     */
    protected $backgroundColor = '#ffffff';

    /**
     * @MongoDB\Boolean
     */
    protected $backgroundTransparent = false;

    /**
     * @MongoDB\String
     */
    protected $titleColor = '#cccccc';

    /**
     * @MongoDB\String
     */
    protected $textColor = '#cccccc';

    /**
     * @MongoDB\String
     */
    protected $urlColor = '#cccccc';

    /**
     * @MongoDB\String
     */
    protected $borderColor = '#cccccc';

    /**
     * @MongoDB\Boolean
     */
    protected $borderTransparent = false;

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
     * @return \User
     */
    public function setInstanceId($instanceId)
    {
        $this->instanceId = $instanceId;
        return $this;
    }

    /**
     * Set componentId
     *
     * @param string $componentId
     * @return \User
     */
    public function setComponentId($componentId)
    {
        $this->componentId = $componentId;
        return $this;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return \User
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set width
     *
     * @param int $width
     * @return \User
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Get width
     *
     * @return int $width
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param int $height
     * @return \User
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Get height
     *
     * @return int $height
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set cornerStyle
     *
     * @param int $cornerStyle
     * @return \User
     */
    public function setCornerStyle($cornerStyle)
    {
        $this->cornerStyle = $cornerStyle;
        return $this;
    }

    /**
     * Get cornerStyle
     *
     * @return int $cornerStyle
     */
    public function getCornerStyle()
    {
        return $this->cornerStyle;
    }

    /**
     * Set fontStyle
     *
     * @param string $fontStyle
     * @return \User
     */
    public function setFontStyle($fontStyle)
    {
        $this->fontStyle = $fontStyle;
        return $this;
    }

    /**
     * Get fontStyle
     *
     * @return string $fontStyle
     */
    public function getFontStyle()
    {
        return $this->fontStyle;
    }

    /**
     * Set fontSize
     *
     * @param int $fontSize
     * @return \User
     */
    public function setFontSize($fontSize)
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    /**
     * Get fontSize
     *
     * @return int $fontSize
     */
    public function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * Set backgroundColor
     *
     * @param string $backgroundColor
     * @return \User
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }

    /**
     * Get backgroundColor
     *
     * @return string $backgroundColor
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Set backgroundTransparent
     *
     * @param boolean $backgroundTransparent
     * @return \User
     */
    public function setBackgroundTransparent($backgroundTransparent)
    {
        $this->backgroundTransparent = $backgroundTransparent;
        return $this;
    }

    /**
     * Get backgroundTransparent
     *
     * @return boolean $backgroundTransparent
     */
    public function getBackgroundTransparent()
    {
        return $this->backgroundTransparent;
    }

    /**
     * Set titleColor
     *
     * @param string $titleColor
     * @return \User
     */
    public function setTitleColor($titleColor)
    {
        $this->titleColor = $titleColor;
        return $this;
    }

    /**
     * Get titleColor
     *
     * @return string $titleColor
     */
    public function getTitleColor()
    {
        return $this->titleColor;
    }

    /**
     * Set textColor
     *
     * @param string $textColor
     * @return \User
     */
    public function setTextColor($textColor)
    {
        $this->textColor = $textColor;
        return $this;
    }

    /**
     * Get textColor
     *
     * @return string $textColor
     */
    public function getTextColor()
    {
        return $this->textColor;
    }

    /**
     * Set urlColor
     *
     * @param string $urlColor
     * @return \User
     */
    public function setUrlColor($urlColor)
    {
        $this->urlColor = $urlColor;
        return $this;
    }

    /**
     * Get urlColor
     *
     * @return string $urlColor
     */
    public function getUrlColor()
    {
        return $this->urlColor;
    }

    /**
     * Set borderColor
     *
     * @param string $borderColor
     * @return \User
     */
    public function setBorderColor($borderColor)
    {
        $this->borderColor = $borderColor;
        return $this;
    }

    /**
     * Get borderColor
     *
     * @return string $borderColor
     */
    public function getBorderColor()
    {
        return $this->borderColor;
    }

    /**
     * Set borderTransparent
     *
     * @param boolean $borderTransparent
     * @return \User
     */
    public function setBorderTransparent($borderTransparent)
    {
        $this->borderTransparent = $borderTransparent;
        return $this;
    }

    /**
     * Get borderTransparent
     *
     * @return boolean $borderTransparent
     */
    public function getBorderTransparent()
    {
        return $this->borderTransparent;
    }
}
