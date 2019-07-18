<?php

namespace ThBIB\Recommend;

use VuFind\Recommend\SideFacets as OriginalSideFacets;

class SideFacets extends OriginalSideFacets
{
    /**
     * Get facet information from the search results.
     *
     * @return array
     * @throws \Exception
     */
    public function getFacetSet()
    {
        $facetSet = $this->results->getFacetList($this->mainFacets);

        foreach ($this->hierarchicalFacets as $hierarchicalFacet) {
            if (isset($facetSet[$hierarchicalFacet])) {
                if (!$this->hierarchicalFacetHelper) {
                    throw new \Exception(
                        get_class($this) . ': hierarchical facet helper unavailable'
                    );
                }

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