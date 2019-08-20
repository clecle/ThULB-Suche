<?php
/**
 * Custom Record Tab Factory Class
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2014.
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
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace ThULB\RecordTab;
use Zend\ServiceManager\ServiceManager;

/**
 * Record Tab Factory Class
 *
 * @category ThULB
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>+
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 *
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Factory for CollectionList tab plugin.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return CollectionList
     */
    public static function getArticleCollectionList(ServiceManager $sm)
    {
        return new ArticleCollectionList(
            $sm->get('VuFind\SearchRunner'),
            $sm->get('VuFind\RecommendPluginManager')
        );
    }
    
    /**
     * Factory for CollectionList tab plugin.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return CollectionList
     */
    public static function getNonArticleCollectionList(ServiceManager $sm)
    {
        return new NonArticleCollectionList(
            $sm->get('VuFind\SearchRunner'),
            $sm->get('VuFind\RecommendPluginManager')
        );
    }

    /**
     * Factory for CollectionList tab plugin.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return CollectionList
     */
    public static function getRecordLinkCollectionList(ServiceManager $sm)
    {
        return new RecordLinkCollectionList(
            $sm->get('VuFind\SearchRunner'),
            $sm->get('VuFind\RecommendPluginManager')
        );
    }
}
