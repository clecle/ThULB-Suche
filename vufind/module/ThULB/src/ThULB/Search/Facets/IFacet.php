<?php

namespace ThULB\Search\Facets;

use VuFind\Search\Base\Params;
use VuFindSearch\Backend\Solr\Response\Json\NamedList;

interface IFacet
{
    /**
     * Populates or creates the facet list for this field.
     *
     * @param string    $field The field of this list.
     * @param NamedList $data   The data to populate the facet list with.
     * @param Params    $params Params of the search.
     *
     * @return array
     */
    public function getFacetList($field, $data, $params);

    /**
     * Return the filter value associated with the given value.
     *
     * @param string $value Value to get the filter value for.
     *
     * @return string
     */
    public function getFilterValue($value);
}