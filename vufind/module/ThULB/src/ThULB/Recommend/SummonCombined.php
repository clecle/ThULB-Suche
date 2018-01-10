<?php

namespace ThULB\Recommend;

use VuFind\Recommend\AbstractSummonRecommend;

/**
 * Extracts all results of interest at once instead of calling the api multiple
 * times to e.g. get best bets and databases seperately.
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class SummonCombined extends AbstractSummonRecommend
{
    /**
     * Get best bets and database results together.
     *
     * @return array
     */
    public function getResults()
    {
        return [
                   'best_bets' => $this->results->getBestBets(),
                   'databases' => $this->results->getDatabaseRecommendations()
               ];
                
    }
}
