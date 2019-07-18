<?php


namespace ThBIB\Recommend;

use ThULB\Recommend\Factory as OriginalFactory;
use Zend\ServiceManager\ServiceManager;

class Factory extends OriginalFactory
{
    /**
     * Factory for SideFacets module.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SideFacets
     */
    public static function getSideFacets(ServiceManager $sm)
    {
        return new SideFacets(
            $sm->get('VuFind\Config\PluginManager'),
            $sm->get('ThBIB\Search\Solr\HierarchicalFacetHelper')
        );
    }
}