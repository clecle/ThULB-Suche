<?php
/**
 * "Get Item Status" AJAX handler
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2018.
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
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Chris Delis <cedelis@uillinois.edu>
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace ThULB\AjaxHandler;

use VuFind\AjaxHandler\GetItemStatuses as OriginalGetItemStatuses;

class GetItemStatuses extends OriginalGetItemStatuses
{
    /**
     * Support method for getItemStatuses() -- process a single bibliographic record
     * for "group" location setting.
     *
     * @param array  $record            Information on items linked to a single
     *                                  bib record
     * @param array  $messages          Custom status HTML
     *                                  (keys = available/unavailable)
     * @param string $callnumberSetting The callnumber mode setting used for
     *                                  pickValue()
     *
     * @return array                    Summarized availability information
     */
    protected function getItemStatusGroup($record, $messages, $callnumberSetting) : array
    {
        $statusGroup = parent::getItemStatusGroup($record, $messages, $callnumberSetting);

        // Use message for status 'unknown' only if there are no other items without status 'unknown'
        $available = null;
        $hasUnknown = false;
        foreach ($statusGroup['locationList'] as $key => $location) {
            if(isset($location['status_unknown']) && $location['status_unknown']) {
                $hasUnknown = true;
            }
            else {
                $available = $available || $location['availability'];
            }
        }
        if($hasUnknown && $available !== null) {
            $msg = $available ? 'available' : 'unavailable';
            $statusGroup['availability_message'] = $messages[$msg];
        }

        // Sort locations by displayed name
        usort($statusGroup['locationList'], [$this, 'sortLocationList']);

        return $statusGroup;
    }

    protected function sortLocationList($location1, $location2) : int {
        return strcasecmp($location1['location'], $location2['location']);
    }
}
