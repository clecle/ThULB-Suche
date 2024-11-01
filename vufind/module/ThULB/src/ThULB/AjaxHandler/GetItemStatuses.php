<?php

/**
 * "Get Item Status" AJAX handler
 *
 * PHP version 8
 *
 * Copyright (C) Villanova University 2018.
 * Copyright (C) The National Library of Finland 2023.
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

use Laminas\Config\Config;
use Laminas\View\Renderer\RendererInterface;
use VuFind\AjaxHandler\GetItemStatuses as OriginalGetItemStatuses;
use VuFind\ILS\Connection;
use VuFind\ILS\Logic\AvailabilityStatusInterface;
use VuFind\ILS\Logic\AvailabilityStatusManager;
use VuFind\ILS\Logic\Holds;
use VuFind\Session\Settings as SessionSettings;

class GetItemStatuses extends OriginalGetItemStatuses
{
    protected ?Config $thulbConfig;

    public function __construct(
        SessionSettings $ss,
        Config $config,
        Connection $ils,
        RendererInterface $renderer,
        Holds $holdLogic,
        AvailabilityStatusManager $availabilityStatusManager,
        Config $thulbConfig = null,
    ) {
        parent::__construct($ss, $config, $ils, $renderer, $holdLogic, $availabilityStatusManager);

        $this->thulbConfig = $thulbConfig;
    }

    /**
     * Support method for getItemStatuses() -- process a single bibliographic record
     * for "group" location setting.
     *
     * @param array  $record            Information on items linked to a single
     *                                  bib record
     * @param string $callnumberSetting The callnumber mode setting used for
     *                                  pickValue()
     *
     * @return array                    Summarized availability information
     */
    protected function getItemStatusGroup($record, $callnumberSetting) : array {
        // Summarize call number, location and availability info across all items:
        $locations = [];
        $onlyHandsets = true;

        $itemList = $record;
        foreach ($record as $key => $info) {
            if(in_array($info['departmentId'], $this->thulbConfig?->ItemStatus?->exclude?->toArray() ?? [])) {
                unset($itemList[$key]);
                continue;
            }

            // do not display location information for handsets
            if($info['isHandset'] ?? false) {
                continue;
            }
            $onlyHandsets = false;

            $availabilityStatus = $info['availability'];
            // Find an available copy
            if ($availabilityStatus->isAvailable()) {
                if ('true' !== ($locations[$info['location']]['available'] ?? null)) {
                    $locations[$info['location']]['available'] = $availabilityStatus->getStatusDescription();
                }
            }
            // Store call number/location info:
            $locations[$info['location']]['callnumbers'][] = $this->formatCallNo(
                $info['callnumber_prefix'] ?? '',
                $info['callnumber']
            );
            $locations[$info['location']]['items'][] = $info;
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

            // Get combined availability for location
            $locationStatus = $this->availabilityStatusManager->combine($details['items']);
            $locationAvailability = $locationStatus['availability'];

            $locationInfo = [
                'availability' =>
                    $locationAvailability->availabilityAsString(),
                'location' => htmlentities(
                    $this->translateWithPrefix('location_', $location),
                    ENT_COMPAT,
                    'UTF-8'
                ),
                'callnumbers' =>
                    htmlentities($locationCallnumbers, ENT_COMPAT, 'UTF-8'),
                'status_unknown' =>
                    $locationAvailability->is(AvailabilityStatusInterface::STATUS_UNKNOWN),
                'callnumber_handler' => $callnumberHandler,
            ];
            $locationList[] = $locationInfo;
        }

        // Get combined availability
        $combinedInfo = $this->availabilityStatusManager->combine($record);
        $combinedAvailability = $combinedInfo['availability'];

        $reserve = ($record[0]['reserve'] ?? 'N') === 'Y';

        // Sort locations by displayed name
        usort($locationList, [$this, 'sortLocationList']);

        // Send back the collected details:
        return [
            'id' => $record[0]['id'],
            'availability' => $combinedAvailability->availabilityAsString(),
            'availability_message' => $itemList ? $this->getAvailabilityMessage($combinedAvailability) : '',
            'location' => false,
            'locationList' => $locationList,
            'reserve' => $reserve ? 'true' : 'false',
            'reserve_message'
                => $this->translate($reserve ? 'on_reserve' : 'Not On Reserve'),
            'callnumber' => false,
            'missing_data' => $onlyHandsets
        ];
    }

    protected function sortLocationList($location1, $location2) : int {
        return strcasecmp($location1['location'], $location2['location']);
    }
}
