<?php
/**
 * View helper for holdings
 *
 * PHP version 5
 *
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
 * @author   Clemes Kynast <clemens.kynast@thulb.uni-jena.de>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 */

namespace DHGE\View\Helper\Record;

use ThULB\View\Helper\Record\HoldingHelper as OriginalHoldingHelper;

class HoldingHelper extends OriginalHoldingHelper
{
    /**
     * Creates a string including the link to place a recall.
     *
     * @param array $itemRow
     *
     * @return array
     */
    public function getRecallLink(array $itemRow) : array {
        return $this->recallAvailable($itemRow) ? parent::getRecallLink($itemRow) : [];
    }

    protected function recallAvailable(array $itemRow): bool {
        return !$this->view->auth()->getUserObject() ||
            $itemRow['library'] == $this->view->dhge_session()->getLibrary();
    }

    public function getAvailability(&$itemRow) : string {
        // @TODO: move to DHGE specific template
        $str = parent::getAvailability($itemRow);

        if($itemRow['status'] != 'available' && !$this->recallAvailable($itemRow)) {
            $str .= '<br>' . $this->view->transEsc('recall_not_available_in_your_library');
        }

        return $str;
    }
}
