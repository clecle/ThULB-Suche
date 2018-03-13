<?php
/**
 * Override of the VuFind Ajax Controller Module
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

use VuFind\Controller\AjaxController as OriginalAjaxController;

/**
 * This controller handles global AJAX functionality
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class AjaxController extends OriginalAjaxController {
    
    public function getResultCountAjax()
    {
        $index = $this->params()->fromPost('index', $this->params()->fromQuery('index'));
        $lookFor = $this->params()->fromPost('lookfor', $this->params()->fromQuery('lookfor'));
        $type = $this->params()->fromPost('type', $this->params()->fromQuery('type'));
       
        $runner = $this->getServiceLocator()->get('VuFind\SearchRunner');
        $result = $runner->run(['limit' => '0', 'type' => $type, 'lookfor' => $lookFor], $index);
        
        $numberFormatter = $this->getViewRenderer()->plugin('localizedNumber');
        
        return $this->output(['count' => $numberFormatter($result->getResultTotal())], self::STATUS_OK);
    }
}
