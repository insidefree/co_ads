<?php
/**
 * Ronen Amiel <ronen.amiel@gmail.com>
 * 13/01/13, 14:55
 * AdUnit.php
 */

namespace Wix\GoogleAdsenseBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * @MongoDB\EmbeddedDocument
 */
class AdUnit
{
    /**
     * @MongoDB\Id
     * @JMS\Exclude
     */
    protected $id;

    /**
     * @MongoDB\String
     * @JMS\Type("string")
     */
    protected $type = 'TEXT';

    /**
     * @MongoDB\String
     * @JMS\Type("string")
     */
    protected $cornerStyle = 'SQUARE';

    /**
     * @MongoDB\String
     * @JMS\Type("string")
     */
    protected $fontFamily = 'ARIAL';

    /**
     * @MongoDB\String
     * @JMS\Type("string")
     */
    protected $fontSize = 'MEDIUM';

    /**
     * @MongoDB\String
     * @JMS\Type("string")
     */
    protected $backgroundColor = 'ffffff';

    /**
     * @MongoDB\String
     * @JMS\Type("string")
     */
    protected $titleColor = '333333';

    /**
     * @MongoDB\String
     * @JMS\Type("string")
     */
    protected $textColor = '666666';

    /**
     * @MongoDB\String
     * @JMS\Type("string")
     */
    protected $urlColor = '0066cc';

    /**
     * @MongoDB\String
     * @JMS\Type("string")
     */
    protected $borderColor = 'cccccc';

    /**
     * @MongoDB\String
     * @JMS\Type("string")
     */
    protected $size = 'SIZE_300_250';

    /**
     * @JMS\Type("boolean")
     */
    protected $hasAdUnit;

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
     * Set hasAdUnit
     *
     * @param string $hasAdUnit
     * @return AdUnit
     */
    public function setHasAdUnit($hasAdUnit)
    {
        $this->hasAdUnit = $hasAdUnit;
        return $this;
    }

    /**
     * Get hasAdUnit
     *
     * @return string $hasAdUnit
     */
    public function getHasAdUnit()
    {
        return $this->hasAdUnit;
    }

    /**
     * Set id
     *
     * @param string $id
     * @return AdUnit
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return AdUnit
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
     * Set size
     *
     * @param int $size
     * @return AdUnit
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Get size
     *
     * @return int $size
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set cornerStyle
     *
     * @param string $cornerStyle
     * @return AdUnit
     */
    public function setCornerStyle($cornerStyle)
    {
        $this->cornerStyle = $cornerStyle;
        return $this;
    }

    /**
     * Get cornerStyle
     *
     * @return string $cornerStyle
     */
    public function getCornerStyle()
    {
        return $this->cornerStyle;
    }

    /**
     * Set fontFamily
     *
     * @param string $fontFamily
     * @return AdUnit
     */
    public function setFontFamily($fontFamily)
    {
        $this->fontFamily = $fontFamily;
        return $this;
    }

    /**
     * Get fontFamily
     *
     * @return string $fontFamily
     */
    public function getFontFamily()
    {
        return $this->fontFamily;
    }

    /**
     * Set fontSize
     *
     * @param string $fontSize
     * @return AdUnit
     */
    public function setFontSize($fontSize)
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    /**
     * Get fontSize
     *
     * @return string $fontSize
     */
    public function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * Set backgroundColor
     *
     * @param string $backgroundColor
     * @return AdUnit
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
     * Set titleColor
     *
     * @param string $titleColor
     * @return AdUnit
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
     * @return AdUnit
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
     * @return AdUnit
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
     * @return AdUnit
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

    protected function getDimensions()
    {
        return explode('_', substr($this->size, 5));
    }

    public function getWidth()
    {
        $dimensions = $this->getDimensions();

        return $dimensions[0];
    }

    public function getHeight()
    {
        $dimensions = $this->getDimensions();

        return $dimensions[1];
    }
}
