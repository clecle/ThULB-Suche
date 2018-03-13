<?php
/**
 * Factory class for custom controller classer
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
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 */

namespace ThULB\Controller;
use  Zend\ServiceManager\ServiceManager;

/**
 * Factory to load our controllers.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Factory
{
    /**
     * Construct the AjaxController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return AjaxController
     */
    public function getAjaxController(ServiceManager $sm)
    {
        return new AjaxController($sm->getServiceLocator());
    }

    /**
     * Construct the CartController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return CartController
     */
    public static function getCartController(ServiceManager $sm)
    {
        return new CartController(
            $sm->getServiceLocator(),
            new \Zend\Session\Container(
                'cart_followup',
                $sm->getServiceLocator()->get('VuFind\SessionManager')
            )
        );
    }
    
    /**
     * Construct the MyResearchController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return MyResearchController
     */
    public function getMyResearchController(ServiceManager $sm)
    {
        return new MyResearchController($sm->getServiceLocator());
    }
    
    /**
     * Construct the SummonController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SummonController
     */
    public function getSummonController(ServiceManager $sm)
    {
        return new SummonController($sm->getServiceLocator());
    }
    
    /**
     * Construct the SummonrecordController.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SummonrecordController
     */
    public function getSummonrecordController(ServiceManager $sm)
    {
        return new SummonrecordController($sm->getServiceLocator());
    }
}
