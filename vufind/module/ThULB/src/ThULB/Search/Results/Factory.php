<?php
/**
 * Custom factory methods for result model classes.
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
 * @author   Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 */

namespace ThULB\Search\Results;
use VuFind\Search\Solr\SpellingProcessor;
use Laminas\ServiceManager\ServiceManager;
use ThULB\Search\Summon\Results as SummonResults;
use ThULB\Search\Solr\Results as SolrResults;
use ThULB\Search\Blender\Results as BlenderResults;

/**
 * Factory
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Factory {
    /**
     * Factory for Summon results object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SummonResults
     */
    public static function getSummon(ServiceManager $sm) : SummonResults
    {
        $options = $sm->get('VuFind\SearchParamsPluginManager')->get('Summon');
        $searchService = $sm->get('VuFind\Search');
        $recordLoader = $sm->get('VuFind\RecordLoader');
        
        // Clone the options instance in case caller modifies it:
        return new SummonResults(
                clone($options),
                $searchService,
                $recordLoader
            );
    }

    /**
     * Factory for Solr results object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrResults
     */
    public static function getSolr(ServiceManager $sm) : SolrResults
    {
        $params = $sm->get('VuFind\SearchParamsPluginManager')->get('Solr');
        $searchService = $sm->get('VuFind\Search');
        $recordLoader = $sm->get('VuFind\RecordLoader');
        $facetManager = $sm->get(\ThULB\Search\Facets\PluginManager::class);

        $solr = new SolrResults($params, $searchService, $recordLoader, $facetManager);

        $config = $sm->get('VuFind\Config')->get('config');
        $spellConfig = $config->Spelling ?? null;
        $solr->setSpellingProcessor(
            new SpellingProcessor($spellConfig)
        );

        return $solr;
    }

    /**
     * Factory for Solr results object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SolrResults
     */
    public static function getBlender(ServiceManager $sm) : BlenderResults
    {
        $params = $sm->get('VuFind\SearchParamsPluginManager')->get('Blender');
        $searchService = $sm->get('VuFind\Search');
        $recordLoader = $sm->get('VuFind\RecordLoader');
        $facetManager = $sm->get(\ThULB\Search\Facets\PluginManager::class);

        $blender = new BlenderResults($params, $searchService, $recordLoader, $facetManager);

        $config = $sm->get('VuFind\Config')->get('config');
        $spellConfig = $config->Spelling ?? null;
        $blender->setSpellingProcessor(
            new SpellingProcessor($spellConfig)
        );

        return $blender;
    }
}
