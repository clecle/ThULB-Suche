<?php
/**
 * Override of the VuFind Book Bag / Bulk Action Controller
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2015.
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 */

namespace ThULB\Controller;
use VuFind\Controller\CartController as OriginalCartController;

/**
 * Book Bag / Bulk Action Controller
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class CartController extends OriginalCartController
{
    use ChangePasswordTrait;

    public function homeAction()
    {
        if($this->getCart()->isFull()) {
           $this->flashMessenger()->addMessage($this->translate('bookbag_full_info'), 'warning');
        }

        $this->layout()->setVariable('showBreadcrumbs', false);        
        return parent::homeAction();
    }
    
    public function processorAction()
    {
        $this->layout()->setVariable('showBreadcrumbs', false);        
        return parent::processorAction();
    }

    /**
     * Get selected ids
     *
     * @return array
     */
    protected function getSelectedIds()
    {
        // Values may be stored as a default state (checked_default), a list of IDs that do not
        // match the default state (non_default_ids), and a list of all IDs (all_ids_global). If these
        // values are found, we need to calculate the selected list from them.
        $checkedDefault = $this->params()->fromPost('checked_default') !== null;
        $nonDefaultIds = $this->params()->fromPost('non_default_ids');
        $allIdsGlobal = $this->params()->fromPost('all_ids_global', '[]');
        if ($nonDefaultIds !== null) {
            $nonDefaultIds = json_decode($nonDefaultIds);
            return array_values(array_filter(
                json_decode($allIdsGlobal),
                function ($id) use ($checkedDefault, $nonDefaultIds) {
                    $nonDefaultId = in_array($id, $nonDefaultIds);
                    return $checkedDefault xor $nonDefaultId;
                }
            ));
        }
        // If we got this far, values were passed in a simpler format: a list of checked IDs (ids),
        // a list of all IDs on the current page (idsAll), and whether the whole page is
        // selected (selectAll):
        // FIX: keep ids to add to favorite list after creating a new favorite list
        return null === $this->params()->fromPost('selectAll', $this->params()->fromQuery('selectAll'))
            ? $this->params()->fromPost('ids', $this->params()->fromQuery('ids', []))
            : $this->params()->fromPost('idsAll', $this->params()->fromQuery('idsAll', []));
    }
}
