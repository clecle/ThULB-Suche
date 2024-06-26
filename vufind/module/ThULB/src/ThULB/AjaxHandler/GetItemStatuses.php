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
        $useUnknownStatus = null;
        $available = null;
        $availableAtLocation = [];

        foreach ($record as $info) {
            // Find an available copy
            if (!isset($info['use_unknown_message'])) {
                $availStr = $this->availabilityToString($info['availability'] ?? false);
                if ('true' !== $available) {
                    $available = $availableAtLocation[$info['location']] = $availStr;
                }
                if ('true' !== ($locations[$info['location']]['available'] ?? null)) {
                    $locations[$info['location']]['available'] = $availStr;
                }

                $useUnknownStatus = false;
                $locations[$info['location']]['status_unknown'] = false;
            }
            // Check for a use_unknown_message flag
            if (!isset($availableAtLocation[$info['location']]) && ($info['use_unknown_message'] ?? false)) {
                if($useUnknownStatus === null) {
                    $useUnknownStatus = true;
                }

                $locations[$info['location']]['status_unknown'] = true;
            }
            // Store call number/location info:
            $locations[$info['location']]['callnumbers'][] = $this->formatCallNo(
                $info['callnumber_prefix'] ?? '',
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
                'availability' => ($details['available'] ?? false) == 'true',
                'location' => htmlentities(
                    $this->translateWithPrefix('location_', $location),
                    ENT_COMPAT,
                    'UTF-8'
                ),
                'callnumbers' =>
                    htmlentities($locationCallnumbers, ENT_COMPAT, 'UTF-8'),
                'status_unknown' => $details['status_unknown'] ?? false,
                'callnumber_handler' => $callnumberHandler,
            ];
            $locationList[] = $locationInfo;
        }

        $availabilityMessage = $this->getAvailabilityMessage(
            $messages,
            $available,
            $useUnknownStatus
        );

        $reserve = ($record[0]['reserve'] ?? 'N') === 'Y';

        // Sort locations by displayed name
        usort($locationList, [$this, 'sortLocationList']);

        // Send back the collected details:
        return [
            'id' => $record[0]['id'],
            'availability' => $available,
            'availability_message' => $availabilityMessage,
            'location' => false,
            'locationList' => $locationList,
            'reserve' => $reserve ? 'true' : 'false',
            'reserve_message'
                => $this->translate($reserve ? 'on_reserve' : 'Not On Reserve'),
            'callnumber' => false,
        ];
    }

    protected function sortLocationList($location1, $location2) : int {
        return strcasecmp($location1['location'], $location2['location']);
    }
}
