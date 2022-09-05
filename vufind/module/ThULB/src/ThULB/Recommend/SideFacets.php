<?php

namespace ThULB\Recommend;

use Exception;
use ThULB\Search\Solr\HierarchicalFacetHelper;
use VuFind\Recommend\SideFacets as OriginalSideFacets;

class SideFacets extends OriginalSideFacets
{
    /**
     * Hierarchical facet helper
     *
     * @var HierarchicalFacetHelper
     */
    protected $hierarchicalFacetHelper;

    /**
     * Get facet information from the search results.
     *
     * @return array
     *
     * @throws Exception
     */
    public function getFacetSet() : array
    {
        $facetSet = $this->results->getFacetList($this->mainFacets);

        foreach ($this->hierarchicalFacets as $hierarchicalFacet) {
            if (isset($facetSet[$hierarchicalFacet])) {
                if (!$this->hierarchicalFacetHelper) {
                    throw new Exception(
                        get_class($this) . ': hierarchical facet helper unavailable'
                    );
                }

                // use ThBIB helper
                $facetArray = $this->hierarchicalFacetHelper->buildFacetArray(
                    $hierarchicalFacet, $facetSet[$hierarchicalFacet]['list'],
                    $this->results->getUrlQuery(), true, $this->results
                );
                $facetSet[$hierarchicalFacet]['list'] = $this
                    ->hierarchicalFacetHelper
                    ->flattenFacetHierarchy($facetArray);
            }
        }

        return $facetSet;
    }
}