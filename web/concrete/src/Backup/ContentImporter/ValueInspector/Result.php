<?php

namespace Concrete\Core\Backup\ContentImporter\ValueInspector;


use Concrete\Core\Backup\ContentImporter\ValueInspector\InspectionRoutine\RoutineInterface;
use Concrete\Core\Backup\ContentImporter\ValueInspector\Item\FileItem;
use Concrete\Core\Backup\ContentImporter\ValueInspector\Item\ItemInterface;
use Concrete\Core\Backup\ContentImporter\ValueInspector\Item\PageFeedItem;
use Concrete\Core\Backup\ContentImporter\ValueInspector\Item\PageItem;
use Concrete\Core\Backup\ContentImporter\ValueInspector\Item\PageTypeItem;
use Concrete\Core\Backup\ContentImporter\ValueInspector\Item\PictureItem;
use Concrete\Core\Backup\ContentImporter\ValueInspector\Item\ImageItem;

class Result implements ResultInterface
{

    protected $originalContent;
    protected $replacedContent;
    protected $items = array();
    protected $routines = array();

    public function addInspectionRoutine(RoutineInterface $routine)
    {
        $this->routines[$routine->getHandle()] = $routine;
    }

    public function __construct($originalContent)
    {
        $this->originalContent = $originalContent;
    }

    public function getReplacedContent()
    {
        if (!isset($this->replacedContent)) {
            $this->replacedContent = $this->originalContent;
            foreach($this->routines as $routine) {
                $this->replacedContent = $routine->replaceContent($this->replacedContent);
            }
        }
        return $this->replacedContent;
    }

    public function addMatchedItem(ItemInterface $item)
    {
        $this->items[] = $item;
    }

    public function getMatchedItems()
    {
        return $this->items;
    }

    public function getReplacedValue()
    {
        if (isset($this->items[0])) {
            return $this->items[0]->getFieldValue();
        }
    }

}