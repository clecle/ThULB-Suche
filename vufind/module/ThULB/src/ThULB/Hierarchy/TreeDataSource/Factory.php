<?php
/**
 * Hierarchy Data Source Factory Class
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  HierarchyTree_DataSource
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 */
namespace ThULB\Hierarchy\TreeDataSource;
use Laminas\ServiceManager\ServiceManager;

/**
 * Hierarchy Data Source Factory Class
 *
 * This is a factory class to build objects for managing hierarchies.
 *
 * @category ThULB
 * @package  HierarchyTree_DataSource
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:hierarchy_components Wiki
 *
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Factory for Solr driver.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Solr
     */
    public static function getSolr(ServiceManager $sm) : Solr
    {
        $cacheDir = $sm->get('VuFind\CacheManager')->getCacheDir(false);
        $hierarchyFilters = $sm->get('VuFind\Config')->get('HierarchyDefault');
        $filters = isset($hierarchyFilters->HierarchyTree->filterQueries)
          ? $hierarchyFilters->HierarchyTree->filterQueries->toArray()
          : [];
        $solr = $sm->get('VuFind\Search\BackendManager')->get('Solr')->getConnector();
        $formatterManager = $sm->get('VuFind\HierarchyTreeDataFormatterPluginManager');
        
        $searchSettings = $sm->get('VuFind\Config')->get('searches');
        $maxRows = $searchSettings->General->result_limit;
                
        return new Solr(
            $solr, $formatterManager, rtrim($cacheDir, '/') . '/hierarchy',
            $filters, $maxRows
        );
    }
}
