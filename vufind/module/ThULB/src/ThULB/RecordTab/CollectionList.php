<?php
/**
 * Collection list tab
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
 * @package  RecordTabs
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_tabs Wiki
 */

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
