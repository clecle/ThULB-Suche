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
namespace DHGE\AjaxHandler;

use ThULB\AjaxHandler\GetItemStatuses as OriginalGetItemStatuses;
use VuFind\ILS\Logic\AvailabilityStatusInterface;

class GetItemStatuses extends OriginalGetItemStatuses
{
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
    protected function getItemStatusGroup($record, $callnumberSetting) : array
    {
        $statusGroup = parent::getItemStatusGroup($record, $callnumberSetting);

        $statusGroup['availability_message'] = '';

        // Check availabilities for each library
        $libAvailability = [];
        foreach ($record as $info) {
            $libAvailability[$info['library']][] = $info;
        }

        // Sort libraries by name
        ksort($libAvailability);

        // Create availability message for each library.
        $availabilityMessage = '';
        foreach($libAvailability as $items) {
            $combinedInfo = $this->availabilityStatusManager->combine($items);
            $combinedAvailability = $combinedInfo['availability'];
            $availabilityMessage .= $this->getAvailabilityMessage($combinedAvailability);
        }

        if($availabilityMessage) {
            $statusGroup['availability_message'] = $availabilityMessage;
        }

        return $statusGroup;
    }
}
