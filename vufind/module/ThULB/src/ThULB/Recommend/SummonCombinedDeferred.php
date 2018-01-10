<?php

namespace ThULB\Recommend;

use VuFind\Recommend\AbstractSummonRecommendDeferred;

/**
 * Module for asynchronous loading of SummonCombined recommendations.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 * @see ThULB\Recommend\SummonCombined
 */
class SummonCombinedDeferred extends AbstractSummonRecommendDeferred
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->module = 'SummonCombined';
    }
}