<?php
/**
 * Record driver view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 * Copyright (C) Thüringer Universitäts- und Landesbibliothek (ThULB) Jena, 2018.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category ThULB
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Clemens Kynast <clemens.kynast@thulb.uni-jena.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki 
 */

namespace ThULB\View\Helper\Root;
use VuFind\View\Helper\Root\Record as OriginalRecord;

/**
 * Description of Record
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @author Clemens Kynast <clemens.kynast@thulb.uni-jena.de>
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
    
    /**
     * Is this Record OpenAcess?
     *
     * @return string
     */
    public function getOpenAccess()
    {
        return $this->renderTemplate('isopenaccess.phtml');
    }

}
