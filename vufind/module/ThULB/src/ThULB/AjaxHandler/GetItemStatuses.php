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
    protected function getItemStatusGroup($record, $messages, $callnumberSetting) : array {
        // Summarize call number, location and availability info across all items:
        $locations = [];
        $use_unknown_status = $available = null;
        foreach ($record as $info) {
            // Check for a use_unknown_message flag
            if ($available === null && ($info['use_unknown_message'] ?? false)) {
                $use_unknown_status = true;
                $locations[$info['location']]['status_unknown'] = true;
            }
            else {
                $use_unknown_status = false;
            }

            // Find an available copy
            if ($info['availability']) {
                $available = $locations[$info['location']]['available'] = true;
            }

            // Store call number/location info:
            $locations[$info['location']]['callnumbers'][] = $this->formatCallNo(
                $info['callnumber_prefix'],
                $info['callnumber']
            );
        }

        // Build list split out by location:
        $locationList = [];
        foreach ($locations as $location => $details) {
            $locationCallnumbers = array_unique($details['callnumbers']);
            // Determine call number string based on findings:
            $callnumberHandler = $this->getCallnumberHandler(
                $locationCallnumbers,
                $callnumberSetting
            );
            $locationCallnumbers = $this->pickValue(
                $locationCallnumbers,
                $callnumberSetting,
                'Multiple Call Numbers'
            );
            $locationInfo = [
                'availability' =>
                    $details['available'] ?? false,
                'location' => htmlentities(
                    $this->translateWithPrefix('location_', $location),
                    ENT_COMPAT,
                    'UTF-8'
                ),
                'callnumbers' =>
                    htmlentities($locationCallnumbers, ENT_COMPAT, 'UTF-8'),
                'status_unknown' => $details['status_unknown'] ?? false,
                'callnumber_handler' => $callnumberHandler
            ];
            $locationList[] = $locationInfo;
        }

        // Sort locations by displayed name
        usort($locationList, [$this, 'sortLocationList']);

        $availability_message = $use_unknown_status
            ? $messages['unknown']
            : $messages[$available ? 'available' : 'unavailable'];

        $statusGroup = [
            'id' => $record[0]['id'],
            'availability' => ($available ? 'true' : 'false'),
            'availability_message' => $availability_message,
            'location' => false,
            'locationList' => $locationList,
            'reserve' =>
                ($record[0]['reserve'] == 'Y' ? 'true' : 'false'),
            'reserve_message' => $record[0]['reserve'] == 'Y'
                ? $this->translate('on_reserve')
                : $this->translate('Not On Reserve'),
            'callnumber' => false
        ];

        return $statusGroup;
    }

    protected function sortLocationList($location1, $location2) : int {
        return strcasecmp($location1['location'], $location2['location']);
    }
}
