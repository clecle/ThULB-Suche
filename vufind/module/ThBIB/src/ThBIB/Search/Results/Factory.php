<?php

namespace ThBIB\Search\Results;

use VuFind\Search\Solr\SpellingProcessor;
use Zend\ServiceManager\ServiceManager;
use ThBIB\Search\Solr\Results;

/**
 * Factory
 */
class Factory extends \ThULB\Search\Results\Factory {

    /**
     * Returns Results object for Solr.
     *
     * @param ServiceManager $sm
     *
     * @return Results
     */
    public static function getSolr(ServiceManager $sm)
    {
        $params = $sm->get('VuFind\SearchParamsPluginManager')->get('Solr');
        $searchService = $sm->get('VuFind\Search');
        $recordLoader = $sm->get('VuFind\RecordLoader');
        
        $solr = new Results($params, $searchService, $recordLoader);
        
        $config = $sm->get('VuFind\Config')->get('config');
        $spellConfig = isset($config->Spelling) ? $config->Spelling : null;
        $solr->setSpellingProcessor(
            new SpellingProcessor($spellConfig)
        );
        
        return $solr;
    }
}
