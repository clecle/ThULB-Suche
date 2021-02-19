<?php

namespace ThULB\Search\Solr;

use VuFind\Search\Solr\Options as OriginalOptions;

/**
 * Created to enable VuFind to find the Options when using ThULB\Search\Solr\Params
 */
class Options extends OriginalOptions
{
    public function setResultLimit(int $limit) {
        $this->resultLimit = $limit;
    }
}