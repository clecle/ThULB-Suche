<?php
/**
 * Factory methods for custom record drivers
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

namespace ThULB\RecordDriver;

class Factory
{
    /**
     * Factory for SolrMarc record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrMarc
     */
    public static function getSolrMarc(\Zend\ServiceManager\ServiceManager $sm)
    {
        $driver = new \ThULB\RecordDriver\SolrVZGRecord(
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            null,
            $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
        );
        $driver->attachILS(
            $sm->getServiceLocator()->get('VuFind\ILSConnection'),
            $sm->getServiceLocator()->get('VuFind\ILSHoldLogic'),
            $sm->getServiceLocator()->get('VuFind\ILSTitleHoldLogic')
        );
        $driver->attachSearchService($sm->getServiceLocator()->get('VuFind\Search'));
        return $driver;
    }
    
    /**
     * Factory for Summon record driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Summon
     */
    public static function getSummon(\Zend\ServiceManager\ServiceManager $sm)
    {
        $summon = $sm->getServiceLocator()->get('VuFind\Config')->get('Summon');
        $driver = new Summon(
            $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
            $summon, $summon
        );
        $driver->setDateConverter(
            $sm->getServiceLocator()->get('VuFind\DateConverter')
        );
        return $driver;
    }
}
