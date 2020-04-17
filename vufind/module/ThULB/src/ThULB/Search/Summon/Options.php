<?php

namespace ThULB\Search\Summon;

use VuFind\Search\Summon\Options as OriginalOptions;

class Options extends OriginalOptions
{
    /**
     * Available sort options for facets
     *
     * @var array
     */
    protected $facetSortOptions = [
        'count' => 'sort_count',
        'index' => 'sort_alphabetic'
    ];
}