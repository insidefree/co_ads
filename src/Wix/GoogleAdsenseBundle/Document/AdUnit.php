<?php
/**
 * Ronen Amiel <ronen.amiel@gmail.com>
 * 13/01/13, 14:55
 * AdUnit.php
 */

namespace Wix\GoogleAdsenseBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @MongoDB\EmbeddedDocument
 */
class AdUnit
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\String
     */
    protected $type = 'TEXT';

    /**
     * @MongoDB\Int
     */
    protected $width = 300;

    /**
     * @MongoDB\Int
     */
    protected $height = 250;

    /**
     * @MongoDB\String
     */
    protected $cornerStyle = 'SQUARE';

    /**
     * @MongoDB\String
     */
    protected $fontFamily = 'ARIAL';

    /**
     * @MongoDB\String
     */
    protected $fontSize = 'MEDIUM';

    /**
     * @MongoDB\String
     */
    protected $backgroundColor = 'ffffff';

    /**
     * @MongoDB\String
     */
    protected $titleColor = '333333';

    /**
     * @MongoDB\String
     */
    protected $textColor = '666666';

    /**
     * @MongoDB\String
     */
    protected $urlColor = '0066cc';

    /**
     * @MongoDB\String
     */
    protected $borderColor = 'cccccc';

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
     * Set width
     *
     * @param int $width
     * @return AdUnit
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
     * @return AdUnit
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
     * @return string
     */
    public function getSize()
    {
        return 'SIZE_' . $this->width . '_' . $this->height;
    }

    /**
     * @param $size
     * @return $this
     */
    public function setSize($size)
    {
        $size = explode('_', substr($size, 5));

        $this->width = $size[0];
        $this->height = $size[1];

        return $this;
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
}
