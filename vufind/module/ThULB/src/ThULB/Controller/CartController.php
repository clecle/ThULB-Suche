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
    public function homeAction()
    {
        if($this->getCart()->isFull()) {
           $this->flashMessenger()->addMessage($this->translate('bookbag_full_info'), 'info');
        }

        $this->layout()->setVariable('showBreadcrumbs', false);        
        return parent::homeAction();
    }
    
    public function processorAction()
    {
        $this->layout()->setVariable('showBreadcrumbs', false);        
        return parent::processorAction();
    }
}
