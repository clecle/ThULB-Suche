<?php

/**
 * Helper class for rendering availability statuses.
 *
 * PHP version 8
 *
 * Copyright (C) Hebis Verbundzentrale 2024.
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
 * @package  View_Helpers
 * @author   Thomas Wagener <wagener@hebis.uni-frankfurt.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */

namespace ThULB\View\Helper\Root;

use VuFind\ILS\Logic\AvailabilityStatusInterface;
use VuFind\View\Helper\Root\AvailabilityStatus as OriginalAvailabilityStatus;

/**
 * Helper class for rendering availability statuses.
 *
 * @category VuFind
 * @package  View_Helpers
 * @author   Thomas Wagener <wagener@hebis.uni-frankfurt.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class AvailabilityStatus extends OriginalAvailabilityStatus
{
    /**
     * Html class for items where status is unknown.
     *
     * @var string
     */
    protected string $classUnknown = 'text-warning';

    protected string $classOrdered = 'text-status-ordered';

    /**
     * Get html class for availability status.
     *
     * @param AvailabilityStatusInterface $availabilityStatus Availability Status
     *
     * @return string
     */
    public function getClass(AvailabilityStatusInterface $availabilityStatus): string
    {
        if ($availabilityStatus->is(\VuFind\ILS\Logic\AvailabilityStatusInterface::STATUS_UNAVAILABLE)) {
            return $this->classUnavailable;
        }
        if ($availabilityStatus->is(\VuFind\ILS\Logic\AvailabilityStatusInterface::STATUS_AVAILABLE)) {
            return $this->classAvailable;
        }
        if ($availabilityStatus->is(\ThULB\ILS\Logic\AvailabilityStatus::STATUS_ORDERED)) {
            return $this->classOrdered;
        }
        if ($availabilityStatus->is(\VuFind\ILS\Logic\AvailabilityStatusInterface::STATUS_UNKNOWN)) {
            return $this->classUnknown;
        }
        return $this->classUncertain;
    }

    /**
     * Render ajax status.
     *
     * @param AvailabilityStatusInterface $availabilityStatus Availability Status
     *
     * @return string
     */
    public function renderStatusForAjaxResponse(AvailabilityStatusInterface $availabilityStatus): string
    {
        if ($availabilityStatus->is(\VuFind\ILS\Logic\AvailabilityStatusInterface::STATUS_UNKNOWN)) {
            $key = 'ajax/status-unknown.phtml';
        } elseif ($availabilityStatus->is(\VuFind\ILS\Logic\AvailabilityStatusInterface::STATUS_AVAILABLE)) {
            $key = 'ajax/status-available.phtml';
        } elseif ($availabilityStatus->is(\ThULB\ILS\Logic\AvailabilityStatus::STATUS_ORDERED)) {
            $key = 'ajax/status-ordered.phtml';
        } elseif ($availabilityStatus->is(\VuFind\ILS\Logic\AvailabilityStatusInterface::STATUS_UNAVAILABLE)) {
            $key = 'ajax/status-unavailable.phtml';
        } else {
            $key = 'ajax/status-uncertain.phtml';
        }
        if (!isset($this->messageCache[$key])) {
            $this->messageCache[$key] = $this->getView()->render($key);
        }
        return $this->messageCache[$key];
    }
}
