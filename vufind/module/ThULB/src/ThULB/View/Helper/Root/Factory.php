<?php
/**
 * Factory methods for customized view helpers.
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

namespace ThULB\View\Helper\Root;
use Laminas\ServiceManager\ServiceManager;

/**
 * Description of Factory
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Factory 
{
    /**
     * Construct the Record helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Record
     */
    public static function getRecord(ServiceManager $sm)
    {
        $helper = new Record(
            $sm->get('VuFind\Config')->get('config'),
            $sm->get(\VuFind\RecordTab\PluginManager::class)->get(\ThULB\RecordTab\NonArticleCollectionList::class)
        );
        $helper->setCoverRouter(
            $sm->get('VuFind\Cover\Router')
        );
        return $helper;
    }

    /**
     * Construct the RecordLink helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return RecordLink
     */
    public static function getRecordLink(ServiceManager $sm)
    {
        return new RecordLink($sm->get('VuFind\RecordRouter'));
    }

    /**
     * Construct the Session helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Session
     */
    public static function getSession(ServiceManager $sm)
    {
        return new Session($sm->get('VuFind\SessionManager'));
    }

    /**
     * Construct the Unpaywall helper.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return DoiLinker
     */
    public static function getDoiLinker(ServiceManager $sm)
    {
        $config = $sm->get(\VuFind\Config\PluginManager::class)
            ->get('config');
        $pluginManager = $sm->get(\VuFind\DoiLinker\PluginManager::class);
        return new DoiLinker($pluginManager, $config->DOI->resolver ?? null);
    }
}
