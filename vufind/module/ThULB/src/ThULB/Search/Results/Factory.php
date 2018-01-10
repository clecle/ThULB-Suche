<?php

namespace ThULB\Search\Results;
use Zend\ServiceManager\ServiceManager,
    ThULB\Search\Summon\Results as SummonResults,
    ThULB\Search\Solr\Results as SolrResults;

/**
 * Description of Factory
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Factory {
    /**
     * Factory for Summon results object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Summon
     */
    public static function getSummon(ServiceManager $sm)
    {
        $options = $sm->getServiceLocator()
            ->get('VuFind\SearchParamsPluginManager')->get('Summon');
        $searchService = $sm->getServiceLocator()
            ->get('VuFind\Search');
        $recordLoader = $sm->getServiceLocator()
            ->get('VuFind\RecordLoader');
        
        // Clone the options instance in case caller modifies it:
        return new SummonResults(
                clone($options),
                $searchService,
                $recordLoader
            );
    }
    
    public static function getSolr(ServiceManager $sm)
    {
        $params = $sm->getServiceLocator()
            ->get('VuFind\SearchParamsPluginManager')->get('Solr');
        $searchService = $sm->getServiceLocator()->get('VuFind\Search');
        $recordLoader = $sm->getServiceLocator()->get('VuFind\RecordLoader');
        
        $solr = new SolrResults($params, $searchService, $recordLoader);
        
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        $spellConfig = isset($config->Spelling) ? $config->Spelling : null;
        $solr->setSpellingProcessor(
            new \VuFind\Search\Solr\SpellingProcessor($spellConfig)
        );
        
        return $solr;
    }
}
