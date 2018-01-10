<?php

namespace ThULB\Recommend;

use Zend\ServiceManager\ServiceManager;

/**
 * Factory methods for Recommender modules
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Factory
{
    /**
     * Factory for SummonCombined module.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return SummonCombined
     */
    public static function getSummonCombined(ServiceManager $sm)
    {
        return new SummonCombined(
            $sm->getServiceLocator()->get('VuFind\SearchResultsPluginManager')
        );
    }
}
