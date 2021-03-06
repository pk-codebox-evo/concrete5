<?php
namespace Concrete\Block\PageAttributeDisplay;

use Concrete\Core\Block\BlockController;
use Concrete\Core\Attribute\Key\CollectionKey as CollectionAttributeKey;
use Core;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @author Ryan Tyler
 */
class Controller extends BlockController
{
    protected $btTable = 'btPageAttributeDisplay';
    protected $btInterfaceWidth = "500";
    protected $btInterfaceHeight = "365";
    public $dateFormat = "m/d/y h:i:a";
    protected $btCacheBlockOutput = true;
    protected $btCacheBlockOutputOnPost = true;
    protected $btCacheBlockOutputForRegisteredUsers = false;

    /**
     * @var int thumbnail height
     */
    public $thumbnailHeight = 250;

    /**
     * @var int thumbnail width
     */
    public $thumbnailWidth = 250;

    public function getBlockTypeDescription()
    {
        return t("Displays the value of a page attribute for the current page.");
    }

    public function getBlockTypeName()
    {
        return t("Page Attribute Display");
    }

    /**
     * @return mixed AttributeValue
     */
    public function getContent()
    {
        $c = \Page::getCurrentPage();
        $content = "";
        switch ($this->attributeHandle) {
            case "rpv_pageName":
                $content = h($c->getCollectionName());
                break;
            case "rpv_pageDescription":
                $content = nl2br(h($c->getCollectionDescription()));
                break;
            case "rpv_pageDateCreated":
                $content = $c->getCollectionDateAdded();
                break;
            case "rpv_pageDateLastModified":
                $content = $c->getCollectionDateLastModified();
                break;
            case "rpv_pageDatePublic":
                $content = $c->getCollectionDatePublic();
                break;
            default:
                $content = $c->getAttribute($this->attributeHandle);
                if (is_object($content) && $content instanceof \Concrete\Core\Entity\File\File) {
                    if ($this->thumbnailWidth > 0 || $this->thumbnailHeight > 0) {
                        $im = Core::make('helper/image');
                        $thumb = $im->getThumbnail(
                            $content,
                            $this->thumbnailWidth,
                            $this->thumbnailHeight
                        ); //<-- set these 2 numbers to max width and height of thumbnails
                        $content = "<img src=\"{$thumb->src}\" width=\"{$thumb->width}\" height=\"{$thumb->height}\" alt=\"\" />";
                    } else {
                        $image = Core::make('html/image', array($content));
                        $content = (string) $image->getTag();
                    }
                } else {
                    if (!is_scalar($content) && (!is_object($content) || !method_exists($content, '__toString'))) {
                        $content = $c->getAttribute($this->attributeHandle, 'displaySanitized');
                    }
                }
                break;
        }

        $is_stack = $c->getController() instanceof \Concrete\Controller\SinglePage\Dashboard\Blocks\Stacks;
        if (!strlen(trim(strip_tags($content))) && ($c->isMasterCollection() || $is_stack)) {
            $content = $this->getPlaceHolderText($this->attributeHandle);
        }
        
        if(!empty($this->delimiter)) {
            $parts = explode("\n", $content);
            if(count($parts)>1){
                switch ($this->delimiter) {
                    case 'comma':
                        $delimiter = ',';
                        break;
                    case 'commaSpace':
                        $delimiter = ', ';
                        break;
                    case 'pipe':
                        $delimiter = '|';
                        break;
                    case 'dash':
                        $delimiter = '-';
                        break;
                    case 'semicolon':
                        $delimiter = ';';
                        break;
                    case 'semicolonSpace':
                        $delimiter = '; ';
                        break;
                    case 'break':
                        $delimiter = '<br />';
                        break;
                    default:
                        $delimiter = ' ';
                        break;
                }
                $content = implode($delimiter, $parts);
            }
        }

        return $content;
    }

    /**
     * Returns a place holder for pages that are new or when editing default page types.
     *
     * @param string $handle
     *
     * @return string
     */
    public function getPlaceHolderText($handle)
    {
        $pageValues = $this->getAvailablePageValues();
        if (in_array($handle, array_keys($pageValues))) {
            $placeHolder = $pageValues[$handle];
        } else {
            $attributeKey = CollectionAttributeKey::getByHandle($handle);
            if (is_object($attributeKey)) {
                $placeHolder = $attributeKey->getAttributeKeyName();
            }
        }

        return "[" . $placeHolder . "]";
    }

    /**
     * Returns the title text to display in front of the value.
     *
     * @return string
     */
    public function getTitle()
    {
        return strlen($this->attributeTitleText) ? $this->attributeTitleText . " " : "";
    }

    public function getAvailablePageValues()
    {
        return array(
            'rpv_pageName' => t('Page Name'),
            'rpv_pageDescription' => t('Page Description'),
            'rpv_pageDateCreated' => t('Page Date Created'),
            'rpv_pageDatePublic' => t('Page Date Published'),
            'rpv_pageDateLastModified' => t('Page Date Modified'),
        );
    }

    public function getAvailableAttributes()
    {
        return \Concrete\Core\Attribute\Key\CollectionKey::getList();
    }

    protected function getTemplateHandle()
    {
        if (in_array($this->attributeHandle, array_keys($this->getAvailablePageValues()))) {
            switch ($this->attributeHandle) {
                case "rpv_pageDateCreated":
                case 'rpv_pageDateLastModified':
                case "rpv_pageDatePublic":
                    $templateHandle = 'date_time';
                    break;
            }
        } else {
            $attributeKey = CollectionAttributeKey::getByHandle($this->attributeHandle);
            if (is_object($attributeKey)) {
                $attributeType = $attributeKey->getAttributeType();
                $templateHandle = $attributeType->getAttributeTypeHandle();
            }
        }

        return $templateHandle;
    }

    /**
     * Returns opening html tag.
     *
     * @return string
     */
    public function getOpenTag()
    {
        $tag = "";
        if (strlen($this->displayTag)) {
            $tag = "<" . $this->displayTag . " class=\"ccm-block-page-attribute-display-wrapper\">";
        }

        return $tag;
    }

    /**
     * Returns closing html tag.
     *
     * @return string
     */
    public function getCloseTag()
    {
        $tag = "";
        if (strlen($this->displayTag)) {
            $tag = "</" . $this->displayTag . ">";
        }

        return $tag;
    }

    public function view()
    {
        $templateHandle = $this->getTemplateHandle();
        if (in_array($templateHandle, array('date_time', 'boolean'))) {
            $this->render('templates/' . $templateHandle);
        }
    }
}
