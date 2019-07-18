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

namespace ThBIB\Search\Results;
use Zend\ServiceManager\ServiceManager,
    ThBIB\Search\Solr\Results as SolrResults;

/**
 * Factory
 */
class Factory extends \ThULB\Search\Results\Factory {

    public static function getSolr(ServiceManager $sm)
    {
        $params = $sm->get('VuFind\SearchParamsPluginManager')->get('Solr');
        $searchService = $sm->get('VuFind\Search');
        $recordLoader = $sm->get('VuFind\RecordLoader');
        
        $solr = new SolrResults($params, $searchService, $recordLoader);
        
        $config = $sm->get('VuFind\Config')->get('config');
        $spellConfig = isset($config->Spelling) ? $config->Spelling : null;
        $solr->setSpellingProcessor(
            new \VuFind\Search\Solr\SpellingProcessor($spellConfig)
        );
        
        return $solr;
    }
}
