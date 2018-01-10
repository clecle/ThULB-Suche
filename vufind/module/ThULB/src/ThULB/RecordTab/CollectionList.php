<?php

namespace ThULB\RecordTab;
use VuFind\RecordTab\CollectionList as OriginalCollectionList;

/**
 * Description of CollectionList
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class CollectionList extends OriginalCollectionList
{
    
    /**
     * Can this tab be loaded via AJAX?
     *
     * @return bool
     */
    public function supportsAjax()
    {
        return true;
    }

    /**
     * Is this tab initially visible?
     *
     * @return bool
     */
    public function isVisible()
    {
        $uriParts = explode('/', strstr($this->request->getRequestUri(), '?', true));
        $classParts = explode('\\', get_class($this));
        if (end($uriParts) === end($classParts)) {
            // show the tab, if it is requested directly
            return true;
        }
        
        // the tab will be made visible via javascript, if content exists
        return false;
    }
}
