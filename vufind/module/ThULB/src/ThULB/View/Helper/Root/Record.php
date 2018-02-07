<?php

namespace ThULB\View\Helper\Root;
use VuFind\View\Helper\Root\Record as OriginalRecord;

/**
 * Description of Record
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Record extends OriginalRecord
{
    /**
     * Get HTML to render a title. Maximum length limitation is not applied
     * anymore - it happens in javascript code.
     *
     * @param int $maxLength Maximum length of non-highlighted title.
     *
     * @return string
     */
    public function getTitleHtml($maxLength = 180)
    {
        $highlightedTitle = $this->driver->tryMethod('getHighlightedTitle');
        $title = trim($this->driver->tryMethod('getTitle'));
        
        
        if (!empty($highlightedTitle)) {
            $highlight = $this->getView()->plugin('highlight');
            return $highlight($highlightedTitle);
        }
        
        if (!empty($title)) {
            $escapeHtml = $this->getView()->plugin('escapeHtml');
            return $escapeHtml($title);
        }
        
        $transEsc = $this->getView()->plugin('transEsc');
        return $transEsc('Title not available');
    }

    /**
     * Render a list of record formats.
     *
     * @return string
     */
    public function getCitationReferences()
    {
        return $this->renderTemplate('citation-references.phtml');
    }
}
