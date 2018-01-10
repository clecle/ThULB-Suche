<?php

namespace ThULB\Search\Solr;

use VuFind\Search\Solr\Results as OriginalResults;

/**
 * Description of Results
 *
 * @author Richard Großer <richard.grosser@thulb.uni-jena.de>
 */
class Results extends OriginalResults
{
    use \ThULB\Search\Results\SortedFacetsTrait;
}
