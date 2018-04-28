<?php

/**
 * @package toolkit
 */
/**
 * Creates a filter that only returns XMLElement items
 */
class XMLElementChildrenFilter extends FilterIterator
{
    public function accept()
    {
        $item = $this->getInnerIterator()->current();
        return $item instanceof XMLElement;
    }
}
